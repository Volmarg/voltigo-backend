<?php

namespace App\Response\System\State;

use App\Action\System\SystemStateAction;
use App\Response\Base\BaseResponse;

/**
 * Response for {@see SystemStateAction::getOffersHandlerState()}
 */
class OffersHandlerState extends BaseResponse
{

    private bool $reachable = false;
    private bool $quotaReached = false;

    /**
     * @return bool
     */
    public function isReachable(): bool
    {
        return $this->reachable;
    }

    /**
     * @param bool $reachable
     */
    public function setReachable(bool $reachable): void
    {
        $this->reachable = $reachable;
    }

    /**
     * @return bool
     */
    public function isQuotaReached(): bool
    {
        return $this->quotaReached;
    }

    /**
     * @param bool $quotaReached
     */
    public function setQuotaReached(bool $quotaReached): void
    {
        $this->quotaReached = $quotaReached;
    }

}