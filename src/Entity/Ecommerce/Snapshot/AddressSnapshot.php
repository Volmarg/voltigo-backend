<?php

namespace App\Entity\Ecommerce\Snapshot;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Enum\Address\CountryEnum;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\Ecommerce\Snapshot\AddressSnapshotRepository;

/**
 * @ORM\Entity(repositoryClass=AddressSnapshotRepository::class)
 */
class AddressSnapshot implements EntityInterface
{
    use CreatedAndModifiedTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $zip;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $street;

    /**
     * @ORM\Column(type="string", length=255)
     * @Encrypted
     */
    private string $city;

    /**
     * @ORM\Column(type="text")
     * @Encrypted
     */
    private string $homeNumber;

    /**
     * @ORM\Column(type="string", length=255, enumType=CountryEnum::class)
     */
    private CountryEnum $country;

    /**
     * @ORM\OneToOne(targetEntity=UserDataSnapshot::class, inversedBy="addressSnapshot", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private UserDataSnapshot $user;

    public function __construct()
    {
        $this->initCreatedAndModified();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getHomeNumber(): string
    {
        return $this->homeNumber;
    }

    /**
     * @param string $homeNumber
     */
    public function setHomeNumber(string $homeNumber): void
    {
        $this->homeNumber = $homeNumber;
    }

    /**
     * @return CountryEnum
     */
    public function getCountry(): CountryEnum
    {
        return $this->country;
    }

    /**
     * @param CountryEnum $country
     */
    public function setCountry(CountryEnum $country): void
    {
        $this->country = $country;
    }

    public function getUser(): ?UserDataSnapshot
    {
        return $this->user;
    }

    public function setUser(UserDataSnapshot $user): self
    {
        $this->user = $user;

        return $this;
    }

}
