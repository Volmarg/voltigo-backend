<?php

namespace App\Service\UserRegulation;

use App\Entity\Regulation\RegulationData;
use App\Entity\Security\User;
use App\Entity\Security\UserRegulation;
use App\Repository\Regulation\RegulationDataRepository;
use App\Repository\Security\UserRegulationRepository;
use App\Service\Security\JwtAuthenticationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

/**
 * Service for handling {@see RegulationData}
 */
class UserRegulationService
{
    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly UserRegulationRepository $regulationRepository,
        private readonly RegulationDataRepository $regulationDataRepository,
        private readonly JwtAuthenticationService $jwtAuthenticationService
    ) {

    }

    /**
     * Handles accepting regulation data for user, alongside with persisting the accepted content.
     *
     * @param string $regulationIdentifier
     * @param string $hash
     * @param string $regulationContent
     */
    public function accept(string $regulationIdentifier, string $hash, string $regulationContent): void
    {
        if (
                empty($regulationContent)
            ||  empty(strip_tags($regulationContent))
        ) {
            throw new LogicException("Regulation content is empty - has no really usefully text.");
        }

        $user                          = $this->jwtAuthenticationService->getUserFromRequest();
        $existingNotAcceptedRegulation = $this->regulationRepository->findOneBy([
            "identifier" => $regulationIdentifier,
            "accepted"   => 0,
            "user"       => $user,
        ]);

        if ($existingNotAcceptedRegulation) {
            $this->updateNonAcceptedExistingRegulation($hash, $regulationContent, $existingNotAcceptedRegulation);
            return;
        }

        $anyExistingRegulation = $this->regulationRepository->findOneBy([
            "identifier" => $regulationIdentifier,
            "user"       => $user,
        ]);

        if (!empty($anyExistingRegulation)) {
            return;
        }

        $this->createNewUserRegulation(
            $regulationIdentifier,
            $hash,
            $regulationContent,
            $user
        );
    }

    /**
     * Create for user new regulation alongside with its data
     *
     * @param string $regulationIdentifier
     * @param string $hash
     * @param string $regulationContent
     * @param User   $user
     *
     * @return void
     */
    private function createNewUserRegulation(
        string $regulationIdentifier,
        string $hash,
        string $regulationContent,
        User   $user
    ): void {
        $regulation = new UserRegulation();
        $regulation->setIdentifier($regulationIdentifier);
        $regulation->setUser($user);
        $regulation->setAccepted(true);
        $regulation->setAcceptDate(new DateTime());

        $regulationData = $this->getRegulationData($regulationContent, $hash);
        $regulation->setData($regulationData);

        $this->entityManager->persist($regulation);
        $this->entityManager->flush();
    }

    /**
     * Mark given, existing regulation as accepted
     *
     * @param string         $hash
     * @param string         $regulationContent
     *
     * @param UserRegulation $regulation
     */
    private function updateNonAcceptedExistingRegulation(
        string         $hash,
        string         $regulationContent,
        UserRegulation $regulation,
    ): void {
        $regulationData = $this->getRegulationData($regulationContent, $hash);
        $regulation->setAccepted(true);
        $regulation->setAcceptDate(new DateTime());
        $regulation->setData($regulationData);

        $this->entityManager->persist($regulation);
        $this->entityManager->flush();
    }

    /**
     * Either return an already existing regulation data (based on hash) or create new one
     *
     * @param string $regulationContent
     * @param string $hash
     *
     * @return RegulationData
     */
    private function getRegulationData(string $regulationContent, string $hash): RegulationData
    {
        $regulationData = $this->regulationDataRepository->findOneBy([
            "hash" => $hash,
        ]);

        if (!empty($regulationData)) {
            return $regulationData;
        }

        $regulationData = new RegulationData();
        $regulationData->setContent($regulationContent);
        $regulationData->setHash($hash);

        return $regulationData;
    }
}