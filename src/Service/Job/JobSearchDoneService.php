<?php

namespace App\Service\Job;

use App\Controller\Email\EmailController;
use App\DTO\Internal\WebsocketNotificationDto;
use App\DTO\RabbitMq\Consumer\JobSearch\Done\ParameterBag;
use App\DTO\RabbitMq\Consumer\JobSearch\Done\ParameterBag as ConsumerDoneParameterBag;
use App\Entity\Email\Email;
use App\Entity\Job\JobSearchResult;
use App\Entity\Security\User;
use App\RabbitMq\Consumer\JobSearch\JobSearchDoneConsumer;
use App\Service\Messages\Notification\WebsocketNotificationService;
use App\Service\Templates\Email\JobSearchTemplatesService;
use App\Service\Websocket\Endpoint\NotificationWebsocketEndpoint;
use App\Vue\VueRoutes;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\Intl\Countries;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Mostly related to: {@see JobSearchDoneConsumer}
 */
class JobSearchDoneService
{
    private const USER_EMAIL_SUBJECT_FAILURE = "Job search failed";
    private const USER_EMAIL_SUBJECT_SUCCESS = "Job search finished with success";

    public function __construct(
        private readonly EmailController              $emailController,
        private readonly JobSearchTemplatesService    $jobSearchTemplatesService,
        private readonly WebsocketNotificationService $websocketNotificationService,
        private readonly TranslatorInterface          $translator,
        private readonly EntityManagerInterface       $entityManager,
    ) {
    }

    /**
     * Sends websocket notification to user
     *
     * @param User                     $user
     * @param ConsumerDoneParameterBag $consumerDoneParameterBag
     *
     * @throws Exception
     */
    public function notifyViaSocket(User $user, ConsumerDoneParameterBag $consumerDoneParameterBag): void
    {
        $message = $this->translator->trans('job.search.messages.doneSuccess');
        if (!$consumerDoneParameterBag->isSuccess()) {
            $message = $this->translator->trans('job.search.messages.doneFailure');
        }

        $notification = new WebsocketNotificationDto();
        $notification->setUserIdToFindConnection((string)$user->getId());
        $notification->setSocketEndpointName(NotificationWebsocketEndpoint::SERVER_ENDPOINT_NAME);
        $notification->setFindConnectionBy(WebsocketNotificationDto::FIND_CONNECTION_BY_USER_ID);
        $notification->setMessage($message);
        $notification->setFrontendHandlerName(NotificationWebsocketEndpoint::FRONTEND_HANDLER_NAME);

        $this->websocketNotificationService->sendAsyncNotification($notification);
    }

    /**
     * Will update the related {@see JobSearchResult} with the {@see JobSearchResult::$externalExtractionId}
     *
     * @param ParameterBag    $parameterBag
     * @param JobSearchResult $searchResult
     */
    public function updateSearchEntity(ParameterBag $parameterBag, JobSearchResult $searchResult): void
    {
        $searchResult->setExternalExtractionId($parameterBag->getExtractionId());
        $searchResult->setStatusFromJobOfferHandler($parameterBag->getExtractionStatus());
        $searchResult->setPercentageDone($parameterBag->getPercentageDone());

        $this->entityManager->persist($searchResult);
        $this->entityManager->flush();
    }

    /**
     * Will handle building and saving email for finished job search
     *
     * @throws ORMException
     * @throws RuntimeError
     * @throws LoaderError
     * @throws OptimisticLockException
     * @throws SyntaxError
     * @throws Exception
     */
    public function buildEmail(ConsumerDoneParameterBag $consumerDoneParameterBag, JobSearchResult $searchResult): void
    {
        $templateVariables = $this->buildEmailTemplateVariables($searchResult);
        if (!$consumerDoneParameterBag->isSuccess() || empty($consumerDoneParameterBag->getExtractionId())) {
            $userEmailSubject = self::USER_EMAIL_SUBJECT_FAILURE;
            $userEmailBody    = $this->jobSearchTemplatesService->renderSearchFailure($templateVariables);

            $email = new Email();
            $email->setBody($userEmailBody);
            $email->setSubject($userEmailSubject);
            $email->setRecipients([$searchResult->getUser()->getEmail()]);
            $this->emailController->save($email);

            return;
        }

        $templateVariables = $this->addSuccessTemplateVariables($searchResult, $templateVariables);
        $userEmailSubject  = self::USER_EMAIL_SUBJECT_SUCCESS;
        $userEmailBody     = $this->jobSearchTemplatesService->renderSearchSuccess($templateVariables);

        $email = new Email();
        $email->setBody($userEmailBody);
        $email->setSubject($userEmailSubject);
        $email->setRecipients([$searchResult->getUser()->getEmail()]);
        $this->emailController->save($email);
    }

    /**
     * Builds the array of variables needed for rendering the body of E-Mail that will get sent after search is done
     * (no matter the status of search)
     *
     * @return string[]
     *
     * @throws Exception
     */
    private function buildEmailTemplateVariables(JobSearchResult $searchResult): array
    {
        /**
         * Cannot rely on the {@see \Locale::getDefault()} as it's empty string when called from RabbitMq context.
         */
        $locale = 'en_US_POSIX';

        return ([
            'searchId' => $searchResult->getId(),
            'keywords' => $searchResult->getKeywords(),
            'distance' => $searchResult->getMaxDistance(),
            'location' => $searchResult->getLocationName(),
            'country'  => Countries::getAlpha3Name(strtoupper($searchResult->getFirstTargetArea()), $locale),
            'user'     => $searchResult->getUser(),
        ]);
    }

    /**
     * @param JobSearchResult $searchResult
     * @param array           $templateVariables
     *
     * @return array
     */
    private function addSuccessTemplateVariables(JobSearchResult $searchResult, array $templateVariables): array
    {
        $templateVariables['detailsUrl'] = VueRoutes::buildFrontendUrlForRoute(VueRoutes::ROUTE_PATH_JOB_SEARCH_RESULT_DETAILS, [
            VueRoutes::ROUTE_PARAM_SEARCH_ID => $searchResult->getId(),
        ]);

        return $templateVariables;
    }

}
