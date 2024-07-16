<?php

namespace App\Service\Security;

use App\Service\Validation\ValidationService;
use LogicException;

/**
 * Provides some base64 related logic
 */
class Base64Service
{

    /**
     * The problem is that there were / are a bunch of issues with base64 encoding
     * Php can't tell if some string is really a base64:
     * - from perspective of base64 the character range etc. will mean it IS base64,
     * - but the string can be sometimes just a word that falls into the base64 criteria
     *
     * This prefix can be used when needed to ENSURE that provided string is real base64.
     *
     * There were several attempts to solve this,
     * (where at least one still remains {@see ValidationService::isBase64EncodedValue()}), but none was perfect enough
     */
    public const BASE_64_PREFIX = "=ENC/";

    /**
     * @param string $testedString
     *
     * @return bool
     */
    public static function hasBase64InternalPrefix(string $testedString): bool
    {
        return str_starts_with($testedString, self::BASE_64_PREFIX);
    }

    /**
     * Removes the {@see Base64Service::BASE_64_PREFIX} from string and returns the real base64 string,
     * however if the prefix is not present then it will throw exception
     *
     * @param string $targetString
     *
     * @return string
     */
    public static function getRealBas64String(string $targetString): string
    {
        if (!self::hasBase64InternalPrefix($targetString)) {
            throw new LogicException("This string is missing the prefix: " . self::BASE_64_PREFIX);
        }

        return str_replace(self::BASE_64_PREFIX, "", $targetString);
    }
}