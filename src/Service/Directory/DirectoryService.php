<?php

namespace App\Service\Directory;

class DirectoryService
{
    /**
     * Will go over the directory recursively, removing all it's files and folders.
     * If provided directory path is a file then it will just unlink it
     *
     * @param string $dirPath
     *
     * @return bool
     */
    public static function rmRf(string $dirPath): bool
    {
        if (!file_exists($dirPath)) {
            return true;
        }

        if (!is_dir($dirPath)) {
            return unlink($dirPath);
        }

        foreach (scandir($dirPath) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!self::rmRf($dirPath . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dirPath);
    }
}