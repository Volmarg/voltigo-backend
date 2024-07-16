<?php

namespace App\Service\Logger;

use App\Exception\LogicFlow\UnsupportedDataProvidedException;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Handles logging logic
 *
 * Information: Adding new logger.
 * - add constant with which will represent the logger,
 * - create property and DI with variable name starting with EXACT SAME name as set above,
 * - add new logger and channel in: `/config/packages/monolog.yaml`
 *
 * Class LoggerService
 * @package App\Service\Logger
 */
class LoggerService
{
    /**
     * If necessary - expand the list, and add some logic to pick over the used Logger
     */
    const LOGGER_HANDLER_DEFAULT                      = "default";
    const LOGGER_HANDLER_SECURITY                     = "security";
    const LOGGER_HANDLER_NOTIFIER_PROXY_LOGGER_BRIDGE = "notifierProxyLoggerBridge";
    const LOGGER_HANDLER_WEBSOCKET                    = "websocket";

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @var LoggerInterface $defaultLogger
     */
    private LoggerInterface $defaultLogger;

    /**
     * @var LoggerInterface $securityLogger
     */
    private LoggerInterface $securityLogger;

    /**
     * @var LoggerInterface $notifierProxyLoggerBridgeLogger
     */
    private LoggerInterface $notifierProxyLoggerBridgeLogger;

    /**
     * @var LoggerInterface $websocketLogger
     */
    private LoggerInterface $websocketLogger;

    /**
     * LoggerService constructor.
     *
     * @param LoggerInterface $defaultLogger
     * @param LoggerInterface $securityLogger
     * @param LoggerInterface $notifierProxyLoggerBridgeLogger
     * @param LoggerInterface $websocketLogger
     */
    public function __construct(
        LoggerInterface $defaultLogger,
        LoggerInterface $securityLogger,
        LoggerInterface $notifierProxyLoggerBridgeLogger,
        LoggerInterface $websocketLogger
    )
    {
         $this->defaultLogger                   = $defaultLogger;
         $this->securityLogger                  = $securityLogger;
         $this->websocketLogger                 = $websocketLogger;
         $this->notifierProxyLoggerBridgeLogger = $notifierProxyLoggerBridgeLogger;
    }

    /**
     * Will log the exception
     *
     * @param Throwable $e
     * @param mixed[] $additionalData
     * @param int $level
     */
    public function logException(Throwable $e, array $additionalData = [], int $level = Logger::CRITICAL): void
    {
        $this->log($level,"Exception was thrown", [
            "exceptionClass"   => get_class($e),
            "exceptionMessage" => $e->getMessage(),
            "exceptionCode"    => $e->getCode(),
            "exceptionTrace"   => $e->getTrace(),
            "additionalData"   => $additionalData,
        ]);
    }

    /**
     * Will return the logger service that is currently being used as logger
     *
     * @param string $loggerHandler
     * @return LoggerService
     * @throws UnsupportedDataProvidedException
     */
    public function setLoggerService(string $loggerHandler = self::LOGGER_HANDLER_DEFAULT): self
    {
        switch($loggerHandler){
            case self::LOGGER_HANDLER_DEFAULT:
            {
                $this->logger = $this->defaultLogger;
            }
            break;

            case self::LOGGER_HANDLER_SECURITY:
            {
                $this->logger = $this->securityLogger;
            }
            break;

            case self::LOGGER_HANDLER_NOTIFIER_PROXY_LOGGER_BRIDGE:
            {
                $this->logger = $this->notifierProxyLoggerBridgeLogger;
            }
            break;

            case self::LOGGER_HANDLER_WEBSOCKET:
            {
                $this->logger = $this->websocketLogger;
            }
            break;

            default:
                throw new UnsupportedDataProvidedException("This logger handler is not supporter: {$loggerHandler}");

        }

        return $this;
    }

    /**
     * @param string $message
     * @param mixed[] $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * @param string $message
     * @param mixed[] $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * @param string $message
     * @param mixed[] $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * @param string $message
     * @param mixed[] $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * @param string $message
     * @param mixed[] $context
     */
    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * @param string $message
     * @param mixed[] $context
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * @param int $level
     * @param string $message
     * @param mixed[] $context
     */
    public function log(int $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

}