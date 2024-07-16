<?php

namespace App\Command\Cleanup;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Entity\Storage\CsrfTokenStorage;
use App\Entity\Storage\FrontendErrorStorage;
use App\Entity\Storage\PageTrackingStorage;
use App\Service\Cleanup\CleanupServiceInterface;
use App\Service\Logger\LoggerService;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Will cleanup the storages:
 *
 * - @see CsrfTokenStorage
 * - @see FrontendErrorStorage
 * - @see PageTrackingStorage
 *
 * Class CleanupStoragesCommand
 */
class CleanupStoragesCommand extends AbstractCommand
{
    const COMMAND_NAME = "cleanup:storages";

    private const MODE_ALL    = "ALL";
    private const MODE_SINGLE = "SINGLE";

    private const OPTION_MODE               = "mode";
    private const OPTION_SINGLE_SERVICE_ID = "single-cleanup-service-id";

    // these are defined in services.yml
    private const AVAILABLE_SERVICES = [
        'cleanup.service.aqmp',
        'cleanup.service.api',
        'cleanup.upload',
        'cleanup.service.banned_jwt',
        'cleanup.service.csrf_token',
        'cleanup.service.frontend_error',
        'cleanup.service.one_time_jwt',
        'cleanup.service.page_tracking',
        'cleanup.service.offers_search',
        'cleanup.service.offer_information',
        'cleanup.service.email_attachment',
        'cleanup.service.user'
    ];

    private const ALLOWED_MODES = [
      self::MODE_SINGLE,
      self::MODE_ALL,
    ];

    /**
     * @var string|null
     */
    private ?string $mode;

    /**
     * @var string|null
     */
    private ?string $serviceId;

    /**
     * @var CleanupServiceInterface[]
     */
    private array $storageCleanupServices = [];

    /**
     * @return CleanupServiceInterface[]
     */
    public function getStorageCleanupServices(): array
    {
        return $this->storageCleanupServices;
    }

    /**
     * @param CleanupServiceInterface[] $storageCleanupServices
     */
    public function setStorageCleanupServices(array $storageCleanupServices): void
    {
        $this->storageCleanupServices = $storageCleanupServices;
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    protected function afterMainInitialization(): void
    {
        $this->mode = $this->input->getOption(self::OPTION_MODE);
        if (empty($this->mode)) {
            throw new Exception("Mode was not provided");
        }

        if (!in_array($this->mode, self::ALLOWED_MODES)) {
            throw new Exception("This mode is not supported: {$this->mode}");
        }

        $this->serviceId = $this->input->getOption(self::OPTION_SINGLE_SERVICE_ID);

        if(
                $this->mode == self::MODE_SINGLE
            &&  empty($this->serviceId)
        ) {
            throw new Exception("Service id is required when using mode: " . self::OPTION_SINGLE_SERVICE_ID);
        }
    }

    /**
     * Set configuration
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("Will cleanup expired data from storages tables");
        $this->addOption(self::OPTION_MODE, null, InputOption::VALUE_REQUIRED, "Defines the cleanup mode, either all storages are executed or just one");
        $this->addOption(self::OPTION_SINGLE_SERVICE_ID, null, InputOption::VALUE_OPTIONAL, "Cleanup service id to be called when using mode: " . self::MODE_SINGLE);

        $serviceIdsJson = json_encode(self::AVAILABLE_SERVICES, JSON_PRETTY_PRINT);
        $this->setHelp("
- Available serviceIds: {$serviceIdsJson},
- Mode `" . self::MODE_ALL . "` will trigger all cleanups
- Mode `" . self::MODE_SINGLE. "` will trigger only one provided serviceId (which is required if that mode is used) 
        ");
    }

    /**
     * @param ConfigLoader       $configLoader
     * @param LoggerService      $loggerService
     * @param ContainerInterface $container
     * @param KernelInterface    $kernel
     */
    public function __construct(
        ConfigLoader                        $configLoader,
        private readonly LoggerService      $loggerService,
        private readonly ContainerInterface $container,
        private readonly KernelInterface    $kernel
    )
    {
        parent::__construct($configLoader, $kernel);
    }

    /**
     * Execute the command logic
     *
     * @return int
     */
    protected function executeLogic(): int
    {
        $this->io->info("Started removing old entries from storages");
        {
            try{

                match ($this->mode) {
                    self::MODE_ALL    => $this->cleanAll(),
                    self::MODE_SINGLE => $this->cleanSingle(),
                    default        => throw new \LogicException("Got non supported mode for match case {$this->mode}"),
                };

            }catch(Exception | TypeError $e){
                $this->loggerService->logException($e);
                $this->io->error("Exception was thrown while trying to remove storage entries");
                return self::FAILURE;
            }
        }

        $this->io->success("Done removing old entries from storages");
        return self::SUCCESS;
    }

    /**
     * Handles cleanup for all the storages
     * @return void
     */
    private function cleanAll(): void
    {
        $countAllRemovedEntries = 0;
        foreach($this->getStorageCleanupServices() as $cleanupService){
            $this->io->info("Now removing entries for: " . $cleanupService::class);

            try {
                $countRemovedEntries     = $cleanupService->cleanUp();
                $countAllRemovedEntries += $countRemovedEntries;
                $this->io->info("Removed: {$countRemovedEntries} entry/ies");

            } catch (Exception|TypeError $e) {
                $this->loggerService->logException($e);
                $this->io->warning("Failed removal for service: " . $cleanupService::class);
                continue; // try with next cleanup
            }

        }

        $this->io->info("Count of totally removed entries: {$countAllRemovedEntries}");
    }

    /**
     * Handles cleaning up single storage
     */
    private function cleanSingle(): void
    {
        try {
            /**
             * @var CleanupServiceInterface $cleanupService
             */
            $cleanupService = $this->container->get($this->serviceId);

            $this->io->info("Now removing entries for: " . $cleanupService::class);
            $countRemovedEntries = $cleanupService->cleanUp();

            $this->io->info("Removed: {$countRemovedEntries} entry/ies");

        } catch (Exception|TypeError $e) {
            $this->loggerService->logException($e);
            return;
        }
    }
}