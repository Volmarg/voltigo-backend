<?php

namespace App\Command\Validation;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Controller\Core\Services;
use Exception;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Helper command to validate jwt tokens - useful to ensure that tokens are generated properly
 *
 * Class ValidateJwtTokenCommand
 * @package App\Command\Validation
 */
class ValidateJwtTokenCommand extends AbstractCommand
{
    const COMMAND_NAME = "validation:jwt-token";

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Will validate the jwt token.");
    }

    /**
     * @var Services $services
     */
    private Services $services;

    public function __construct(
        Services                         $services,
        ConfigLoader                     $configLoader,
        private readonly KernelInterface $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
        $this->services = $services;
    }

    /**
     * Execute command logic
     *
     * @return int
     */
    protected function executeLogic(): int
    {
        $token = $this->io->ask("Jwt token: ");

        try{
            $isTokenValid = $this->services->getJwtAuthenticationService()->isTokenValid($token);
            $this->io->info("Is token valid: " . (int) $isTokenValid);

        }catch(Exception | TypeError $e){
            $this->io->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}