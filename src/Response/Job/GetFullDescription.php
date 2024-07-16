<?php

namespace App\Response\Job;

use App\Response\Base\BaseResponse;

/**
 * Response delivering the offer full description
 */
class GetFullDescription extends BaseResponse
{
    /**
     * @var string $description
     */
    private string $description;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

}