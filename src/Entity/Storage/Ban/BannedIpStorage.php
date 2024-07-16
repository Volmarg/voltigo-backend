<?php

namespace App\Entity\Storage\Ban;

use App\Entity\Interfaces\EntityInterface;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\Storage\Ban\BannedIpStorageRepository;

/**
 * @ORM\Entity(repositoryClass=BannedIpStorageRepository::class)
 * @ORM\MappedSuperclass()
 */
class BannedIpStorage extends BaseBanStorage implements EntityInterface
{
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private string $ip;

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
    }

}