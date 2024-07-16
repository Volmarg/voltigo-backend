<?php

namespace App\Controller\Email;

use App\Entity\Email\Email;
use App\Repository\Email\EmailRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Handles email related logic like:
 *  - saving emails in db,
 *  - forwarding emails to sender,
 *  - removing emails,
 *
 * This class is NOT sending emails to the companies on its own, it will only forward it further to the sending tool
 */
class EmailController
{

    public function __construct(
       private EmailRepository $emailRepository
    ){}

    /**
     * Will save the entity (create or update the existing one)
     *
     * @param Email $email
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Email $email): void
    {
        $this->emailRepository->save($email);;
    }

    /**
     * Will return all E-mails that has been sent
     *
     * @return Email[]
     */
    public function getAllSentEmails(): array
    {
        return $this->emailRepository->getAllSentEmails();
    }

}