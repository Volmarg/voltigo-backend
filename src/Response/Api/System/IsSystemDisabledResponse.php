<?php

namespace App\Response\Api\System;

use App\Response\Api\BaseApiResponse;

class IsSystemDisabledResponse extends BaseApiResponse
{
    private bool $disabled;

    // used explicitly by grafana etc.
    private int $disabledInt;

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getDisabledInt(): int
    {
        return $this->disabledInt;
    }

    public function setDisabledInt(int $disabledInt): void
    {
        $this->disabledInt = $disabledInt;
    }

}
