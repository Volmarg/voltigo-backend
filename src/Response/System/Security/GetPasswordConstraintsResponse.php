<?php

namespace App\Response\System\Security;

use App\Response\Base\BaseResponse;

class GetPasswordConstraintsResponse extends BaseResponse
{
    private array $constraints = [];

    /**
     * @return array
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * @param array $constraints
     */
    public function setConstraints(array $constraints): void
    {
        $this->constraints = $constraints;
    }

}