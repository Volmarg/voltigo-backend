<?php

namespace App\Service\Shell;

use Exception;

/**
 * Converts raw html to pdf
 * - this is not perfect, the pdf generation this way has issues, that's known, but in general the output is pretty decent,
 * - the good side of such pdf is that text is still selectable inside etc. (it's not one big image),
 *   also it allows to auto split html per pages (A4 for example),
 *
 * @package App\Services\Shell
 */
class ShellHtmlToMultiPagePdfService extends ShellAbstractService
{
    private const ORIENTATION_LANDSCAPE = "Landscape";
    private const ORIENTATION_PORTRAIT= "Portrait";

    private const PAGE_SIZE_A4 = "A4";

    /**
     * If handler fails then it breaks the further execution and raises error
     */
    private const ERROR_HANDLING_STRATEGY_ABORT = "abort";

    /**
     * Defines the size of page, accepts standard names like A1, A2 etc. (not all might be supported)
     */
    private const ARG_PAGE_SIZE = "s";

    /**
     * Specify how to handle media that fail to load
     */
    private const OPTION_LOAD_MEDIA_ERROR_HANDLING = "load-media-error-handling";

    /**
     * Specify how to handle pages that fail to load
     */
    private const OPTION_LOAD_ERROR_HANDLING = "load-error-handling";

    /**
     * Also understanding ass "Screen size" for which pdf is generated, if not provided it may turn out that
     * pdf is showing as "mobile view". It does not define that pdf will be that big, it just defines what view
     * the pdf will be generated for (if the html is responsive - most likely).
     */
    private const OPTION_VIEWPORT_SIZE = "viewport-size";

    /**
     * The official documentation says "Disable the intelligent shrinking strategy",
     * In practice this causes some issues with pdf displaying incorrectly, fore example as if "mobile view"
     */
    private const PARAM_DISABLE_SMART_SHRINKING = "disable-smart-shrinking";

    const EXECUTABLE_BINARY_NAME = "wkhtmltopdf";

    /**
     * That's needed because if images used in html are not reachable then the tool runs almost forever just to crash
     * with information that access has been denied etc.
     *
     * This time should be enough for fetching most of the images etc.
     */
    const COMMAND_TIMEOUT = 30; // in seconds

    /**
     * Controls the pdf page orientation
     */
    private const ARG_ORIENTATION  = "O";


    /**
     * Will return executable php binary name
     * @Return string
     */
    protected function getExecutableBinaryName(): string
    {
        return self::EXECUTABLE_BINARY_NAME;
    }

    /**
     * Takes html content, creates temp file, reads temp file via shell, creates pdf out of it,
     * returns base64 content of the pdf
     *
     * @param string      $rawHtml
     * @param string      $orientation
     * @param string      $pageSize
     * @param string|null $viewPortSize
     * @param int         $timeout
     *
     * @return string
     *
     * @throws Exception
     */
    public function htmlToPdf(
        string  $rawHtml,
        string  $orientation = self::ORIENTATION_PORTRAIT,
        string  $pageSize = self::PAGE_SIZE_A4,
        ?string $viewPortSize = "1920x1080",
        int     $timeout = self::COMMAND_TIMEOUT
    ): string {
        $tempFilePath     = "/tmp/" . uniqid() . ".";
        $tempHtmlFilePath = $tempFilePath . "html";
        $tempPdfFilePath  = $tempFilePath . "pdf";

        if (!preg_match("#\d+x\d+#", $viewPortSize)) {
            throw new Exception("This is not valid viewport: {$viewPortSize}, expected syntax like '800x600'");
        }

        file_put_contents($tempHtmlFilePath, $rawHtml);

        $command = $this->buildCommand([
            "-" . self::ARG_ORIENTATION,
            $orientation,
            "-" . self::ARG_PAGE_SIZE,
            $pageSize,
            "--" . self::PARAM_DISABLE_SMART_SHRINKING,
            "--" . self::OPTION_LOAD_ERROR_HANDLING,
            self::ERROR_HANDLING_STRATEGY_ABORT,
            "--" . self::OPTION_LOAD_MEDIA_ERROR_HANDLING,
            self::ERROR_HANDLING_STRATEGY_ABORT,
            "--" . self::OPTION_VIEWPORT_SIZE,
            $viewPortSize,
            $tempHtmlFilePath,
            $tempPdfFilePath
        ], true, $timeout);

        $this->executeShellCommand($command, $timeout);

        if (!file_exists($tempPdfFilePath)) {
            unlink($tempHtmlFilePath);
            throw new Exception("Failed creating output image file!");
        }

        $imageFileContent = file_get_contents($tempPdfFilePath);
        $base64Content    = base64_encode($imageFileContent);

        unlink($tempPdfFilePath);
        unlink($tempHtmlFilePath);

        return $base64Content;
    }

}