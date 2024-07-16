<?php

namespace App\Command\Security;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Controller\Core\Services;
use Exception;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Helper command to extract the jwt token payload
 *
 * Class ExtractJwtTokenPayloadCommand
 */
class ExtractJwtTokenPayloadCommand extends AbstractCommand
{
    const COMMAND_NAME                   = "security:extract-jwt-token-payload";
    private const MAX_PAYLOAD_CHUNK_SIZE = 7;

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Will extract jwt token payload");
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
            if(!$isTokenValid){
                $this->io->error("This is not a valid JWT token!");
                return self::FAILURE;
            }

            $payload = $this->services->getJwtAuthenticationService()->getPayloadFromToken($token);
            $chunks  = array_chunk($payload, self::MAX_PAYLOAD_CHUNK_SIZE, true);
            foreach ($chunks as $payloadChunk) {
                $this->showTableForChunk($payloadChunk);
            }

        }catch(Exception | TypeError $e){
            $this->io->error($e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Splits the payload result into multiple tables because the more data there is
     * the harder it gets to read the cli table
     *
     * @param array $payloadChunk
     *
     * @return void
     */
    private function showTableForChunk(array $payloadChunk): void
    {
        $tableHeaders = array_keys($payloadChunk);
        $tableRows    = [];
        foreach($payloadChunk as $key => $value){

            if (is_scalar($value)) {
                $tableRows[] = $value;
            } else {
                $encodedValue = json_encode($value);
                $tableRows[]  = $encodedValue;
            }

        }

        $this->io->table($tableHeaders, [$tableRows]);
    }

}