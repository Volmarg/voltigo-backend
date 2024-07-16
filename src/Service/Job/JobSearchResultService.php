<?php

namespace App\Service\Job;

use App\Action\Job\JobSearchResultAction;
use App\DTO\Internal\Job\JobSearchDTO;
use App\Response\Base\BaseResponse;
use App\Service\Api\JobSearcher\JobSearchService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\System\Restriction\JobSearchRestrictionService;
use App\Service\System\State\SystemStateService;
use App\Service\Validation\ValidationService;
use App\Service\Websocket\WebsocketServerConnectionHandler;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * {@see JobSearchResultAction} - this service is strictly related to that class
 */
class JobSearchResultService
{

    public function __construct(
        private readonly JobSearchService            $jobSearchService,
        private readonly SystemStateService          $systemStateService,
        private readonly TranslatorInterface         $translator,
        private readonly JobSearchRestrictionService $jobSearchRestrictionService,
        private readonly ValidationService           $validationService,
        private readonly JwtAuthenticationService    $jwtAuthenticationService
    ){}

    /**
     * Will check if the job searching can be started for variety of conditions,
     *
     * If {@see BaseResponse} gets returned then it means "not allowed to search",
     * otherwise null gets returned meaning that everything is fine and job search can be started.
     *
     * @param JobSearchDTO $searchDto
     *
     * @return BaseResponse|null
     *
     * @throws GuzzleException
     */
    public function checkIfCanSearchForJobs(JobSearchDTO $searchDto, ): ?BaseResponse
    {
        if (!WebsocketServerConnectionHandler::isWebsocketRunning()) {
            $message = $this->translator->trans('generic.internalServerError');
            return BaseResponse::buildInternalServerErrorResponse($message);
        }

        if (!$this->jobSearchService->pingService()) {
            $message = $this->translator->trans('job.search.messages.offersHandlerNotReachable');
            return BaseResponse::buildBadRequestErrorResponse($message);
        }

        if ($this->systemStateService->isOffersHandlerQuotaReached()) {
            $message = $this->translator->trans('job.search.messages.quotaReached');
            return BaseResponse::buildBadRequestErrorResponse($message);
        }

        if ($this->jobSearchRestrictionService->hasReachedMaxActiveSearch()) {
            // don't care about trans, user is dirty messing code
            return BaseResponse::buildBadRequestErrorResponse("You are not allowed to search for offers. Max active search has been reached!");
        }

        $violations = $this->validationService->validateAndReturnArrayOfInvalidFieldsWithMessages($searchDto);
        if (!$violations->isSuccess()) {
            return BaseResponse::buildInvalidFieldsRequestErrorResponse($violations->getViolationsWithMessages());
        }

        $user = $this->jwtAuthenticationService->getUserFromRequest();
        if ($searchDto->countJobSearchKeywords() > $this->jobSearchRestrictionService->getMaxSearchedKeywords($user)) {
            // don't care about trans, user is dirty messing code
            return BaseResponse::buildBadRequestErrorResponse("You are not allowed to search for this many keywords!");
        }

        return null;
    }

}