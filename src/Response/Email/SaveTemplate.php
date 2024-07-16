<?php

namespace App\Response\Email;

use App\Action\Email\EmailTemplateAction;
use App\Response\Base\BaseResponse;

/**
 * Related explicitly to the {@see EmailTemplateAction::save()}
 */
class SaveTemplate extends BaseResponse
{

    /**
     * @var int|null
     */
    private ?int $templateId = null;

    /**
     * @return int|null
     */
    public function getTemplateId(): ?int
    {
        return $this->templateId;
    }

    /**
     * @param int|null $templateId
     */
    public function setTemplateId(?int $templateId): void
    {
        $this->templateId = $templateId;
    }

}