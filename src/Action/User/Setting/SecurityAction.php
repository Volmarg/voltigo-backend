<?php

namespace App\Action\User\Setting;

use App\Response\Base\BaseResponse;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Security\PasswordGeneratorService;
use App\Service\Security\UserSecurityService;
use App\Service\Validation\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles user security settings
 */
class SecurityAction extends AbstractController
{
    private const KEY_RAW_PASSWORD = "rawPassword";

    public function __construct(
        private readonly ValidationService        $validationService,
        private readonly TranslatorInterface      $translator,
        private readonly EntityManagerInterface   $entityManager,
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly UserSecurityService      $userSecurityService,
        private readonly PasswordGeneratorService $passwordGeneratorService
    ){}

    /**
     * Saves the user password
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    #[Route("/user/security/password/change", name: "user.base_data.password.change", methods: [Request::METHOD_OPTIONS, Request::METHOD_POST])]
    public function requestEmailChange(Request $request): JsonResponse
    {
        $json = $request->getContent();
        if (!$this->validationService->validateJson($json)) {
            return BaseResponse::buildInvalidJsonResponse()->toJsonResponse();
        }

        $dataArray   = json_decode($json, true);
        $rawPassword = $dataArray[self::KEY_RAW_PASSWORD] ?? null;

        if (empty($rawPassword)) {
            $message = $this->translator->trans('user.settings.security.password.message.empty');
            return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
        }

        if (!$this->passwordGeneratorService->validatePassword($rawPassword)) {
            $message = $this->translator->trans('security.password.message.toWeak');
            return BaseResponse::buildBadRequestErrorResponse($message)->toJsonResponse();
        }

        $user            = $this->jwtAuthenticationService->getUserFromRequest();
        $encodedPassword = $this->userSecurityService->encodeRawPasswordForUserEntity($rawPassword);
        $user->setPassword($encodedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $message = $this->translator->trans('user.settings.security.save.message.ok');
        return BaseResponse::buildOkResponse($message)->toJsonResponse();
    }

}
