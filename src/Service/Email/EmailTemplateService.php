<?php

namespace App\Service\Email;

use App\Entity\Email\Email;
use App\Entity\Email\EmailTemplate;
use App\Entity\Security\User;
use App\Enum\Email\TemplateIdentifierEnum;
use App\Exception\Lib\HtmlToImageException;
use App\Service\Shell\ShellCutyCapService;
use App\Service\Shell\ShellImagickConvertService;
use App\Service\Shell\ShellWkHtmlToImageService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;

class EmailTemplateService
{

    public function __construct(
        private readonly ShellImagickConvertService $shellImagickConvertService,
        private readonly ShellCutyCapService        $shellCutyCapService,
        private readonly EntityManagerInterface     $entityManager
    ) {
    }

    /**
     * Creates the email template base64 which is used to display the template miniature.
     * Not relying directly on {@see ShellWkHtmlToImageService} as it's sometimes not loading images.
     *
     * It creates image from html, then crops is to given size
     *
     * @throws HtmlToImageException
     */
    public function prepareExampleImageBase64(EmailTemplate $emailTemplate): string
    {
        $base64Example = $this->shellCutyCapService->htmlToBase64($emailTemplate->getExampleHtml());
        $tempFile      = "/tmp/" . uniqid() . "." . ShellCutyCapService::DEFAULT_EXTENSION;

        file_put_contents($tempFile, base64_decode($base64Example));

        $base64ExampleSmall = $this->shellImagickConvertService->getBase64($tempFile, "png", false, 'x700', 80);

        unlink($tempFile);

        return $base64ExampleSmall;
    }

    /**
     * Handles preparing {@see Email} out of the front-based data for {@see EmailTemplate}.
     * The E-Mail is then sent directly to user so that he can preview how the output E-Mail
     * will look like.
     *
     * @throws Exception
     */
    public function createTestEmail(string $body, EmailTemplate $emailTemplate, User $user): void
    {
        if (!$emailTemplate->getUser()) {
            throw new LogicException("User {$user->getId()}, tried to send template test mail for predefined template: {$emailTemplate->getId()}");
        }

        $subject = "[E-Mail template test] {$emailTemplate->getEmailTemplateName()} (Id: {$emailTemplate->getId()})";

        $emailEntity = new Email();
        $emailEntity->setSender($user);
        $emailEntity->setTemplate($emailTemplate);
        $emailEntity->setSubject($subject);
        $emailEntity->setBody($body);
        $emailEntity->setRecipients([$user->getEmail()]);
        $emailEntity->setIdentifier(TemplateIdentifierEnum::TEMPLATE_TEST_EMAIL->name);

        $this->entityManager->persist($emailEntity);
        $this->entityManager->flush();
    }

}
