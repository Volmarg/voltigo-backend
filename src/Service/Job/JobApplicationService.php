<?php

namespace App\Service\Job;

use App\Action\Job\JobApplicationAction;
use App\Action\Job\JobApplicationInterface;
use App\DTO\Frontend\JobOffer\Filter\FilterDTO;
use App\DTO\Validation\ValidationResultDTO;
use App\Entity\Job\JobSearchResult;
use App\Enum\Points\Shop\ProductIdentifierEnum;
use App\Enum\Service\Serialization\SerializerType;
use App\Exception\NotFoundException;
use App\Exception\Payment\PointShop\NotEnoughPointsException;
use App\Repository\Job\JobApplicationRepository;
use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\PointShop\PointShopProductPaymentService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Serialization\ObjectSerializerService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles the logic related to the application process for the offers
 * -{@see JobApplicationAction}
 */
class JobApplicationService
{

    public function __construct(
        private readonly JobSearchService               $jobSearchService,
        private readonly ObjectSerializerService        $objectSerializerService,
        private readonly TranslatorInterface            $translator,
        private readonly LoggerInterface                $logger,
        private readonly PointShopProductPaymentService $pointShopProductPaymentService,
        private readonly ParameterBagInterface          $parameterBag,
        private readonly JobApplicationRepository       $jobApplicationRepository,
        private readonly JwtAuthenticationService       $jwtAuthenticationService
    ){}

    /**
     * This performs a one last check of the offer applications E-Mail recipients integrity
     * Basically this function is supposed to prevent users from manipulating anything on front
     * like for example changing E-Mail recipient on front for give offer.
     *
     * So for each "application data" the {@see JobSearchService} will be called and checked if some E-Mail
     * has been changed or manipulated.
     *
     * Keep in mind special cases:
     * - offer being already removed on the searcher side (E-Mail will not be validated for such case)
     * - target E-Mail is indeed not there on the searcher side so user needs to be informed about
     *   the fact that he either changed the E-Mail or it was changed in DB so the easiest solution is to force user
     *   to reload the page to re-fetch offers with new E-Mails
     *
     * @param array $applicationsData
     * @param array $filterValues
     *
     * @return ValidationResultDTO
     * @throws GuzzleException
     * @throws Exception
     */
    public function validateRecipientsIntegrity(array $applicationsData, array $filterValues): ValidationResultDTO
    {

        /** @var  FilterDTO $frontFilter */
        $frontFilter = $this->objectSerializerService->fromJson(json_encode($filterValues), FilterDTO::class, SerializerType::CUSTOM);
        $frontFilter->selfCorrect();

        foreach ($applicationsData as $applicationData) {
            $offerId   = $applicationData[JobApplicationInterface::OFFER_ID];
            $recipient = $applicationData[JobApplicationInterface::RECIPIENT];

            $offer = $this->jobSearchService->getSingleOffer($offerId, $frontFilter);
            if ($offer->getContactDetail()->getEmail() !== $recipient) {
                $message = $this->translator->trans('job.application.sendApplication.messages.recipientChanged', [
                    "%company%" => $offer->getCompanyDetail()->getCompanyName(),
                    "%title%"   => $offer->getJobTitle(),
                ]);

                $this->logger->warning("Recipient was either manipulated or has just changed on searcher side", [
                    "externalOfferId"               => $offerId,
                    "recipient"                     => $recipient,
                    "searchedFetchedOfferRecipient" => $offer->getContactDetail()->getEmail(),
                ]);

                return ValidationResultDTO::buildInvalidValidation($message);
            }
        }

        return ValidationResultDTO::buildOkValidation();
    }

    /**
     * Handles "buying" the email sending, deducting user points and setting some information that will be
     * either shown for user on front or will be used eventually for some debugging etc.
     *
     * Trying to avoid using words here, because even tho it's not planned to support other languages than English
     * it would create issue where some hardcoded words would be saved in database, or would require some mechanism
     * to translate parts on front.
     *
     * @param array           $applicationsData
     * @param JobSearchResult $jobSearch
     *
     * @throws NotFoundException
     * @throws NotEnoughPointsException
     */
    public function buyEmailSending(array $applicationsData, JobSearchResult $jobSearch): void
    {
        $applicationsDataArray = [];
        foreach ($applicationsData as $applicationData) {
            $offerId          = $applicationData[JobApplicationInterface::OFFER_ID];
            $offerUrl         = $applicationData[JobApplicationInterface::OFFER_URL];
            $offerTitle       = $applicationData[JobApplicationInterface::OFFER_TITLE];
            $offerCompanyName = $applicationData[JobApplicationInterface::OFFER_COMPANY_NAME];

            // passing in only certain data else even entire E-Mail would be saved, and it's bad from perspective of data protection...
            $applicationsDataArray[] = [
                JobApplicationInterface::OFFER_ID           => $offerId,
                JobApplicationInterface::OFFER_URL          => $offerUrl,
                JobApplicationInterface::OFFER_TITLE        => $offerTitle,
                JobApplicationInterface::OFFER_COMPANY_NAME => $offerCompanyName,
            ];
        }

        $dataArray = [
            "serializedJobSearch" => $this->objectSerializerService->toArray($jobSearch),
            "applicationsData"    => $applicationsDataArray,
        ];

        $this->pointShopProductPaymentService->buy(
            ProductIdentifierEnum::EMAIL_SENDING->name,
            count($applicationsData),
            $dataArray,
            ["Job search Id: {$jobSearch->getId()}"]
        );
    }

    /**
     * Uses {@see self::canBeApplied()} to exclude offers for which applications cannot be sent
     *
     * @param array $applicationsData
     *
     * @return array
     */
    public function excludeApplicationDataOffers(array $applicationsData): array
    {
        foreach ($applicationsData as $index => $applicationData) {
            $offerId = $applicationData[JobApplicationInterface::OFFER_ID];
            if (!$this->canBeApplied($offerId)) {
                unset($applicationsData[$index]);
            }
        }

        return $applicationsData;
    }

    /**
     * User cannot apply for the same offer if the application for it was already sent in last "X" days,
     * this rule is already follow when fetching offers from searcher, but it's possible for user to open
     * 2 browsers and apply from both windows at same time
     *
     * @param int $offerId
     *
     * @return bool
     */
    private function canBeApplied(int $offerId): bool
    {
        $user                = $this->jwtAuthenticationService->getUserFromRequest();
        $daysOffset          = $this->parameterBag->get('exclude_applied_on_in_last_days');
        $existingApplication = $this->jobApplicationRepository->findById($offerId, $user, $daysOffset);

        return empty($existingApplication);
    }
}