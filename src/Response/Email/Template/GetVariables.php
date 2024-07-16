<?php

namespace App\Response\Email\Template;

use App\Action\Email\EmailTemplateAction;
use App\Response\Base\BaseResponse;

/**
 * Related explicitly to the {@see EmailTemplateAction::getDummyVariables()}
 * and {@see EmailTemplateAction::getRealVariables()}
 */
class GetVariables extends BaseResponse
{
    /**
     * @var array $variablesData
     */
    private array $variablesData;

    /**
     * @return array
     */
    public function getVariablesData(): array
    {
        return $this->variablesData;
    }

    /**
     * @param array $offerData
     */
    public function setVariablesData(array $offerData): void
    {
        $this->variablesData = $offerData;
    }


}