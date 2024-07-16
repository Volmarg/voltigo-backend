<?php

namespace App\Controller\Email;

use App\Controller\Core\Services;
use App\Entity\Email\EmailTemplate;
use App\Repository\Email\EmailTemplateRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Handles logic related to the Email builder templates (front)
 */
class EmailTemplateController
{

    public function __construct(
        private EmailTemplateRepository          $emailTemplateRepository,
        private Services                         $services,
    )
    {}

    /**
     * Will return one template for id or null
     *
     * @param int $id
     * @return EmailTemplate|null
     */
    public function findOneById(int $id): ?EmailTemplate
    {
        return $this->emailTemplateRepository->find($id);
    }

    /**
     * Will check if given template name is unique or not
     *
     * @param string $name
     * @param array  $excludedIds
     *
     * @return bool
     */
    public function isNameUnique(string $name, array $excludedIds = []): bool
    {
        $emailTemplate = $this->fineOneNotDeletedByName($name, $excludedIds);
        return empty($emailTemplate);
    }

    /**
     * Will return one template by the name or null if nothing was found
     *
     * @param string $name
     * @param array  $excludedIds
     *
     * @return EmailTemplate|null
     */
    public function fineOneNotDeletedByName(string $name, array $excludedIds = []): ?EmailTemplate
    {
        $allTemplates = $this->emailTemplateRepository->findBy([
            EmailTemplate::FIELD_NAME_EMAIL_TEMPLATE_NAME => $name,
            EmailTemplate::FIELD_NAME_EMAIL_DELETED       => false,
        ]);

        foreach($allTemplates as $template){
            if( !in_array($template->getId(), $excludedIds) ){
                return $template;
            }
        }

        return null;
    }

    /**
     * Will update existing template or create new one
     *
     * @param EmailTemplate $emailTemplate
     *
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(EmailTemplate $emailTemplate): void
    {
        $this->emailTemplateRepository->save($emailTemplate);
    }

    /**
     * @param EmailTemplate $emailTemplate
     * @param array         $excludedIds
     *
     * @return array
     */
    public function buildNotUniqueNameViolation(EmailTemplate $emailTemplate, array $excludedIds = []): array
    {
        $isNameUnique = $this->isNameUnique($emailTemplate->getEmailTemplateName(), $excludedIds);
        if(!$isNameUnique) {
            return [
                EmailTemplate::FIELD_NAME_EMAIL_TEMPLATE_NAME => $this->services->getTranslator()->trans("email.builder.entity.violations.name.notUnique"),
            ];
        }

        return [];
    }

}