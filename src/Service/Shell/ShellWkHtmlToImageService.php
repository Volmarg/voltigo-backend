<?php

namespace App\Service\Shell;

use App\Exception\Lib\HtmlToImageException;
use Exception;

/**
 * Converts raw html to image, it does offer, a LOT of options but in many cases the
 * quality of output image is poor.
 *
 * It however can be used to provide compressed (lower quality) images
 *
 * Class ShellPhpService
 * @package App\Services\Shell
 */
class ShellWkHtmlToImageService extends ShellHtmlToImageAbstractService
{
    public const DEFAULT_EXTENSION = "jpg";

    const EXECUTABLE_BINARY_NAME = "wkhtmltoimage";

    /**
     * That's needed because if images used in html are not reachable then the tool runs almost forever just to crash
     * with information that access has been denied etc.
     *
     * This time should be enough for fetching most of the images etc.
     */
    const COMMAND_TIMEOUT = 15; // in seconds

    /**
     * Quality of the output image, Int<0,100>
     * - the lower the value the worse the quality is
     */
    private const PARAM_QUALITY   = "quality";

    private const DEFAULT_QUALITY = 20;

    /**
     * Defined the height of the output image, <Int 0, x>
     */
    private const PARAM_HEIGHT = "height";
    private const HEIGHT_AUTO  = 0;

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
     * @param int    $quality
     * @param int    $height
     * @param int    $timeout
     *
     * @return string
     *
     * @throws HtmlToImageException
     */
    public function htmlToBase64(string $rawHtml, int $quality = self::DEFAULT_QUALITY, int $height = self::HEIGHT_AUTO, int $timeout = self::COMMAND_TIMEOUT): string
    {
        $tempFilePath     = "/tmp/" . uniqid() . ".";
        $tempHtmlFilePath = $tempFilePath . "html";
        $tempJpgFilePath  = $tempFilePath . self::DEFAULT_EXTENSION;

        file_put_contents($tempHtmlFilePath, $rawHtml);

        $command = $this->buildCommand([
            "--" . self::PARAM_QUALITY,
            $quality,
            "--" . self::PARAM_HEIGHT,
            $height,
            $tempHtmlFilePath,
            $tempJpgFilePath
        ], true, $timeout);

        $this->executeShellCommand($command, $timeout);

        if (!file_exists($tempJpgFilePath)) {
            unlink($tempHtmlFilePath);
            throw new HtmlToImageException("Failed creating output image file!");
        }

        $imageFileContent   = file_get_contents($tempJpgFilePath);
        $base64ImageContent = base64_encode($imageFileContent);

        unlink($tempJpgFilePath);
        unlink($tempHtmlFilePath);

        return $base64ImageContent;
    }

}