<?php

namespace App\Command;

use App\Controller\Core\ConfigLoader;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Contains common logic for project related commands
 */
abstract class AbstractCommand extends Command
{
    use LockableTrait;

    // makes not much sense but phpstan cries about it
    const COMMAND_NAME = "abstract-command";

    /**
     * Will execute the main logic of command
     *
     * @return int
     */
    protected abstract function executeLogic(): int;

    /**
     * Set the command configuration
     */
    protected abstract function setConfiguration(): void;

    /**
     * Call some additional logic before the actual configuration
     */
    protected function beforeConfiguration(): void
    {
        // can be overwritten in child
    }

    /**
     * Call some extra logic when the parent class (main) initialisation is done
     */
    protected function afterMainInitialization(): void
    {}

    /**
     * @var SymfonyStyle $io
     */
    protected SymfonyStyle $io;

    /**
     * @var ConfigLoader $configLoader
     */
    protected ConfigLoader $configLoader;

    /**
     * @var OutputInterface $output
     */
    protected OutputInterface $output;

    /**
     * @var InputInterface $input
     */
    protected InputInterface $input;

    /**
     * @var LoggerInterface $logger
     */
    protected LoggerInterface $logger;

    public function __construct(
        ConfigLoader    $configLoader,
        KernelInterface $kernel
    )
    {
        $this->logger       = $kernel->getContainer()->get('public_logger');
        $this->configLoader = $configLoader;
        parent::__construct();
    }

    /**
     * Set configuration
     * @throws Exception
     */
    protected function configure(): void
    {
        $fullCommandName = $this->buildCommandName();
        $this->setName($fullCommandName);

        $this->beforeConfiguration();
        $this->setConfiguration();
    }

    /**
     * Initialize the command logic
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->input  = $input;
        $this->output = $output;
        $this->io     = new SymfonyStyle($input, $output);

        $this->afterMainInitialization();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->info("Started handling: " . static::class);
        if (!$this->lock($this->buildCommandName())) {
            $this->io->info("This command is already running: {$this->buildCommandName()}");

            return self::SUCCESS;
        }

        try{
            $this->input  = $input;
            $this->output = $output;
            $status       = $this->executeLogic();

            if ($status != self::SUCCESS) {
                $this->logger->critical("Command: " . static::class . " exited with status `{$status}`", [
                    "failStatuses" => [
                        self::FAILURE,
                        self::INVALID,
                    ]
                ]);
            }

        }catch(Exception | TypeError $e){
            $msg = "Failed executing command: " . $this->getName();
            $this->io->error($msg);
            $this->io->error($e->getMessage());

            $this->logger->critical("Exception while executing command: " . static::class, [
                "exceptions" => [
                    'class'   => $e::class,
                    'message' => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ]
            ]);

            $status = self::FAILURE;
        }

        $this->io->info("Finished handling: " . static::class);
        $this->release();

        return $status;
    }

    /**
     * @throws Exception
     */
    private function buildCommandName(): string
    {
        $commandPrefix   = $this->normalizedProjectNameForCommandName();
        $fullCommandName = $commandPrefix . ":" . static::COMMAND_NAME;

        return $fullCommandName;
    }

    /**
     * Will return the project name in the normalized name usable for command name
     * - no spaces etc.
     *
     * @return string
     * @throws Exception
     */
    private function normalizedProjectNameForCommandName(): string
    {
        $trimmedProjectName = $this->configLoader->getConfigLoaderProject()->getProjectName();

        if( empty($trimmedProjectName) ){
            throw new Exception("Project name is not set!");
        }

        $namePartials = explode(" ", $trimmedProjectName);

        $normalizedProjectName = "";
        foreach($namePartials as $index => $namePartial){
            $formattedNamePartial   = ( $index < 1 ? strtolower($namePartial) : ucfirst($namePartial) );
            $normalizedProjectName .= $formattedNamePartial;
        }

        return $normalizedProjectName;
    }

}