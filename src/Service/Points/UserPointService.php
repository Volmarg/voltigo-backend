<?php

namespace App\Service\Points;

use App\Entity\Security\User;
use App\Response\Points\GrantPointsResponse;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains logic related to any kind of direct manipulation of user points
 * - no ordering process,
 * - no transactions handling etc.
 */
class UserPointService
{
    /**
     * @param UserPointHistoryService  $userPointHistoryService
     * @param EntityManagerInterface   $entityManager
     * @param UserPointsLimiterService $userPointsLimiterService
     * @param TranslatorInterface      $translator
     */
    public function __construct(
        private readonly UserPointHistoryService $userPointHistoryService,
        private readonly EntityManagerInterface  $entityManager,
        private readonly UserPointsLimiterService $userPointsLimiterService,
        private readonly TranslatorInterface $translator
    ) {

    }

    /**
     * Adds user some points:
     * - this does not follow the order process,
     * - this method was created for open-source based handling,
     *
     * @param User $user
     * @param int  $points
     *
     * @return GrantPointsResponse
     * @throws Exception
     */
    public function grantPoints(User $user, int $points): GrantPointsResponse
    {
        $response = GrantPointsResponse::buildOkResponse();
        $response->setMessage($this->translator->trans('points.message.grantInformation.pointsGranted'));

        try {
            $this->entityManager->beginTransaction();

            $pointsBefore = $user->getPointsAmount();
            if (!$this->userPointsLimiterService->canBuyPoints($points)) {
                $response = GrantPointsResponse::buildBadRequestErrorResponse();
                $response->setMessage($this->translator->trans('points.message.grantInformation.maxReached'));

                return $response;
            }

            $user->addPoints($points);
            $this->userPointHistoryService->createAndSave($user, $pointsBefore, $user->getPointsAmount());

            $this->entityManager->commit();

            return $response;
        } catch (Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }
}