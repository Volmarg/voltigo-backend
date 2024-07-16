<?php

namespace App\Controller\Core;

use App\Service\Attribute\AttributeReaderService;
use App\Service\Form\FormService;
use App\Service\Logger\LoggerService;
use App\Service\Routing\UrlMatcherService;
use App\Service\Security\CsrfTokenService;
use App\Service\Security\FrontendDecryptor;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Security\PasswordGeneratorService;
use App\Service\Security\UserSecurityService;
use App\Service\Serialization\ObjectSerializerService;
use App\Service\Shell\ShellService;
use App\Service\Templates\Email\UserTemplatesService;
use App\Service\Validation\ValidationService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains all of the services created for this project
 *
 * Class Services
 * @package App\Controller\Core
 * @deprecated - legacy garbage
 */
class Services
{

    /**
     * @var ValidationService $validationService
     */
    private ValidationService $validationService;

    /**
     * @var FormService $formService
     */
    private FormService $formService;

    /**
     * @var UserSecurityService $userSecurityService
     */
    private UserSecurityService $userSecurityService;

    /**
     * @var LoggerService LoggerService
     */
    private LoggerService $loggerService;

    /**
     * @var CsrfTokenService $csrfTokenService
     */
    private CsrfTokenService $csrfTokenService;

    /**
     * @var UrlMatcherService $urlMatcherService
     */
    private UrlMatcherService $urlMatcherService;

    /**
     * @var JwtAuthenticationService $jwtAuthenticationService
     */
    private JwtAuthenticationService $jwtAuthenticationService;

    /**
     * @var AttributeReaderService $attributeReaderService
     */
    private AttributeReaderService $attributeReaderService;

    /**
     * @var TranslatorInterface $translator
     */
    private TranslatorInterface $translator;

    /**
     * @var FrontendDecryptor $frontendDecryptor
     */
    private FrontendDecryptor $frontendDecryptor;

    /**
     * @var ShellService $shellService
     */
    private ShellService $shellService;

    /**
     * @var PasswordGeneratorService $passwordGeneratorService
     */
    private PasswordGeneratorService $passwordGeneratorService;

    /**
     * @var UserTemplatesService $userTemplateService
     */
    private UserTemplatesService $userTemplateService;

    /**
     * @return UserSecurityService
     */
    public function getUserSecurityService(): UserSecurityService
    {
        return $this->userSecurityService;
    }

    /**
     * @param UserSecurityService $userSecurityService
     */
    public function setUserSecurityService(UserSecurityService $userSecurityService): void
    {
        $this->userSecurityService = $userSecurityService;
    }

    /**
     * @return ValidationService
     */
    public function getValidationService(): ValidationService
    {
        return $this->validationService;
    }

    /**
     * @param ValidationService $validationService
     */
    public function setValidationService(ValidationService $validationService): void
    {
        $this->validationService = $validationService;
    }

    /**
     * @return FormService
     */
    public function getFormService(): FormService
    {
        return $this->formService;
    }

    /**
     * @param FormService $formService
     */
    public function setFormService(FormService $formService): void
    {
        $this->formService = $formService;
    }

    /**
     * @return LoggerService
     */
    public function getLoggerService(): LoggerService
    {
        return $this->loggerService;
    }

    /**
     * @param LoggerService $loggerService
     */
    public function setLoggerService(LoggerService $loggerService): void
    {
        $this->loggerService = $loggerService;
    }

    /**
     * @return CsrfTokenService
     */
    public function getCsrfTokenService(): CsrfTokenService
    {
        return $this->csrfTokenService;
    }

    /**
     * @param CsrfTokenService $csrfTokenService
     */
    public function setCsrfTokenService(CsrfTokenService $csrfTokenService): void
    {
        $this->csrfTokenService = $csrfTokenService;
    }

    /**
     * @return UrlMatcherService
     */
    public function getUrlMatcherService(): UrlMatcherService
    {
        return $this->urlMatcherService;
    }

    /**
     * @param UrlMatcherService $urlMatcherService
     */
    public function setUrlMatcherService(UrlMatcherService $urlMatcherService): void
    {
        $this->urlMatcherService = $urlMatcherService;
    }

    /**
     * @return JwtAuthenticationService
     */
    public function getJwtAuthenticationService(): JwtAuthenticationService
    {
        return $this->jwtAuthenticationService;
    }

    /**
     * @param JwtAuthenticationService $jwtAuthenticationService
     */
    public function setJwtAuthenticationService(JwtAuthenticationService $jwtAuthenticationService): void
    {
        $this->jwtAuthenticationService = $jwtAuthenticationService;
    }

    /**
     * @return AttributeReaderService
     */
    public function getAttributeReaderService(): AttributeReaderService
    {
        return $this->attributeReaderService;
    }

    /**
     * @param AttributeReaderService $attributeReaderService
     */
    public function setAttributeReaderService(AttributeReaderService $attributeReaderService): void
    {
        $this->attributeReaderService = $attributeReaderService;
    }

    /**
     * @return TranslatorInterface
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @return FrontendDecryptor
     */
    public function getFrontendDecryptor(): FrontendDecryptor
    {
        return $this->frontendDecryptor;
    }

    /**
     * @param FrontendDecryptor $frontendDecryptor
     */
    public function setFrontendDecryptor(FrontendDecryptor $frontendDecryptor): void
    {
        $this->frontendDecryptor = $frontendDecryptor;
    }

    /**
     * @return ShellService
     */
    public function getShellService(): ShellService
    {
        return $this->shellService;
    }

    /**
     * @param ShellService $shellService
     */
    public function setShellService(ShellService $shellService): void
    {
        $this->shellService = $shellService;
    }

    /**
     * @return PasswordGeneratorService
     */
    public function getPasswordGeneratorService(): PasswordGeneratorService
    {
        return $this->passwordGeneratorService;
    }

    /**
     * @param PasswordGeneratorService $passwordGeneratorService
     */
    public function setPasswordGeneratorService(PasswordGeneratorService $passwordGeneratorService): void
    {
        $this->passwordGeneratorService = $passwordGeneratorService;
    }

    /**
     * @return UserTemplatesService
     */
    public function getUserTemplateService(): UserTemplatesService
    {
        return $this->userTemplateService;
    }

    /**
     * @param UserTemplatesService $userTemplateService
     */
    public function setUserTemplateService(UserTemplatesService $userTemplateService): void
    {
        $this->userTemplateService = $userTemplateService;
    }

    /**
     * @return ObjectSerializerService
     */
    public function getObjectSerializerService(): ObjectSerializerService
    {
        return $this->objectSerializerService;
    }

    public function __construct(
        private ObjectSerializerService $objectSerializerService
    ){}

}