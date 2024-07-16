<?php

namespace App\Command\Backup;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Controller\Core\Services;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Process\Process;

/**
 * Will create database backup
 *
 * Class CleanupStoragesCommand
 */
abstract class BaseBackupCommand extends AbstractCommand
{
    const OPTION_TARGET_DUMP_FOLDER_PATH       = "targetDumpFolderPath";
    const OPTION_TARGET_DUMP_FOLDER_PATH_SHORT = "dumpPath";

    /**
     * Folder path in which backup should be stored
     *
     * @var ?string $targetDumpFolderPath
     */
    protected ?string $targetDumpFolderPath = "";

    /**
     * @var Services $services
     */
    protected Services $services;

    /**
     * @var ParameterBagInterface $parameterBag
     */
    protected ParameterBagInterface $parameterBag;

    /**
     * @param Services              $services
     * @param ConfigLoader          $configLoader
     * @param ParameterBagInterface $parameterBag
     * @param KernelInterface       $kernel
     */
    public function __construct(
        Services                         $services,
        ConfigLoader                     $configLoader,
        ParameterBagInterface            $parameterBag,
        private readonly KernelInterface $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
        $this->services     = $services;
        $this->parameterBag = $parameterBag;
    }

    /**
     * {@inheritDoc}
     */
    protected function beforeConfiguration(): void
    {
        $this->addOption(
            self::OPTION_TARGET_DUMP_FOLDER_PATH,
            self:: OPTION_TARGET_DUMP_FOLDER_PATH_SHORT,
            InputOption::VALUE_OPTIONAL,
            "Target folder inside which should the backup be created",
        );

        $this->addUsage("--" . self::OPTION_TARGET_DUMP_FOLDER_PATH ."=/folder/path");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $this->targetDumpFolderPath = $input->getOption(self::OPTION_TARGET_DUMP_FOLDER_PATH);
    }

    /**
     * Will check if target folder exists and has all the privileges to write data
     * - attempts to create folder if such does not exist
     *
     * @throws Exception
     * @return string - folder path that will be used (if none is provided via cli then default one will be taken)
     */
    protected function handleTargetFolder(string $targetDumpFolder): string
    {
        if( !empty($this->targetDumpFolderPath) ){
            $targetDumpFolder = $this->targetDumpFolderPath;
        }

        if( !file_exists($targetDumpFolder) ){
            $isSuccess = mkdir($targetDumpFolder, 0777, true);
            if(!$isSuccess){
                throw new Exception("Could not create the folder: {$targetDumpFolder}");
            }

        }else{
            if( !is_writable($targetDumpFolder) ){
                throw new Exception("Folder is not writeable: {$targetDumpFolder}");
            }
        }

        return $targetDumpFolder;
    }

    /**
     * Will handle building tar archive for files and earlier process if such was called
     *
     * @param array $filesPaths
     * @param string $outputFileName
     * @param string $backupDirectoryPath
     * @param ?Process<PhpProcess> $process
     * @throws Exception
     */
    protected function checkBackupProcessAndCreateArchive(
        array    $filesPaths,
        string   $outputFileName,
        string   $backupDirectoryPath,
        ?Process $process = null
    ): void
    {
        if(
                !is_null($process)
            &&  !$process->isSuccessful()
        ){
            $message = "Process exited with FAILURE";
            $this->services->getLoggerService()->critical($message, [
                "calledCommand" => $process->getCommandLine(),
            ]);
            throw new Exception($message);
        }

        $tarArchivePath = $this->services->getShellService()->getShellTarArchivizerService()->buildTarArchiveForTargetFileS(
            $filesPaths,
            $outputFileName,
            $backupDirectoryPath
        );

        if( !file_exists($tarArchivePath) ){
            throw new Exception("Tar archive does not exist: {$tarArchivePath}");
        }
    }
}