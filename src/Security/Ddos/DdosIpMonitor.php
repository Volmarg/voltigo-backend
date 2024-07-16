<?php

namespace App\Security\Ddos;

use App\Controller\Core\Env;
use App\Entity\Storage\Ban\BannedIpStorage;
use App\Repository\Storage\Ban\BannedIpStorageRepository;
use App\Repository\Storage\PageTrackingStorageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Very small layer for anti ddos based on IP calls, it might not be enough at first but with this data gathered,
 * it might be easier to then read it via some script on linux for permanently blocking access to server,
 */
class DdosIpMonitor
{
    private const CHECKED_CALLS_MINUTES_OFFSET = 1;
    private const ABUSIVE_CALLS_LOWER_RANGE    = 250;

    private ?BannedIpStorage $lastUsedBan = null;

    public function __construct(
        private readonly LoggerInterface               $logger,
        private readonly PageTrackingStorageRepository $pageTrackingStorageRepository,
        private readonly EntityManagerInterface        $entityManager,
        private readonly BannedIpStorageRepository     $bannedIpStorageRepository
    ){}

    /**
     * Check if there were some strange amounts of calls to given page recently
     * @return BannedIpStorage|null
     */
    public function isAbusiveCall(): ?BannedIpStorage {

        return null; // open-source

        #prevent accidentally banning dev system user
        if (Env::isDev()) {
            return null;
        }

        if (!empty($this->lastUsedBan)) {
            return $this->lastUsedBan;
        }

        $request          = Request::createFromGlobals();
        $existingBanEntry = $this->bannedIpStorageRepository->findLatestValid($request->getClientIp(), self::CHECKED_CALLS_MINUTES_OFFSET);
        if (!empty($existingBanEntry)) {
            $this->lastUsedBan = $existingBanEntry;
            return $existingBanEntry;
        }

        $callsCount = $this->pageTrackingStorageRepository->getRecentCallCountForIp(self::CHECKED_CALLS_MINUTES_OFFSET, $request->getClientIp());
        if ($callsCount > self::ABUSIVE_CALLS_LOWER_RANGE) {
            $reason = $this->buildBanReason($callsCount);

            $bannedIp = new BannedIpStorage();
            $bannedIp->setIp($request->getClientIp());
            $bannedIp->makeLifetime();
            $bannedIp->setIssuedBy(self::class . "::" . __FUNCTION__);
            $bannedIp->setReason($reason);

            $this->entityManager->persist($bannedIp);
            $this->entityManager->flush();

            $this->logger->critical("Internal ddos monitor: banned an ip, excessive calls detected in last " . self::CHECKED_CALLS_MINUTES_OFFSET . " minutes", [
                "ip"         => $bannedIp,
                "callsCount" => $callsCount,
            ]);

            $this->lastUsedBan = $bannedIp;
            return $bannedIp;
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
Ip got requested: {$callsCount} times.
Ban configurations:
- observed minutes offset: " . self::CHECKED_CALLS_MINUTES_OFFSET . "
- abusive calls lower range: " . self::ABUSIVE_CALLS_LOWER_RANGE;

        return trim($message);
    }

}