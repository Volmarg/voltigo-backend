<?php

namespace App\Command\Validation;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Service\Security\CsrfTokenService;
use Doctrine\ORM\ORMException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Helper command to validate csrf tokens - useful to ensure that tokens are generated properly
 *
 * Class ValidateCsrfTokenCommand
 * @package App\Command\Validation
 */
class ValidateCsrfTokenCommand extends AbstractCommand
{
    const COMMAND_NAME = "validation:csrf-token";

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Will validate the csrf token. Keep it mind that it will be invalidated once validations is handled");
    }

    /**
     * @var CsrfTokenService $csrfTokenService
     */
    private CsrfTokenService $csrfTokenService;

    public function __construct(
        CsrfTokenService                 $csrfTokenService,
        ConfigLoader                     $configLoader,
        private readonly KernelInterface $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
        $this->csrfTokenService = $csrfTokenService;
    }

    /**
     * Execute the command logic
     *
     * @return int
     * @throws ORMException
     */
    protected function executeLogic(): int
    {
        $id   = $this->io->ask("Id used to create csrf token: ");
        $csrf = $this->io->ask("Csrf token to validate: ");

        $isTokenValid = $this->csrfTokenService->isCsrfTokenValid($id, $csrf);

        $this->io->info("Is token valid: " . (int) $isTokenValid);

        return self::SUCCESS;
    }

}