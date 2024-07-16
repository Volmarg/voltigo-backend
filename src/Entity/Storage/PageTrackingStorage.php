<?php

namespace App\Entity\Storage;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Repository\Storage\PageTrackingStorageRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PageTrackingStorageRepository::class)
 */
class PageTrackingStorage implements StorageInterface, EntityInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $ip;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private string $requestContent;

    /**
     * @ORM\Column(type="text")
     */
    private string $requestUri;

    /**
     * Must be nullable because route can be 404, etc.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $routeName = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private string $method;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="pageTrackingStorages")
     */
    private ?user $user;

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

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTime $created;

    /**
     * @ORM\Column(type="datetime", columnDefinition="DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP")
     */
    private ?DateTime $modified;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int|null $responseCode
     */
    private ?int $responseCode;

    public function __construct()
    {
        $this->created  = new DateTime();
        $this->modified = new DateTime();
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

    public function setUser(?User $user): PageTrackingStorage
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

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    /**
     * @param int|null $responseCode
     */
    public function setResponseCode(?int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    /**
     * @return DateTime|null
     */
    public function getModified(): ?DateTime
    {
        return $this->modified;
    }

    /**
     * @param DateTime|null $modified
     */
    public function setModified(?DateTime $modified): void
    {
        $this->modified = $modified;
    }

    /**
     * @return string|null
     */
    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    /**
     * @param string|null $routeName
     */
    public function setRouteName(?string $routeName): void
    {
        $this->routeName = $routeName;
    }

}
