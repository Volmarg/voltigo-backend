<?php

namespace App\Service\File\Path;

/**
 * Handles directories related logic
 */
class PathService
{

    /**
     * Checks if given path ends with slash and if it does then nothing happen, else adds the slash,
     * This function does not care if provided path is directory or not, it only handles slash adding
     *
     * @param string $path
     *
     * @return string
     */
    public static function setTrailingSlash(string $path): string
    {
        $usedPath = (
                    str_ends_with($path, DIRECTORY_SEPARATOR)
                    ?   $path
                    :   $path. DIRECTORY_SEPARATOR
        );

        return $usedPath;
    }
}