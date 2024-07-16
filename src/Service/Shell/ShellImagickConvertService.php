<?php

namespace App\Service\Shell;

use App\Exception\Lib\HtmlToImageException;

/**
 * Calls the: https://linux.die.net/man/1/imagemagick (convert)
 *
 * @package App\Services\Shell
 */
class ShellImagickConvertService extends ShellAbstractService
{
    private const DEFAULT_QUALITY = 0; //max

    private const DEFAULT_SHARPENING = '0x0.5'; // slight

    private const DEFAULT_DENSITY = "80"; //was found out that it's the most optimal one

    private const DEFAULT_COMPRESSION_TYPE = "Zip"; // didn't test out other ones, this is just fine

    const EXECUTABLE_BINARY_NAME = "convert";

    /**
     * Defines quality of input / output file
     */
    private const PARAM_QUALITY = "quality";

    /**
     * This is explicitly for PDF only - DPI
     */
    private const PARAM_DENSITY = "density";

    /**
     * Crops the image:
     * Form is: convert input.jpg -crop WIDTHxHEIGHT+0+0 result.jpg
     */
    private const PARAM_CROP = "crop";

    /**
     * Sets the compression of data in output file (type of pixel compression when writing the image)
     */
    private const PARAM_COMPRESS = "compress";

    /**
     * Make the output image sharper
     */
    private const PARAM_SHARPEN = "sharpen";

    /**
     * Will return executable php binary name
     * @Return string
     */
    protected function getExecutableBinaryName(): string
    {
        return self::EXECUTABLE_BINARY_NAME;
    }

    const COMMAND_TIMEOUT = 30; // in seconds

    /**
     * Takes input path and converts that to file of a format based on outputExtension.
     *
     * Example:
     * - input: /input.jpg
     * - outputExtension: pdf
     *
     * Result is working file in "pdf" format.
     *
     * This function returns base64 content of "output" file
     *
     * @param string      $sourceFilePath
     * @param string      $outputExtension
     * @param bool        $sharpen
     * @param string|null $croppingParams - example 200x300 (crop to: 200 in width, 300 in height), x300 (only height) etc.
     * @param int         $quality        - the lower number the better quality <0-100>
     *
     * @return string
     *
     * @throws HtmlToImageException
     */
    public function getBase64(
        string  $sourceFilePath,
        string  $outputExtension,
        bool    $sharpen = true,
        ?string $croppingParams = null,
        int     $quality = self::DEFAULT_QUALITY
    ): string
    {
        $tempFilePath = "/tmp/" . uniqid() . "." . $outputExtension;
        $isPdf        = (strtolower($outputExtension) === "pdf");

        $sharpening = ($sharpen                  ? " -" . self::PARAM_SHARPEN . " " . self::DEFAULT_SHARPENING : '');
        $density    = ($isPdf                    ? " -" . self::PARAM_DENSITY . " " . self::DEFAULT_DENSITY    : '');
        $cropping   = (!is_null($croppingParams) ? " -" . self::PARAM_CROP    . " {$croppingParams}+0+0"       : '');

        $command = $this->buildCommand([
            " {$sourceFilePath}",
            " -" . self::PARAM_QUALITY  . " {$quality}",
            " -" . self::PARAM_COMPRESS . " " . self::DEFAULT_COMPRESSION_TYPE,
            $sharpening,
            $density,
            $cropping,
            " {$tempFilePath}",
        ], false);

        $this->executeShellCommand($command);

        if (!file_exists($tempFilePath)) {
            throw new HtmlToImageException("Failed creating output image file!");
        }

        $imageFileContent = file_get_contents($tempFilePath);
        $base64Content    = base64_encode($imageFileContent);

        unlink($tempFilePath);

        return $base64Content;
    }

}