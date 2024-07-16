<?php

namespace App\Response\User;

use App\Response\Base\BaseResponse;

/**
 * Response which delivers information if certain user regulation was accepted or not
 */
class IsUserRegulationAcceptedResponse extends BaseResponse
{
    private bool $accepted;

    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->accepted;
    }

    /**
     * @param bool $accepted
     */
    public function setAccepted(bool $accepted): void
    {
        $this->accepted = $accepted;
    }

}