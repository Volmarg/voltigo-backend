<?php

namespace App\Service\Templates\Variable;

use App\DTO\Frontend\Email\Template\Variables\AllVariablesDTO;
use App\DTO\Frontend\Email\Template\Variables\JobOfferDataVariablesDTO;
use App\DTO\Frontend\Email\Template\Variables\UserVariablesDTO;
use App\Service\Security\JwtAuthenticationService;
use JobSearcherBridge\DTO\Offers\JobOfferAnalyseResultDto;

/**
 * Provides offers which can be used on front for rendering email template tags
 * However the special dto is used for this, other than {@see JobOfferAnalyseResultDto} because only some fields
 * should be allowed as the variables
 */
class VariableProvider
{
    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService,
    ) {

    }

    /**
     * Provide all variables for email template
     *
     * @param JobOfferAnalyseResultDto $analyseResultDto
     *
     * @return AllVariablesDTO
     */
    public function provide(JobOfferAnalyseResultDto $analyseResultDto): AllVariablesDTO
    {
        $user = $this->jwtAuthenticationService->getUserFromRequest();
        $offerDataVariables = $this->buildOfferVariables($analyseResultDto);
        $userDataVariables  = UserVariablesDTO::fromUserEntity($user);

        $allVariables = new AllVariablesDTO();
        $allVariables->setUser($userDataVariables);
        $allVariables->setJobOffer($offerDataVariables);

        return $allVariables;
    }

    /**
     * Will build {@see JobOfferDataVariablesDTO} from {@see JobOfferAnalyseResultDto}
     * Keep in mind that not all analyse data is being used on purpose.
     *
     * @param JobOfferAnalyseResultDto $analyseResultDto
     *
     * @return JobOfferDataVariablesDTO
     */
    private function buildOfferVariables(JobOfferAnalyseResultDto $analyseResultDto): JobOfferDataVariablesDTO
    {
        $offerVariables = new JobOfferDataVariablesDTO();

        $offerVariables->setCompanyName($analyseResultDto->getCompanyDetail()->getCompanyName() ?? "");
        $offerVariables->setCompanyUrl($analyseResultDto->getCompanyDetail()->getWebsiteUrl() ?? "");
        $offerVariables->setOfferUrl($analyseResultDto->getJobOfferUrl());
        $offerVariables->setOfferTitle($analyseResultDto->getJobTitle());

        return $offerVariables;
    }
}