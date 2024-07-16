<?php

namespace App\Action\Debug;

use App\DTO\RabbitMq\Producer\JobSearch\Start\ParameterBag;
use App\Entity\Security\User;
use App\Response\Base\BaseResponse;
use App\Service\RabbitMq\JobSearcher\JobSearchStartProducerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Special class containing logic for testing generic things, either dev or prod (not accessible for standard users)
 *
 * Class DebugAction
 * @package App\Action
 */
#[Route("/debug", name: "debug_")]
class DebugAction extends AbstractController
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ){}

    /**
     * General route with some test logic
     *
     * @return JsonResponse
     */
    #[Route("/ping", name: "ping")]
    public function ping(): JsonResponse
    {
        return BaseResponse::buildOkResponse("Ok")->toJsonResponse();
    }

    /**
     * General route for testing rabbit MQ
     *
     * @param JobSearchStartProducerService $jobSearcherProducerService
     *
     * @return JsonResponse
     */
    #[Route("/test/rabbit-mq/producer", name: "test.rabbit.mq.producer")]
    public function testRabbitMq(JobSearchStartProducerService $jobSearcherProducerService): JsonResponse
    {
        $parameterBag = new ParameterBag();
        $parameterBag->setCountry("deu");
        $parameterBag->setMaxPaginationPage(0);
        $parameterBag->setKeywords("lkw");
        $parameterBag->setLocationName("dresden");
        $parameterBag->setSearchId(1);
        $jobSearcherProducerService->produce($parameterBag);

        return BaseResponse::buildOkResponse("Ok")->toJsonResponse();
    }

    /**
     * Generic function for testing
     */
    #[Route("/test")]
    public function test(): never {
        $user = $this->entityManager->find(User::class, 6);
        $user->setDeleted(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        die();
    }
}