<?php

namespace App\Listener\Bundles\LexitJwtAuthentication;

use App\Entity\Security\User;
use App\Repository\Security\UserRepository;
use App\Service\Points\UserPointsLimiterService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\System\Restriction\EmailTemplateRestrictionService;
use App\Service\System\Restriction\JobSearchRestrictionService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles the action when jwt has been created -> manipulates the payload / adds new fields
 * @link https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/2-data-customization.md
 */
class JwtCreatedListener implements EventSubscriberInterface
{

    const JWT_KEY_ACCOUNT_TYPE   = "accountType";
    const JWT_KEY_USER_ID        = "userId";
    const JWT_KEY_IS_USER_ACTIVE = "isUserActive";

    private const JWT_KEY_ACCOUNT = "account";

    private const JWT_KEY_ACCOUNT_MAX_PARALLEL_JOB_SEARCHES = "maxParallelJobSearches";
    private const JWT_KEY_ACCOUNT_MAX_SEARCHED_KEYWORDS = "maxSearchedKeywords";
    private const JWT_KEY_ACCOUNT_MAX_POINTS_ALLOWED = "maxPointsAllowed";
    private const JWT_KEY_CAN_GENERATE_PDF_FROM_TEMPLATE = "canGeneratePdfFromTemplate";
    private const JWT_KEY_HAS_BOUGH_ANY_POINTS = "hasBoughtAnyPoints";

    private const JWT_KEY_FIRSTNAME        = "firstname";
    private const JWT_KEY_LASTNAME         = "lastname";
    private const JWT_KEY_POINTS           = "points";
    private const JWT_KEY_PENDING_POINTS   = "pendingPoints";
    private const JWT_KEY_ZIP              = "zip";
    private const JWT_KEY_STREET           = "street";
    private const JWT_KEY_CITY             = "city";
    private const JWT_KEY_HOME_NUMBER      = "homeNumber";
    private const JWT_KEY_COUNTRY_CODE     = "countryCode";
    private const JWT_KEY_PROFILE_PIC_PATH = "profilePicturePath";

    public function __construct(
        private readonly JobSearchRestrictionService     $jobSearchRestrictionService,
        private readonly EmailTemplateRestrictionService $emailTemplateRestrictionService,
        private readonly UserRepository                  $userRepository,
    ){}

    /**
     * Handle the event
     *
     * @param JWTCreatedEvent $event
     */
    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();
        $data = $event->getData();

        $this->userRepository->refreshUserWithRelations($user);

        $profilePicture     = $user->getFirstProfileImage();
        $profilePicturePath = $profilePicture?->getLinkedPathWithFileName();

        $newData = array_merge($data, [
            JwtAuthenticationService::JWT_KEY_EMAIL    => $user->getUserIdentifier(),
            JwtAuthenticationService::JWT_KEY_USERNAME => $user->getUsername(),
            self::JWT_KEY_FIRSTNAME                    => $user->getFirstname(),
            self::JWT_KEY_POINTS                       => $user->getPointsAmount(),
            self::JWT_KEY_PENDING_POINTS               => $user->getPendingPointsAmount(),
            self::JWT_KEY_LASTNAME                     => $user->getLastname(),
            self::JWT_KEY_ZIP                          => $user->getAddress()->getZip(),
            self::JWT_KEY_STREET                       => $user->getAddress()->getStreet(),
            self::JWT_KEY_CITY                         => $user->getAddress()->getCity(),
            self::JWT_KEY_HOME_NUMBER                  => $user->getAddress()->getHomeNumber(),
            self::JWT_KEY_COUNTRY_CODE                 => $user->getAddress()->getCountry()->name,
            self::JWT_KEY_ACCOUNT_TYPE                 => $user->getAccount()->getType()->getName(),
            self::JWT_KEY_IS_USER_ACTIVE               => $user->isActive(),
            self::JWT_KEY_USER_ID                      => $user->getId(),
            self::JWT_KEY_PROFILE_PIC_PATH             => $profilePicturePath,
            self::JWT_KEY_ACCOUNT                      => [
                self::JWT_KEY_ACCOUNT_MAX_PARALLEL_JOB_SEARCHES => $this->jobSearchRestrictionService->getMaxActiveSearch($user),
                self::JWT_KEY_ACCOUNT_MAX_SEARCHED_KEYWORDS     => $this->jobSearchRestrictionService->getMaxSearchedKeywords($user),
                self::JWT_KEY_ACCOUNT_MAX_POINTS_ALLOWED        => UserPointsLimiterService::MAX_POINTS_PER_USER,
                self::JWT_KEY_HAS_BOUGH_ANY_POINTS              => !$user->getPointHistory()->isEmpty(),
                self::JWT_KEY_CAN_GENERATE_PDF_FROM_TEMPLATE    => $this->emailTemplateRestrictionService->canGeneratePdf($user),
            ],
        ]);

        $event->setData($newData);
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => "onJwtCreated",
        ];
    }
}