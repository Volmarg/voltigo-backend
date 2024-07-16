<?php

namespace App\Action\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/api", name: "api.")]
class SecurityAction extends AbstractController
{
    /**
     * This route is only used in the Lexit package by some internal code - it crashes without this route
     *
     * @return array
     */
    #[Route('/auth/login', name: 'login', methods: ['POST'])]
    public function apiLogin(): array
    {
        return [];
    }
}