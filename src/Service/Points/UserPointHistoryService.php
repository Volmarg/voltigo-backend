<?php

namespace App\Service\Points;

use App\Entity\Ecommerce\Order;
use App\Entity\Ecommerce\User\UserPointHistory;
use App\Entity\Security\User;
use App\Enum\Points\UserPointHistoryTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic related to {@see UserPointHistory}
 */
class UserPointHistoryService
{

    /**
     * @param EntityManagerInterface $entityManager
     * @param TranslatorInterface    $translator
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface    $translator
    ) {
    }

    /**
     * Creates the {@see UserPointHistory} entry based on the {@see Order}
     *
     * @param Order $order
     * @param User  $userBeforeUpdatingPoints
     */
    public function createAndSaveFromOrder(Order $order, User $userBeforeUpdatingPoints): void
    {
        $entity = new UserPointHistory();
        $entity->setType(UserPointHistoryTypeEnum::RECEIVED->name);
        $entity->setRelatedOrder($order);
        $entity->setUser($order->getUser());
        $entity->setAmountBefore($userBeforeUpdatingPoints->getPointsAmount());
        $entity->setAmountNow($order->getUser()->getPointsAmount());
        $entity->setInformation("Granted points from order: {$order->getId()}");

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Creates the {@see UserPointHistory}:
     * - this method does not rely on {@see Order}
     *
     * @param User $user
     * @param int  $pointsBefore
     * @param int  $pointsNow
     */
    public function createAndSave(User $user, int $pointsBefore, int $pointsNow): void
    {
        $entity = new UserPointHistory();
        $entity->setType(UserPointHistoryTypeEnum::RECEIVED->name);
        $entity->setUser($user);
        $entity->setAmountBefore($pointsBefore);
        $entity->setAmountNow($pointsNow);
        $entity->setInformation($this->translator->trans('points.text.grantedViaBasket'));

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

}