<?php

namespace App\DTO\Geo;

class CountryDataDto
{
    private string $twoDigitCode;

    /**
     * @return string
     */
    public function getTwoDigitCode(): string
    {
        return $this->twoDigitCode;
    }

    /**
     * @param string $twoDigitCode
     */
    public function setTwoDigitCode(string $twoDigitCode): void
    {
        $this->twoDigitCode = $twoDigitCode;
    }

}