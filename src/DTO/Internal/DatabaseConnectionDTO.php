<?php

namespace App\DTO\Internal;

class DatabaseConnectionDTO
{

    const DEFAULT_DATABASE_PORT = 3302;

    /**
     * @var string $host
     */
    private string $host = "";

    /**
     * @var int $port
     */
    private int $port = self::DEFAULT_DATABASE_PORT;

    /**
     * @var string $user
     */
    private string $user = "";

    /**
     * @var string $password
     */
    private string $password = "";

    /**
     * @var string $databaseName
     */
    private string $databaseName = "";

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     */
    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getDatabaseName(): string
    {
        $strippedName = str_replace("/", "", $this->databaseName);
        return $strippedName;
    }

    /**
     * @param string $databaseName
     */
    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

}