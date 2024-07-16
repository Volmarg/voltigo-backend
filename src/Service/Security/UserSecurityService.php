<?php


namespace App\Service\Security;

use App\Entity\Security\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Consist of logic related to user authentication
 *
 * Class UserSecurityService
 * @package App\Service\Security
 */
class UserSecurityService
{

    /**
     * @var UserPasswordHasherInterface $userPasswordHasher
     */
    private UserPasswordHasherInterface $userPasswordHasher;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * UserSecurityService constructor.
     *
     * @param UserPasswordHasherInterface $userPasswordEncoder
     * @param LoggerInterface $logger
     */
    public function __construct(UserPasswordHasherInterface $userPasswordEncoder, LoggerInterface $logger)
    {
        $this->userPasswordHasher = $userPasswordEncoder;
        $this->logger             = $logger;
    }

    /**
     * Will encode plain password for standard login user interface
     *
     * @param string $plainPassword
     * @return string
     */
    public function encodeRawPasswordForUserEntity(string $plainPassword): string
    {
        // it's required to use even blank user entity to fetch the encoder from it
        $user = new User();
        return $this->encodePasswordForUserInterface($user, $plainPassword);
    }

    /**
     * @param string $plainPassword
     * @param PasswordAuthenticatedUserInterface $user
     * @return bool
     */
    public function validatePasswordForUser(string $plainPassword, PasswordAuthenticatedUserInterface $user): bool
    {
        $isPasswordValid = $this->userPasswordHasher->isPasswordValid($user, $plainPassword);
        return $isPasswordValid;
    }

    /**
     * Will encode plain password for user interface
     *
     * @param PasswordAuthenticatedUserInterface $user
     * @param string $plainPassword
     * @return string
     */
    private function encodePasswordForUserInterface(PasswordAuthenticatedUserInterface $user, string $plainPassword): string
    {
        $encodedPassword = $this->userPasswordHasher->hashPassword($user, $plainPassword);
        return $encodedPassword;
    }
}