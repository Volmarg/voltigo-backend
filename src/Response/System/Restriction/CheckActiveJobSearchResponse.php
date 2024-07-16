<?php

namespace App\Response\System\Restriction;

use App\Response\Base\BaseResponse;

class CheckActiveJobSearchResponse extends BaseResponse
{
    private bool $maxReached = false;
    private int  $countOfActive;

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
    public function getCountOfActive(): int
    {
        return $this->countOfActive;
    }

    /**
     * @param int $countOfActive
     */
    public function setCountOfActive(int $countOfActive): void
    {
        $this->countOfActive = $countOfActive;
    }

}