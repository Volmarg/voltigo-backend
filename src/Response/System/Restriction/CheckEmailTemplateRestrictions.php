<?php

namespace App\Response\System\Restriction;

use App\Response\Base\BaseResponse;

class CheckEmailTemplateRestrictions extends BaseResponse
{
    private bool $maxReached = false;
    private int $count;
    private int $maxAllowed;

    /**
     * @return bool
     */
    public function isMaxReached(): bool
    {
        return $this->maxReached;
    }

    /**
     * @param bool $maxReached
     */
    public function setMaxReached(bool $maxReached): void
    {
        $this->maxReached = $maxReached;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    /**
     * @return int
     */
    public function getMaxAllowed(): int
    {
        return $this->maxAllowed;
    }

    /**
     * @param int $maxAllowed
     */
    public function setMaxAllowed(int $maxAllowed): void
    {
        $this->maxAllowed = $maxAllowed;
    }

}