<?php

namespace App\Service\Shell;

/**
 * Consist of shell based sub-services
 */
class ShellService
{
    /**
     * @var ShellMysqlDumpService $shellMysqlDumpService
     */
    private ShellMysqlDumpService $shellMysqlDumpService;

    /**
     * @var ShellTarArchivizerService $shellTarArchivizerService
     */
    private ShellTarArchivizerService $shellTarArchivizerService;

    /**
     * @return ShellMysqlDumpService
     */
    public function getShellMysqlDumpService(): ShellMysqlDumpService
    {
        return $this->shellMysqlDumpService;
    }

    /**
     * @param ShellMysqlDumpService $shellMysqlDumpService
     */
    public function setShellMysqlDumpService(ShellMysqlDumpService $shellMysqlDumpService): void
    {
        $this->shellMysqlDumpService = $shellMysqlDumpService;
    }

    /**
     * @return ShellTarArchivizerService
     */
    public function getShellTarArchivizerService(): ShellTarArchivizerService
    {
        return $this->shellTarArchivizerService;
    }

    /**
     * @param ShellTarArchivizerService $shellTarArchivizerService
     */
    public function setShellTarArchivizerService(ShellTarArchivizerService $shellTarArchivizerService): void
    {
        $this->shellTarArchivizerService = $shellTarArchivizerService;
    }

}