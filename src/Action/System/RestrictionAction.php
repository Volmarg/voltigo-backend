<?php

namespace App\Action\System;

use App\Response\System\Restriction\CheckActiveJobSearchResponse;
use App\Response\System\Restriction\GetMaxAllowedPaymentDataResponse;
use App\Response\System\Restriction\CheckEmailTemplateRestrictions;
use App\Response\System\Restriction\MaxTemplateTestEmailResponse;
use App\Service\Payment\PaymentLimiterService;
use App\Service\System\Restriction\EmailTemplateRestrictionService;
use App\Service\System\Restriction\EmailTemplateTestSendingRestrictionService;
use App\Service\System\Restriction\JobSearchRestrictionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RestrictionAction
{
    public function __construct(
        private readonly JobSearchRestrictionService                $jobSearchRestrictionService,
        private readonly EmailTemplateRestrictionService            $emailTemplateRestrictionService,
        private readonly EmailTemplateTestSendingRestrictionService $emailTemplateTestSendingRestrictionService
    ){}

    /**
     * Will check if logged-in user has reached limit of max active / parallel calls for offer search
     *
     * @return JsonResponse
     */
    #[Route("/system/check-max-active-job-search", name: "system.check.max.active.job.search", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function hasReachedMaxActiveSearch(): JsonResponse
    {
        $response = CheckActiveJobSearchResponse::buildOkResponse();
        $response->setMaxReached($this->jobSearchRestrictionService->hasReachedMaxActiveSearch());
        $response->setCountOfActive($this->jobSearchRestrictionService->getCountOfActive());

        return $response->toJsonResponse();
    }

    /**
     * Check the email template restrictions of logged-in user
     *
     * @return JsonResponse
     */
    #[Route("/system/check-email-template-restrictions", name: "system.check_email_template_restrictions", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function checkEmailTemplateRestrictions(): JsonResponse
    {
        $response = CheckEmailTemplateRestrictions::buildOkResponse();
        $response->setMaxReached($this->emailTemplateRestrictionService->hasReachedMaxTemplates());
        $response->setCount($this->emailTemplateRestrictionService->getCountOfTemplates());
        $response->setMaxAllowed($this->emailTemplateRestrictionService->getMaxAllowedTemplates());

        return $response->toJsonResponse();
    }

    /**
     * Check the "E-Mail Template" test E-Mail restriction
     *
     * @return JsonResponse
     */
    #[Route("/system/restriction/email-template/test-mail-state", name: "system.restriction.email_template.test_state", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function checkEmailTemplateTestMailState(): JsonResponse
    {
        $response = MaxTemplateTestEmailResponse::buildOkResponse();
        $response->setMaxPerDay(EmailTemplateTestSendingRestrictionService::MAX_PER_DAY);
        $response->setSentToday($this->emailTemplateTestSendingRestrictionService->countSentToday());

        return $response->toJsonResponse();
    }

    /**
     * Will return max allowed payment data
     *
     * @return JsonResponse
     */
    #[Route("/system/get-max-allowed-payment-data", name: "system.get_max_allowed_payment_data", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getMaxAllowedPaymentData(): JsonResponse
    {
        $response = GetMaxAllowedPaymentDataResponse::buildOkResponse();
        $response->setUnit(PaymentLimiterService::MAX_ALLOWED_PAYMENT_UNITS);
        $response->setCurrency(PaymentLimiterService::MAX_ALLOWED_PAYMENT_CURRENCY);

        return $response->toJsonResponse();
    }
}