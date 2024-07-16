<?php

namespace App\Service\Security\Scanner;

use App\Exception\Security\MaliciousFileException;
use Appwrite\ClamAV\Network;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use TypeError;

/**
 * Relies on ClamAv:
 * - {@link https://www.tecmint.com/scan-linux-for-malware-and-rootkits/}
 * - {@link https://github.com/appwrite/php-clamav}
 *
 * Testing the scanner:
 * - {@link https://en.wikipedia.org/wiki/EICAR_test_file}
 * - {@link https://www.eicar.org/download-anti-malware-testfile/}
 *
 * The log path can be found in:
 * - clamd.conf
 * -- Key: LogFile
 */
class FileScannerService
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly LoggerInterface       $logger
    ){}

    /**
     * Will scan the file.
     * Returns bool
     * - true (file is safe),
     * - false (file is NOT safe)
     *
     * @param string $filePath
     *
     * @return bool
     * @throws Exception
     */
    public function scan(string $filePath): bool
    {
        $host = $this->parameterBag->get('clam_av.host');
        $port = $this->parameterBag->get('clam_av.port');

        $connectionErrorMessage = "Clam av could not be reached. Is it installed? Is correct host / port used? Is it properly configured?";

        try {
            $clam = new Network($host, $port);
            if (!$clam->ping()) {
                throw new Exception($connectionErrorMessage);
            }
        } catch (Exception|TypeError $e) {
            $message = $connectionErrorMessage . " | Original message: " . $e->getMessage();
            throw new Exception($message);
        }

        $isValid = $clam->fileScan($filePath);

        /**
         * This is left here as info!
         * NEVER EVER call the `shutdown` method as it will kill the `daemon` service in shell without restarting it!
         */
        # $clam->shutdown();

        if (!$isValid) {
            $isRemoved = unlink($filePath);
            if (!$isRemoved) {
                $this->logger->emergency("Could not remove infected file {$filePath}. Do something with it!");
            }
            throw new MaliciousFileException($filePath);
        }

        return true;
    }
}
