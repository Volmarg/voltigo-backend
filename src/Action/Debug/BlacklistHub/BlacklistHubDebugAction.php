<?php

namespace App\Action\Debug\BlacklistHub;

use App\Service\Api\BlacklistHub\BlacklistHubService;
use App\Service\ConfigLoader\ConfigLoaderProject;
use BlacklistHubBridge\Dto\Email\EmailBlacklistSearchDto;
use BlacklistHubBridge\Exception\BlacklistHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;
use symfony\Component\Routing\Annotation\Route;

/**
 * Debugging the blacklist hub calls {@see BlacklistHubService}
 */
class BlacklistHubDebugAction
{
    public function __construct(
        private readonly BlacklistHubService $blacklistHubService,
        private readonly ConfigLoaderProject $configLoaderProject
    ){}

    /**
     * @throws GuzzleException
     * @throws BlacklistHubBridgeException
     */
    #[Route("/debug/blacklist-hub/check-email-blacklist-status/{emailAddress}", name: "debug.blacklist.hub.status", methods: [Request::METHOD_GET])]
    public function getEmailStatuses(string $emailAddress): never
    {
        $emailBlacklistSearch  = new EmailBlacklistSearchDto($emailAddress, $this->configLoaderProject->getFromMail());
        $emailBlacklistSearch2 = new EmailBlacklistSearchDto('test@test.pl');
        $emailBlacklistSearch3 = new EmailBlacklistSearchDto('johnd@ardekay.de', "uuu@tlen.pl");
        $response              = $this->blacklistHubService->getEmailsStatuses([$emailBlacklistSearch, $emailBlacklistSearch2, $emailBlacklistSearch3]);

        die();
    }

    /**
     * @throws GuzzleException
     * @throws BlacklistHubBridgeException
     */
    #[Route("/debug/blacklist-hub/get-blacklisting-url/{recipient}/{fromAddress?}", name: "debug.blacklist.hub.status", methods: [Request::METHOD_GET])]
    public function getBlacklistingUrl(string $recipient, ?string $fromAddress = null): never
    {
        $url = $this->blacklistHubService->getBlacklistingSingleEmailUrl($recipient, $fromAddress);
        die($url);
    }
}