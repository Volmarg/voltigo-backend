<?php

namespace App\Response\Email;

use App\Action\Email\EmailTemplateAction;
use App\Response\Base\BaseResponse;

/**
 * Related explicitly to the {@see EmailTemplateAction::getAllForUser()}
 */
class GetTemplates extends BaseResponse
{

    /**
     * @var array
     */
    private array $templatesDataArray = [];

    /**
     * @return array
     */
    public function getTemplatesDataArray(): array
    {
        return $this->templatesDataArray;
    }

    /**
     * @param array $templatesDataArray
     */
    public function setTemplatesDataArray(array $templatesDataArray): void
    {
        $this->templatesDataArray = $templatesDataArray;
    }

}