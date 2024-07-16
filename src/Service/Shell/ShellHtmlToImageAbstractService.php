<?php

namespace App\Service\Shell;

use App\Exception\Lib\HtmlToImageException;
use Exception;
use Symfony\Component\Process\Process;
use TypeError;

/**
 * Extra wrapper class designed especially when dealing with conversion from "HTML" to "IMG"
 */
abstract class ShellHtmlToImageAbstractService extends ShellAbstractService
{
    /**
     * @param string         $calledCommand
     * @param float|int|null $timeout
     *
     * @return Process
     * @throws HtmlToImageException
     */
    protected function executeShellCommand(string $calledCommand, float|int|null $timeout = null): Process
    {
        try {
            return parent::executeShellCommand($calledCommand, $timeout);
        } catch (Exception|TypeError $e) {
            $msg = "Exception was thrown, msg: {$e->getMessage()}, trace: {$e->getTraceAsString()}";
            throw new HtmlToImageException($msg);
        }
    }

}