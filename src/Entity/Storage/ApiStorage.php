<?php

namespace App\Entity\Storage;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Storage\ApiStorageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ApiStorageRepository::class)
 */
class ApiStorage implements StorageInterface, EntityInterface
{
    const CALL_DIRECTION_INCOMING = "INCOMING";
    const CALL_DIRECTION_OUTGOING = "OUTGOING";

    const STATUS_DONE    = "DONE";
    const STATUS_PENDING = "PENDING";
    const EXTERNAL_ERROR = "EXTERNAL_ERROR";
    const INTERNAL_ERROR = "INTERNAL_ERROR";

    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private string $requestContent;

    /**
     * @ORM\Column(type="text")
     */
    private string $requestUri;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $calledApiName;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $ip;

    /**
     * @ORM\Column(type="text", length=50)
     */
    private string $responseContent;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $callDirection;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $status;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $method;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="pageTrackingStorages")
     */
    private ?User $user;

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     */
    private array $headers = [];

    /**
     * @var array
     * @ORM\Column(type="json", nullable=true)
     */
    private array $queryParameters = [];

    /**
     * Contains the `Request -> request -> all`
     *
     * @var array
     * @ORM\Column(type="json", nullable=true)
     */
    private array $requestParameters = [];

    public function __construct()
    {
        $this->status   = self::STATUS_PENDING;
        $this->initCreatedAndModified();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getRequestContent(): ?string
    {
        return $this->requestContent;
    }

    public function setRequestContent(?string $requestContent): self
    {
        $this->requestContent = $requestContent;

        return $this;
    }

    public function getResponseContent(): ?string
    {
        return $this->responseContent;
    }

    public function setResponseContent(string $responseContent): self
    {
        $this->responseContent = $responseContent;

        return $this;
    }

    public function getRequestUri(): ?string
    {
        return $this->requestUri;
    }

    public function setRequestUri(string $requestUri): self
    {
        $this->requestUri = $requestUri;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return ApiStorage
     */
    public function setUser(?User $user): ApiStorage
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method): void
    {
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    /**
     * @param array $queryParameters
     */
    public function setQueryParameters(array $queryParameters): void
    {
        $this->queryParameters = $queryParameters;
    }

    /**
     * @return array
     */
    public function getRequestParameters(): array
    {
        return $this->requestParameters;
    }

    /**
     * @param array $requestParameters
     */
    public function setRequestParameters(array $requestParameters): void
    {
        $this->requestParameters = $requestParameters;
    }

    /**
     * @return string
     */
    public function getCalledApiName(): string
    {
        return $this->calledApiName;
    }

    /**
     * @param string $calledApiName
     */
    public function setCalledApiName(string $calledApiName): void
    {
        $this->calledApiName = $calledApiName;
    }

    /**
     * @return string
     */
    public function getCallDirection(): string
    {
        return $this->callDirection;
    }

    /**
     * @param string $callDirection
     */
    public function setCallDirection(string $callDirection): void
    {
        $this->callDirection = $callDirection;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

}
