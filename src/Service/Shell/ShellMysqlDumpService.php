<?php

namespace App\Service\Shell;

use App\Controller\Core\Env;
use Exception;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

/**
 * Handles shell calls to mysql
 *
 * Class ShellPhpService
 * @package App\Services\Shell
 */
class ShellMysqlDumpService extends ShellAbstractService
{
    const EXECUTABLE_BINARY_NAME = "mysqldump";

    /**
     * Database username
     */
    const PARAM_USER = "-u";

    /**
     * Database password
     * - password must be glued with "p" like this "-pPaSsWoRd"
     */
    const PARAM_PASSWORD = "-p";

    /**
     * Will return executable php binary name
     * @Return string
     */
    protected function getExecutableBinaryName(): string
    {
        return self::EXECUTABLE_BINARY_NAME;
    }

    /**
     * Will dump project database to given location
     *
     * @param string $dumpFilePath
     *
     * @return Process
     * @throws Exception
     */
    public function dumpDatabase(string $dumpFilePath): Process
    {
        $databaseConnectionDto = Env::getDatabaseConnectionCredentials();
        if( !$this->isExecutableForServicePresent() ){
            throw new Exception("Executable is not present: {$this->getExecutableBinaryName()}");
        }

        $command = $this->buildCommand([
            self::PARAM_USER,
            $databaseConnectionDto->getUser(),
            self::PARAM_PASSWORD . $databaseConnectionDto->getPassword(), // must be glued together, see "man mysqldump"
            $databaseConnectionDto->getDatabaseName(),
            ">",
            $dumpFilePath
        ]);

        $process = $this->executeShellCommand($command);
        return $process;
    }

}