<?php

namespace App\Command\Security;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Repository\Api\ApiUserRepository;
use App\Service\Api\Jwt\UserJwtTokenService;
use Exception;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Will generate the jwt token for api calls
 */
class GenerateApiJwtTokenCommand extends AbstractCommand
{
    const COMMAND_NAME = "security:jwt:generate-api-token";

    private const OPTION_USER_NAME = "user-name";

    private const OPTION_NON_EXPIRING = "non-expiring";

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->addOption(self::OPTION_USER_NAME, null, InputOption::VALUE_REQUIRED, "User-name for which token will be generated");
        $this->setDescription("Will generate the jwt token used to access the project API");
        $this->addOption(self::OPTION_NON_EXPIRING, null, InputOption::VALUE_NONE, "This token will never expire, at least not in Your lifetime");
    }

    public function __construct(
        private readonly UserJwtTokenService $jwtTokenService,
        private readonly ApiUserRepository   $apiUserRepository,
        ConfigLoader                         $configLoader,
        private readonly KernelInterface     $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
    }

    /**
     * Execute command logic
     *
     * @return int
     */
    protected function executeLogic(): int
    {
        try {
            $nonExpiring = $this->input->hasOption(self::OPTION_NON_EXPIRING);
            $userName    = $this->input->getOption(self::OPTION_USER_NAME);
            if (empty($userName)) {
                $this->io->error("User name is missing");
                return self::INVALID;
            }

            $user = $this->apiUserRepository->findOneByName($userName);
            if(empty($user)){
                $this->io->error("User with given name does not exist: {$userName}");
                return self::INVALID;
            }

            $token = $this->jwtTokenService->generate($user, $nonExpiring);

            $this->io->info("Jwt token");
            $this->io->text($token);
            $this->io->newLine(2);
        }catch(Exception | TypeError $e){
            $this->io->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

}