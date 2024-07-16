<?php

namespace App\Service\PointShop;

use App\Entity\Ecommerce\PointShopProduct;
use App\Entity\Ecommerce\User\UserPointHistory;
use App\Entity\Security\User;
use App\Enum\Points\Shop\JobOfferSearchProductIdentifierEnum;
use App\Enum\Points\UserPointHistoryTypeEnum;
use App\Exception\NotFoundException;
use App\Exception\Payment\PointShop\NotEnoughPointsException;
use App\Repository\Ecommerce\PointShopProductRepository;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Serialization\ObjectSerializerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;

/**
 * This class handles buying (spending) the internal points, and any logic related to it, like for example:
 * - handling {@see UserPointHistory},
 * - updating {@see User::$pointsAmount}
 */
class PointShopProductPaymentService
{

    public function __construct(
        private readonly PointShopProductRepository $pointShopProductRepository,
        private readonly EntityManagerInterface     $entityManager,
        private readonly JwtAuthenticationService   $jwtAuthenticationService,
        private readonly ObjectSerializerService    $objectSerializerService,
        private readonly LoggerInterface            $logger,
        private readonly TranslatorInterface        $translator
    ) {
    }

    /**
     * @param int|null $limit
     *
     * @return JobOfferSearchProductIdentifierEnum|null
     */
    public static function mapSearchLimitToProductId(?int $limit): ?JobOfferSearchProductIdentifierEnum
    {
        return match($limit){
            30      => JobOfferSearchProductIdentifierEnum::JOB_SEARCH_TAG_LIMIT_30,
            0, null => JobOfferSearchProductIdentifierEnum::JOB_SEARCH_TAG_NO_LIMIT,
            default => throw new LogicException("This search limit is not supported: {$limit}")
        };
    }

    /**
     * Handles buying something from point shop
     *
     * @param string      $productIdentifier
     * @param int         $quantity
     * @param array       $extraData
     * @param array       $internalData
     * @param string|null $appendedInformation
     *
     * @return UserPointHistory
     * @throws NotEnoughPointsException
     * @throws NotFoundException
     */
    public function buy(
        string  $productIdentifier,
        int     $quantity,
        array   $extraData = [],
        array   $internalData = [],
        ?string $appendedInformation = null
    ): UserPointHistory
    {
        $user    = $this->jwtAuthenticationService->getUserFromRequest();
        $product = $this->pointShopProductRepository->findByInternalIdentifier($productIdentifier);
        if (empty($product)) {
            throw new NotFoundException("No point shop product was found for identifier: {$productIdentifier}");
        }

        $this->canBuy($product, $user, $quantity);
        $currentPointsAmount = $user->getPointsAmount();

        $this->entityManager->beginTransaction();
        try {
            $this->reduceUserPoints($product, $user, $quantity);
            $userPointHistory = $this->createPointHistoryEntry(
                $product,
                $currentPointsAmount,
                $quantity,
                $extraData,
                $internalData,
                $appendedInformation
            );

            $this->entityManager->persist($userPointHistory);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->commit();

        } catch (Exception|TypeError $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $userPointHistory;
    }

    /**
     * Will create {@see UserPointHistory} used for letting user know what happened with his points over time.
     * Info: try not to add more params, if this needs to be expanded even further then try to reduce params somehow
     *
     * @param PointShopProduct $product
     * @param int              $pointsAmountBefore
     * @param int              $quantity
     * @param array            $extraData
     * @param array            $internalData
     * @param string|null      $appendedInformation
     *
     * @return UserPointHistory
     */
    private function createPointHistoryEntry(
        PointShopProduct $product,
        int              $pointsAmountBefore,
        int              $quantity,
        array            $extraData,
        array            $internalData,
        ?string          $appendedInformation = null
    ): UserPointHistory
    {
        $user            = $this->jwtAuthenticationService->getUserFromRequest();
        $productSnapshot = $this->objectSerializerService->toJson($product);

        $userPointHistory = new UserPointHistory();
        $userPointHistory->setUser($user);
        $userPointHistory->setPointShopProductSnapshot($productSnapshot);
        $userPointHistory->setAmountBefore($pointsAmountBefore);
        $userPointHistory->setAmountNow($user->getPointsAmount());
        $userPointHistory->setType(UserPointHistoryTypeEnum::USED->name);
        $userPointHistory->setExtraData($extraData);
        $userPointHistory->setInternalData($internalData);

        $information = "{$product->getName()} x {$quantity}";
        if (!empty($appendedInformation)) {
            $information .= " | {$appendedInformation}";
        }

        $userPointHistory->setInformation($information);

        return $userPointHistory;
    }

    /**
     * Will take away part of user point, based on the bought product
     *
     * @param PointShopProduct $product
     * @param User             $user
     * @param int              $quantity
     */
    private function reduceUserPoints(PointShopProduct $product, User $user, int $quantity): void
    {
        $fee  = $product->getCost() * $quantity;
        $user->decreasePoints($fee);
    }

    /**
     * Check if user can buy given product at all
     *
     * @param PointShopProduct $product
     * @param User             $user
     * @param int              $quantity
     *
     * @throws NotEnoughPointsException
     */
    private function canBuy(PointShopProduct $product, User $user, int $quantity): void
    {
        $fee  = $product->getCost() * $quantity;
        if ($user->getPointsAmount() < $fee) {
            $msg = "User: {$user->getId()}, got not enough points ({$user->getPointsAmount()}) for product: {$product->getId()}";
            $this->logger->critical($msg);

            // message from this exception will be shown to user on front
            throw new NotEnoughPointsException($this->translator->trans('payment.message.notEnoughPoints'), Response::HTTP_BAD_REQUEST);
        }
    }
}