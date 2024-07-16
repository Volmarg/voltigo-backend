<?php

namespace App\Response\Email;

use App\Action\Email\EmailTemplateAction;
use App\Response\Base\BaseResponse;

/**
 * Related explicitly to the {@see EmailTemplateAction::reFetch()}
 */
class ReFetchTemplate extends BaseResponse
{
    /**
     * @var string|null
     */
    private ?string $emailTemplate = null;

    /**
     * @return string|null
     */
    public function getEmailTemplate(): ?string
    {
        return $this->emailTemplate;
    }

    /**
     * @param string|null $emailTemplate
     */
    public function setEmailTemplate(?string $emailTemplate): void
    {
        $this->emailTemplate = $emailTemplate;
    }

}