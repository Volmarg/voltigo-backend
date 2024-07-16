<?php

namespace App\Service\Shell;

use App\Controller\Core\Env;
use App\Service\Logger\LoggerService;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Main / Common logic of the shell executable logic
 *
 * Class ShellAbstractService
 */
abstract class ShellAbstractService
{
    /**
     * Some apps called in CLI don't know about the target resolution etc,
     * This thing called in front of any other callable allows pretending monitor of given screen size,
     *
     * Info:
     * --auto-servernum: {@link https://stackoverflow.com/questions/16726227/xvfb-failed-start-error}
     */
    private const XVFB_WRAPPER_COMMAND = "xvfb-run --auto-servernum --server-args='-screen 0, 1920x1080x16'";

    /**
     * @var LoggerService $loggerService
     */
    protected LoggerService $loggerService;

    /**
     * @var ContainerInterface $container
     */
    private ContainerInterface $container;

    /**
     * @param LoggerService $loggerService
     * @param ContainerInterface $container
     */
    public function __construct(LoggerService $loggerService, ContainerInterface $container)
    {
        $this->loggerService = $loggerService;
        $this->container     =  $container;
    }

    /**
     * Will retrieve executable binary used in child class
     */
    abstract protected function getExecutableBinaryName(): string;

    /**
     * Will return information if executable is present (calls `which`).
     *
     * @return bool
     * @throws Exception
     */
    protected function isExecutableForServicePresent(): bool
    {
        $binaryName = $this->getExecutableBinaryName();
        return $this->isExecutablePresent($binaryName);
    }

    /**
     * Will take the partials and attach each one of them to the executable binary like this:
     *  - assuming executable: "Mysql"
     *  - partials: [1, --2=test, 3]
     *
     * Will result in:
     * - "Mysql 1 --2=test 3
     *
     * @param array<string|int> $partials
     * @param bool              $addSpaceBarPerPartial
     * @param int               $timeout
     * @param bool              $useXvfbWrapper
     *
     * @return string
     */
    protected function buildCommand(
        array $partials = [],
        bool  $addSpaceBarPerPartial = true,
        int   $timeout = 0,
        bool  $useXvfbWrapper = false
    ): string
    {
        $gluedCommand = "";
        if ($timeout > 0) {
            $gluedCommand .= "timeout {$timeout} ";
        }

        if ($useXvfbWrapper) {
            $gluedCommand .= self::XVFB_WRAPPER_COMMAND . " ";
        }

        $gluedCommand .= $this->getExecutableBinaryName();
        foreach($partials as $partial){

            if($addSpaceBarPerPartial){
                $gluedCommand .= " " . $partial;
                continue;
            }

            $gluedCommand .= $partial;
        }

        return $gluedCommand;
    }

    /**
     * Will check if executable is present
     *
     * @param string $executableName
     * @return bool
     * @throws Exception
     */
    protected function isExecutablePresent(string $executableName): bool
    {
        $executableFinder = new ExecutableFinder();
        $executablePath   = $executableFinder->find($executableName);

        if( is_null($executablePath) ){
            $this->loggerService->critical("Searched executable is not present for shell: {$executableName}");
            return false;
        }

        return true;
    }

    /**
     * Execute shell command and return the process object
     *
     * @param string         $calledCommand
     * @param int|float|null $timeout
     *
     * @return Process
     */
    protected function executeShellCommand(string $calledCommand, null|int|float $timeout = null): Process
    {
        $process = Process::fromShellCommandline(trim($calledCommand));

        if (!empty($timeout)) {
            $process->setTimeout((float)$timeout);
        }

        $process->run();

        if( !$process->isSuccessful() ){
            $loggedCommand = $calledCommand;
            if(
                    $this instanceof ShellMysqlDumpService
                &&  Env::isProd()
            ){
                $loggedCommand = "Command has been removed from log due to security policy";
            }

            $this->loggerService->critical("Process was finished but WITH NO SUCCESS", [
                "calledCommand"      => $loggedCommand,
                "commandOutput"      => $process->getOutput(),
                "commandErrorOutput" => $process->getErrorOutput(),
            ]);
        }

        return $process;
    }

}