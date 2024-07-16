<?php

namespace App\Service\Shell;

use App\Exception\Lib\HtmlToImageException;

/**
 * Relies on: {@link https://manpages.org/cutycapt},
 *
 * Similar to: {@see ShellWkHtmlToImageService}, however
 * in this case the image quality is better.
 *
 * @package App\Services\Shell
 */
class ShellCutyCapService extends ShellHtmlToImageAbstractService
{
    public const DEFAULT_EXTENSION = "png";

    // this is what browser adds when local file is being opened
    private const PREFIX_LOCAL_FILE_URL = "file://";

    const EXECUTABLE_BINARY_NAME = "cutycapt";

    /**
     * Input page url
     */
    private const PARAM_URL = "url";

    const COMMAND_TIMEOUT = 30; // in seconds

    /**
     * Output file path
     */
    private const PARAM_OUT = "out";

    /**
     * Will return executable php binary name
     * @Return string
     */
    protected function getExecutableBinaryName(): string
    {
        return self::EXECUTABLE_BINARY_NAME;
    }

    /**
     * Takes html content, creates temp file, reads temp file via shell, creates image, reads image as base64
     * The result of this function is base64 content of image rendered from html
     *
     * @param string $rawHtml
     *
     * @return string
     *
     * @throws HtmlToImageException
     */
    public function htmlToBase64(string $rawHtml): string
    {
        $tempFilePath     = "/tmp/" . uniqid() . ".";
        $tempHtmlFilePath = $tempFilePath . "html";
        $tempOutputPath   = $tempFilePath . self::DEFAULT_EXTENSION;

        file_put_contents($tempHtmlFilePath, $rawHtml);

        $command = $this->buildCommand([
            " --" . self::PARAM_URL  . "=",
            self::PREFIX_LOCAL_FILE_URL . $tempHtmlFilePath,
            " --" . self::PARAM_OUT . "=" . $tempOutputPath,
        ], false, 0, true);

        $this->executeShellCommand($command, self::COMMAND_TIMEOUT);

        if (!file_exists($tempOutputPath)) {
            unlink($tempHtmlFilePath);
            throw new HtmlToImageException("Failed creating output image file!");
        }

        $imageFileContent   = file_get_contents($tempOutputPath);
        $base64ImageContent = base64_encode($imageFileContent);

        unlink($tempOutputPath);
        unlink($tempHtmlFilePath);

        return $base64ImageContent;
    }

}