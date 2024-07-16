<?php

namespace App\Security\Ddos;

use App\Action\Security\SystemAction;
use App\Action\Security\UserAction;
use App\Action\Storage\FrontendErrorStorageAction;
use App\Action\System\SecurityAction;
use App\Action\System\SystemGeoDataAction;
use App\Controller\Core\Env;
use App\Entity\Storage\Ban\BannedUserStorage;
use App\Repository\Storage\Ban\BannedUserStorageRepository;
use App\Repository\Storage\PageTrackingStorageRepository;
use App\Service\Routing\UrlMatcherService;
use App\Service\Security\JwtAuthenticationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Very small layer for anti ddos based on USER calls, in theory this should never be needed but with users it's never
 * known, they might be trying to break something, anyway getting a lot of requests from user is pretty suspicious
 */
class DdosUserMonitor
{
    /**
     * Logically speaking this class operates on "user level" so certain anonymous routes must be excluded
     */
    private const ANONYMOUS_EXCLUDED_ROUTES = [
        SystemGeoDataAction::ROUTE_NAME_GET_INTERNALLY_SUPPORTED_COUNTRIES,
        SecurityAction::ROUTE_NAME_GET_PASSWORD_CONSTRAINT,
        FrontendErrorStorageAction::ROUTE_NAME_INSERT_FRONTEND_ERROR,
        UserAction::ROUT_NAME_REGISTER_USER,
        UserAction::ROUTE_NAME_REQUEST_PASSWORD_RESET_LINK,
        UserAction::ROUTE_NAME_RESET_PASSWORD,
        UserAction::ROUTE_NAME_REQUEST_USER_ACTIVATION_LINK,
        UserAction::ROUTE_NAME_REQUEST_USER_REMOVAL_LINK,
        UserAction::ROUT_NAME_REMOVE_USER,
        UserAction::ROUTE_NAME_ACTIVATE_USER,
        SystemAction::ROUTE_NAME_SYSTEM_GET_CSRF_TOKEN,
    ];

    private const CHECKED_CALLS_MINUTES_OFFSET = 1;
    private const ABUSIVE_CALLS_LOWER_RANGE    = 500; // there are really A LOT of calls per refresh!
    private const BAN_DURATION                 = 5; // minutes

    private ?BannedUserStorage $lastUsedBan = null;

    public function __construct(
        private readonly LoggerInterface               $logger,
        private readonly PageTrackingStorageRepository $pageTrackingStorageRepository,
        private readonly EntityManagerInterface        $entityManager,
        private readonly JwtAuthenticationService      $jwtAuthenticationService,
        private readonly BannedUserStorageRepository   $bannedUserStorageRepository,
        private readonly UrlMatcherService             $urlMatcherService
    ){}

    /**
     * Check if there were some strange amounts of calls to given page recently
     * @return BannedUserStorage|null
     */
    public function isAbusiveCall(): ?BannedUserStorage {

        return null; // open-source

        // prevent accidentally banning dev system user
        if (Env::isDev()) {
            return null;
        }

        $foundRouteNameForUri = $this->urlMatcherService->getRouteForCalledUri(Request::createFromGlobals()->getRequestUri());

        if (
                str_starts_with((Request::createFromGlobals())->getRequestUri(), "/api")
            ||  in_array($foundRouteNameForUri, self::ANONYMOUS_EXCLUDED_ROUTES)
        ) {
            return null;
        }

        $user = $this->jwtAuthenticationService->getUserFromRequest();
        if (!empty($this->lastUsedBan)) {
            return $this->lastUsedBan;
        }

        $existingBanEntry = $this->bannedUserStorageRepository->findLatestValid($user, self::CHECKED_CALLS_MINUTES_OFFSET);
        if (!empty($existingBanEntry)) {
            $this->lastUsedBan = $existingBanEntry;
            return $existingBanEntry;
        }

        $callsCount = $this->pageTrackingStorageRepository->getRecentCallCountForUser(self::CHECKED_CALLS_MINUTES_OFFSET, $user);
        if ($callsCount > self::ABUSIVE_CALLS_LOWER_RANGE) {
            $validTill = (new DateTime())->modify("+" . self::BAN_DURATION . " MINUTES");
            $reason    = $this->buildBanReason($callsCount);

            $bannedUser = new BannedUserStorage();
            $bannedUser->setUser($user);
            $bannedUser->setValidTill($validTill);
            $bannedUser->setIssuedBy(self::class . "::" . __FUNCTION__);
            $bannedUser->setReason($reason);

            $this->entityManager->persist($bannedUser);
            $this->entityManager->flush();

            $this->logger->critical("Internal ddos monitor: banned user, excessive calls detected in last " . self::CHECKED_CALLS_MINUTES_OFFSET . " minutes", [
                "userId"     => $user->getId(),
                "callsCount" => $callsCount,
            ]);

            return $bannedUser;
        }

        return null;
    }

    /**
     * @param int $callsCount
     *
     * @return string
     */
    private function buildBanReason(int $callsCount): string
    {
        // must be aligned so badly, else the DB entry is malformed
        $message = "
Internal anti ddos protection. 
Excessive call detected. 
User excessive requests detected in number of: {$callsCount}.
Ban configurations:
- observed minutes offset: " . self::CHECKED_CALLS_MINUTES_OFFSET . "
- abusive calls lower range: " . self::ABUSIVE_CALLS_LOWER_RANGE;

        return trim($message);
    }

}