<?php

namespace App\Response\System\Restriction;

use App\Action\System\RestrictionAction;
use App\Response\Base\BaseResponse;

/**
 * {@see RestrictionAction::checkEmailTemplateTestMailState()}
 */
class MaxTemplateTestEmailResponse extends BaseResponse
{
    private int $sentToday;
    private int $maxPerDay;

    public function getSentToday(): int
    {
        return $this->sentToday;
    }

    public function setSentToday(int $sentToday): void
    {
        $this->sentToday = $sentToday;
    }

    public function getMaxPerDay(): int
    {
        return $this->maxPerDay;
    }

    public function setMaxPerDay(int $maxPerDay): void
    {
        $this->maxPerDay = $maxPerDay;
    }

}