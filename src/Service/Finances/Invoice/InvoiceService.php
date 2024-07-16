<?php

namespace App\Service\Finances\Invoice;

use App\Service\Security\JwtAuthenticationService;
use Exception;

class InvoiceService
{
    private const FILE_NAME_HARDCODED_PART = "invoice";

    /**
     * Return the folder under which invoice for given user is stored
     *
     * @param int $userId
     * @param int $orderId
     *
     * @return string
     */
    public function getAbsoluteInvoiceFolderPath(int $userId, int $orderId): string
    {
        $folderPath = $this->saveBaseAbsoluteDirectory
                      . $userId
                      . DIRECTORY_SEPARATOR
                      . $orderId
                      . DIRECTORY_SEPARATOR;

        return $folderPath;
    }

    /**
     * Same as {@see InvoiceService::getAbsoluteInvoiceFolderPath()}, but in current case it builds the path relative
     * toward public directory, meaning that this path is also going to be used on front for file downloading etc.
     *
     * @param int $userId
     * @param int $orderId
     *
     * @return string
     */
    public function getInvoiceFolderPathRelativeTowardPublic(int $userId, int $orderId): string
    {
        $folderPath = $this->saveBaseDirectoryRelativeTowardPublic
                      . $userId
                      . DIRECTORY_SEPARATOR
                      . $orderId
                      . DIRECTORY_SEPARATOR;

        return $folderPath;
    }

    /**
     * @param string                   $saveBaseAbsoluteDirectory
     * @param string                   $saveBaseDirectoryRelativeTowardPublic
     * @param JwtAuthenticationService $jwtAuthenticationService
     */
    public function __construct(
        private readonly string                   $saveBaseAbsoluteDirectory,
        private readonly string                   $saveBaseDirectoryRelativeTowardPublic,
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ) {
    }

    /**
     * Instead of calling the {@see InvoiceService::saveInvoicePdf()} over and over again, this function will check
     * if a pdf already exists under path for user.
     *
     * If it exists then returns the existing file path, else returns null.
     *
     * @param int $userId
     * @param int $orderId
     *
     * @return string|null
     */
    public function checkAndGetExistingInvoicePdf(int $userId, int $orderId): ?string
    {
        $folder = $this->getAbsoluteInvoiceFolderPath($userId, $orderId);
        if (!file_exists($folder)) {
            return null;
        }

        $fileNames = scandir($folder);
        foreach ($fileNames as $fileName) {
            if (str_contains($fileName, ".pdf")) {
                $inPublicPath = $this->getInvoiceFolderPathRelativeTowardPublic($userId, $orderId);
                return $inPublicPath . $fileName;
            }
        }

        return null;
    }

    /**
     * Saves the pdf on server and sends the path to the saved file
     *
     * @param string $pdfContent
     * @param int    $orderId
     *
     * @return string
     *
     * @throws Exception
     */
    public function saveInvoicePdf(string $pdfContent, int $orderId): string
    {
        $user     = $this->jwtAuthenticationService->getUserFromRequest();
        $fileName = $user->getId()
                    . "_"
                    . self::FILE_NAME_HARDCODED_PART
                    . "_"
                    . (new \DateTime())->format("Y_m_d_h_i_s")
                    . ".pdf";

        $folderPath   = $this->getAbsoluteInvoiceFolderPath($user->getId(), $orderId);
        $fullPath     = $folderPath . $fileName;
        $inPublicPath = $this->getInvoiceFolderPathRelativeTowardPublic($user->getId(), $orderId);
        if (!file_exists($folderPath)) {
            $isCreated = mkdir($folderPath, 0775, true);
            if (!$isCreated) {
                throw new Exception("Could not create folder for invoice: {$folderPath}");
            }
        }

        $isSaved = file_put_contents($fullPath, $pdfContent);
        if (!$isSaved) {
            throw new Exception("Could not save the invoice pdf under path: {$fullPath}");
        }

        return $inPublicPath . $fileName;
    }

}