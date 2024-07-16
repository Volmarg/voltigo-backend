<?php

namespace App\Enum\File;

/**
 * Describes the allowed file sources.
 * Where source means where was it uploaded / what it will be used for
 */
enum UploadedFileSourceEnum: string
{
    case CV            = "CV";
    case PROFILE_IMAGE = "PROFILE_IMAGE";
    case EASY_EMAIL    = "EASY_EMAIL";
}
