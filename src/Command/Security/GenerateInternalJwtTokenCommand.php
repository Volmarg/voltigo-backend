<?php

namespace App\Command\Security;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Repository\Security\UserRepository;
use App\Service\Security\JwtAuthenticationService;
use Exception;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Will generate the internal jwt token
 */
class GenerateInternalJwtTokenCommand extends AbstractCommand
{
    const COMMAND_NAME = "security:jwt:generate-internal-token";

    private const OPTION_USER_EMAIL = "user-email";

    private const OPTION_NON_EXPIRING = "non-expiring";

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->addOption(self::OPTION_USER_EMAIL, null, InputOption::VALUE_REQUIRED, "User-email for which token will be generated");
        $this->setDescription("Will generate the jwt token used to access the project");
        $this->addOption(self::OPTION_NON_EXPIRING, null, InputOption::VALUE_NONE, "This token will never expire, at least not in Your lifetime");
    }

    public function __construct(
        private readonly JwtAuthenticationService $jwtTokenService,
        private readonly UserRepository           $userRepository,
        ConfigLoader                              $configLoader,
        private readonly KernelInterface          $kernel
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
            $userEmail   = $this->input->getOption(self::OPTION_USER_EMAIL);
            if (empty($userEmail)) {
                $this->io->error("User email is missing");
                return self::INVALID;
            }

            $user = $this->userRepository->getOneByEmail($userEmail);
            if(empty($user)){
                $this->io->error("User for given email does not exist: {$userEmail}");
                return self::INVALID;
            }

            $token = $this->jwtTokenService->buildTokenForUser($user, [], $nonExpiring);

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