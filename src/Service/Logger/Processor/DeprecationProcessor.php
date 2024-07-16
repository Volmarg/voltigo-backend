<?php

namespace App\Service\Logger\Processor;

use App\Controller\Core\Env;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Dirty workaround to make the deprecation logger finally SHOUT THE F up,
 */
class DeprecationProcessor implements ProcessorInterface
{
    private const KEY_MESSAGE    = "message";
    private const KEY_LEVEL_NAME = "level_name";
    private const KEY_CONTEXT    = "context";
    private const KEY_DATETIME   = "datetime";
    private const KEY_LEVEL      = "level";
    private const KEY_DEPRECATED = "deprecated";

    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(array $record)
    {
        $message = $record[self::KEY_MESSAGE] ?? "";
        if (
                !Env::isDeprecationLogging()
            &&  str_contains(strtolower($message), strtolower(self::KEY_DEPRECATED))
        ) {
            return [
                self::KEY_MESSAGE    => "",
                self::KEY_DATETIME   => $record[self::KEY_DATETIME],
                self::KEY_LEVEL      => 0,
                self::KEY_LEVEL_NAME => 0,
                self::KEY_CONTEXT    => [],
            ];
        }

        return $record;
    }
}