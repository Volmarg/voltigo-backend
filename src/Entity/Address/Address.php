<?php

namespace App\Entity\Address;

use Ambta\DoctrineEncryptBundle\Configuration\Encrypted;
use App\DTO\Security\RegisterDataDTO;
use App\Entity\Interfaces\EntityInterface;
use App\Entity\Security\User;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Enum\Address\CountryEnum;
use App\Repository\Address\AddressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AddressRepository::class)
 */
class Address implements EntityInterface
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
     * @ORM\Column(type="text")
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
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="address")
     */
    private $users;

    public function __construct()
    {
        $this->initCreatedAndModified();
        $this->users = new ArrayCollection();
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
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setAddress($this);
        }

        return $this;
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

    /**
     * @param RegisterDataDTO $registrationData
     *
     * @return Address
     */
    public static function buildFromRegisterDto(RegisterDataDTO $registrationData): Address
    {
        $country = CountryEnum::from(strtoupper($registrationData->getCountry()));

        $address = new Address();
        $address->setCity($registrationData->getCity());
        $address->setHomeNumber($registrationData->getHomeNumber());
        $address->setStreet($registrationData->getStreet());
        $address->setZip($registrationData->getZip());
        $address->setCountry($country);

        return $address;
    }

}
