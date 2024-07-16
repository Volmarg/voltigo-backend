<?php

namespace App\Action\Debug\MessageHub;

use App\Response\Base\BaseResponse;
use App\Service\Api\MessageHub\MessageHubService;
use App\Service\Serialization\ObjectSerializerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use symfony\Component\Routing\Annotation\Route;

class MessageHubDebugAction
{
    public function __construct(
        private readonly MessageHubService       $messageHubService,
        private readonly ObjectSerializerService $objectSerializerService
    ){}

    #[Route("/debug/message-hub/insert-email", name: "debug.message.hub.insert-email", methods: [Request::METHOD_GET])]
    public function insertEmail(): JsonResponse
    {
        $messageHubResponse = $this->messageHubService->insertMail(
            "subject",
            "body",
            ['admin@admin.admin']
        );


        $baseResponse = BaseResponse::buildOkResponse();
        $baseResponse->setData([
            'messageHubResponse' => $this->objectSerializerService->toArray($messageHubResponse),
        ]);

        return $baseResponse->toJsonResponse();
    }
}