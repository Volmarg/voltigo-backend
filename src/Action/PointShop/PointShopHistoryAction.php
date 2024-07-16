<?php

namespace App\Action\PointShop;

use App\Entity\Ecommerce\PointShopProduct;
use App\Entity\Ecommerce\User\UserPointHistory;
use App\Entity\Security\User;
use App\Repository\Ecommerce\User\UserPointHistoryRepository;
use App\Response\PointShop\GetFullPointShopHistoryResponse;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Serialization\ObjectSerializerService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * This class purpose is to provide data related to the {@see UserPointHistory}
 */
#[Route("/point-shop/history/", name: "point_shop.history.", methods: Request::METHOD_OPTIONS)]
class PointShopHistoryAction extends AbstractController
{

    public function __construct(
        private readonly UserPointHistoryRepository $userPointHistoryRepository,
        private readonly JwtAuthenticationService   $jwtAuthenticationService,
        private readonly ObjectSerializerService    $objectSerializerService
    ) {
    }

    /**
     * Will return all entries of type {@see UserPointHistory} for {@see User}
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("get-all", name: "get_all", methods: [Request::METHOD_GET])]
    public function getAll(): JsonResponse
    {
        $response          = GetFullPointShopHistoryResponse::buildOkResponse();
        $user              = $this->jwtAuthenticationService->getUserFromRequest();
        $historyEntries    = $this->userPointHistoryRepository->findAllForUser($user);

        foreach ($historyEntries as $entry) {
            if (!$entry->getPointShopProductSnapshot()) {
                continue;
            }

            /** @var PointShopProduct $pointShopProduct */
            $pointShopProduct = $this->objectSerializerService->fromJson($entry->getPointShopProductSnapshot(), PointShopProduct::class);
            $entry->setProductSnapshotIdentifier($pointShopProduct->getInternalIdentifier());
        }

        $serializedEntries = array_map(
            fn(UserPointHistory $pointHistory) => $this->objectSerializerService->toArray($pointHistory),
            $historyEntries,
        );

        $response->setHistoryEntries($serializedEntries);

        return $response->toJsonResponse();
    }

}