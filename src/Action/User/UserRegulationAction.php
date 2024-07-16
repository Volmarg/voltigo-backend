<?php

namespace App\Action\User;

use App\Entity\Security\UserRegulation;
use App\Repository\Security\UserRegulationRepository;
use App\Response\Base\BaseResponse;
use App\Response\User\IsUserRegulationAcceptedResponse;
use App\Service\Security\JwtAuthenticationService;
use App\Service\UserRegulation\UserRegulationService;
use App\Service\Validation\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Handles the logic around {@see UserRegulation}
 */
#[Route("/user-regulation/", name: "user_regulation.", methods: [Request::METHOD_OPTIONS])]
class UserRegulationAction extends AbstractController
{
    public function __construct(
        private readonly UserRegulationRepository $regulationRepository,
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly ValidationService        $validationService,
        private readonly UserRegulationService    $regulationService
    ) {

    }

    /**
     * Will accept given regulation for user
     *
     * @param string $regulationIdentifier
     *
     * @return JsonResponse
     */
    #[Route("check-is-accepted/{regulationIdentifier}", name: "check_is_accepted", methods: [Request::METHOD_GET])]
    public function isAccepted(string $regulationIdentifier): JsonResponse
    {
        $user = $this->jwtAuthenticationService->getUserFromRequest();
        if (!UserRegulation::isRegulationAllowed($regulationIdentifier)) {
            return IsUserRegulationAcceptedResponse::buildBadRequestErrorResponse("This user regulation identifier is not allowed: {$regulationIdentifier}")->toJsonResponse();
        }

        $response = IsUserRegulationAcceptedResponse::buildOkResponse();
        if ($this->regulationRepository->isAccepted($regulationIdentifier, $user)) {
            $response->setAccepted(true);
            return $response->toJsonResponse();
        }

        $response->setAccepted(false);
        return $response->toJsonResponse();
    }

    /**
     * Will accept given regulation for user
     *
     * @param string  $regulationIdentifier
     * @param string  $hash
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route("accept/{regulationIdentifier}/{hash}", name: "accept", methods: [Request::METHOD_POST])]
    public function accept(string $regulationIdentifier, string $hash, Request $request): JsonResponse
    {
        if (!UserRegulation::isRegulationAllowed($regulationIdentifier)) {
            return BaseResponse::buildBadRequestErrorResponse("This user regulation identifier is not allowed: {$regulationIdentifier}")->toJsonResponse();
        }

        $jsonData = $request->getContent();
        if (empty($jsonData)) {
            return BaseResponse::buildBadRequestErrorResponse("Missing request data at all!")->toJsonResponse();
        }

        if (!$this->validationService->validateJson($jsonData)) {
            return BaseResponse::buildBadRequestErrorResponse("Provided json content is not valid")->toJsonResponse();
        }

        $dataArray               = json_decode($jsonData, true);
        $regulationContentBase64 = $dataArray['regulationContentBase64'];
        $regulationContent       = base64_decode($regulationContentBase64);

        $this->regulationService->accept($regulationIdentifier, $hash, $regulationContent);

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }
}