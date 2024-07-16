<?php

namespace App\Response\Api;

use App\Enum\Api\BaseMessageEnum;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Base code used for all the responses sent via API, all existing response classes should extend from this one
 */
class BaseApiResponse
{
    /**
     * @var string $message
     */
    private string $message;

    public function __construct(
        private bool   $success = true,
        private int    $code = Response::HTTP_OK,
        private array  $violations = [],
    ) {
        $this->message = BaseMessageEnum::OK->name;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @return array
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * @param array $violations
     */
    public function setViolations(array $violations): void
    {
        $this->violations = $violations;
    }

    /**
     * Will build generic bad request response
     *
     *
     * @param string $message
     *
     * @return static
     */
    public static function buildBadRequestResponse(string $message): static
    {
        $baseResponse = new static(false, Response::HTTP_BAD_REQUEST);
        $baseResponse->setMessage($message);
        return $baseResponse;
    }

    /**
     * Will build not-found response
     *
     * @param string $message
     *
     * @return static
     */
    public static function buildNotFoundRequestResponse(string $message = "not-found"): static
    {
        $baseResponse = new static(true, Response::HTTP_NOT_FOUND);
        $baseResponse->setMessage($message);
        return $baseResponse;
    }

    /**
     * Will build invalid json response
     *
     * @param string $message
     *
     * @return static
     */
    public static function buildInvalidJsonResponse(string $message = "Invalid json"): static
    {
        $baseResponse = new static(false, Response::HTTP_UNPROCESSABLE_ENTITY);
        $baseResponse->setMessage($message);
        return $baseResponse;
    }

    /**
     * Will build response which indicates that something went wrong and some violations were found
     *
     * @param array $violations
     *
     * @return static
     */
    public static function buildViolationsResponse(array $violations): static
    {
        $baseResponse = new static(false, Response::HTTP_BAD_REQUEST, $violations);
        $baseResponse->setMessage(BaseMessageEnum::BAD_REQUEST->name);
        return $baseResponse;
    }

    /**
     * Will build response which indicates that some internal server error occurred
     *
     * @param string $msg
     *
     * @return static
     */
    public static function buildInternalServerResponse(string $msg = ""): static
    {
        $baseResponse = new static(false, Response::HTTP_INTERNAL_SERVER_ERROR);
        $baseResponse->setMessage($msg ?: BaseMessageEnum::INTERNAL_SERVER_ERROR->name);
        return $baseResponse;
    }

    /**
     * @param int $responseCode
     * @return JsonResponse
     */
    public function toJsonResponse(int $responseCode = Response::HTTP_OK): JsonResponse
    {
        $encoder    = new JsonEncoder();
        $normalizer = new ObjectNormalizer();
        $serializer = new Serializer([$normalizer], [$encoder]);

        $json  = $serializer->serialize($this, "json");
        $array = json_decode($json, true);

        return new JsonResponse($array, $responseCode);
    }

}
