<?php

namespace App\Entity\Regulation;

use App\Entity\Security\UserRegulation;
use App\Entity\Traits\CreatedAndModifiedTrait;
use App\Repository\Regulation\RegulationDataRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RegulationDataRepository::class)
 */
class RegulationData
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
     */
    private string $content;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private string $hash;

    /**
     * @ORM\OneToMany(targetEntity=UserRegulation::class, mappedBy="data", cascade={"persist"})
     */
    private $userRegulations;

    public function __construct()
    {
        $this->initCreatedAndModified();
        $this->userRegulations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return Collection<int, UserRegulation>
     */
    public function getUserRegulations(): Collection
    {
        return $this->userRegulations;
    }

    public function addUserRegulation(UserRegulation $userRegulation): self
    {
        if (!$this->userRegulations->contains($userRegulation)) {
            $this->userRegulations[] = $userRegulation;
            $userRegulation->setData($this);
        }

        return $this;
    }

    public function removeUserRegulation(UserRegulation $userRegulation): self
    {
        if ($this->userRegulations->removeElement($userRegulation)) {
            // set the owning side to null (unless already changed)
            if ($userRegulation->getData() === $this) {
                $userRegulation->setData(null);
            }
        }

        return $this;
    }
}
