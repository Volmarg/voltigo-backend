<?php

namespace App\Service\Email\Modifiers;

use App\Controller\Core\Env;
use App\Service\Api\BlacklistHub\BlacklistHubService;
use BlacklistHubBridge\Exception\BlacklistHubBridgeException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Add project footer to the E-Mail
 */
class ProjectFooterModifier implements EmailModifierInterface
{
    /**
     * @var string $recipient
     */
    private string $recipient;

    /**
     * @var bool $blockingUrl
     */
    private bool $blockingUrl = true;

    /**
     * @var string|null $fromAddress
     */
    private ?string $fromAddress = null;

    /**
     * @return bool
     */
    public function isBlockingUrl(): bool
    {
        return $this->blockingUrl;
    }

    /**
     * @param bool $blockingUrl
     */
    public function setBlockingUrl(bool $blockingUrl): void
    {
        $this->blockingUrl = $blockingUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * {@inheritDoc}
     */
    public function setRecipient(string $recipient): void
    {
        $this->recipient = $recipient;
    }

    /**
     * @return string|null
     */
    public function getFromAddress(): ?string
    {
        return $this->fromAddress;
    }

    /**
     * @param string|null $fromAddress
     */
    public function setFromAddress(?string $fromAddress): void
    {
        $this->fromAddress = $fromAddress;
    }

    public function __construct(
        private readonly Environment           $twig,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly BlacklistHubService   $blacklistHubService
    ) {}

    /**
     * {@inheritDoc}
     *
     * @param string $body
     *
     * @return string
     * @throws BlacklistHubBridgeException
     * @throws GuzzleException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function modify(string $body): string
    {
        $modifiedBody = $body;
        $addedContent = $this->getRenderedContent();
        $modifiedBody = str_replace("</body>", "{$addedContent}</body>", $modifiedBody);

        return $modifiedBody;
    }

    /**
     * Returns string of the content that will be inserted into E-Mail body
     *
     * @return string
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws BlacklistHubBridgeException
     * @throws GuzzleException
     */
    private function getRenderedContent(): string
    {
        $blockCompanyEmailUrl = null;
        $blockUserEmailUrl    = null;
        if ($this->isBlockingUrl()) {
            $blockCompanyEmailUrl = $this->blacklistHubService->getBlacklistingSingleEmailUrl($this->getRecipient());
            $blockUserEmailUrl    = $this->blacklistHubService->getBlacklistingSingleEmailUrl($this->getRecipient(), $this->getFromAddress());
        }

        $templateData = [
            'isBlockingUrl'        => $this->isBlockingUrl(),
            'projectName'          => $this->parameterBag->get('project_name'),
            'projectUrl'           => Env::getProjectLandingPageUrl(),
            'blockUserEmailUrl'    => $blockUserEmailUrl,
            'blockCompanyEmailUrl' => $blockCompanyEmailUrl,
            'randomIdentifier'     => uniqid(),
        ];

        $content = $this->twig->render("/mail/modifiers/project-footer/common.twig", $templateData);

        return $content;
    }
}