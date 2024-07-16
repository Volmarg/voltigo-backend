<?php

namespace App\Controller\Security;

use App\Controller\Core\Services;
use App\Entity\Security\User;
use App\Repository\Security\UserRepository;
use App\Vue\VueRoutes;
use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class UserController
 * @package App\Controller
 */
class UserController extends AbstractController
{

    /**
     * @var UserRepository $userRepository
     */
    private UserRepository $userRepository;

    /**
     * @var Services $services
     */
    private Services $services;

    /**
     * UserController constructor.
     *
     * @param UserRepository $userRepository
     * @param Services $services
     */
    public function __construct(
        UserRepository $userRepository,
        Services       $services
    )
    {
        $this->userRepository = $userRepository;
        $this->services       = $services;
    }

    /**
     * Will return one user for email (is unique) or null if nothing is found
     *
     * @param string $email
     * @return User|null
     */
    public function getOneByEmail(string $email): ?User
    {
        $user = $this->userRepository->getOneByEmail($email);
        return $user;
    }

    /**
     * Will return user by given id (if is not soft deleted)
     *
     * @param string $id
     * @return User|null
     */
    public function getOneById(string $id): ?User
    {
        $user = $this->userRepository->getOneById($id);
        return $user;
    }

    /**
     * Will return logged in user - not just the UserInterface but the actual entity from database with all its data
     * If user is not logged in then null is returned
     */
    public function getLoggedInUser(): ?User
    {
        $loggedInBaseUserInterface = $this->getUser();
        if( empty($loggedInBaseUserInterface) ){
            return null;
        }

        $userEntity = $this->getOneByEmail($loggedInBaseUserInterface->getUserIdentifier());
        return $userEntity;
    }

    /**
     * Will either create new record in db or update existing one
     *
     * @param UserInterface $user
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(UserInterface $user): void
    {
        $this->userRepository->save($user);
    }

    /**
     * Will return all users
     *
     * @return User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->getAllUsers();
    }

    /**
     * Will remove the user
     *
     * @param User $user
     * @return bool
     */
    public function softDeleteUser(User $user): bool
    {
        try{
            $user->setDeleted(true);
            $this->userRepository->save($user);
            return true;
        }catch(Exception $e){
            $this->services->getLoggerService()->logException($e);
            return false;
        }
    }

    /**
     * Will generate link for user removal - link will be valid as long as the token payload allows for it
     *
     * @param User $user
     * @return string
     * @throws JWTDecodeFailureException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function generateResetPasswordLink(User $user): string
    {
        $url = VueRoutes::buildFrontendUrlForRoute(VueRoutes::ROUTE_PATH_USER_PROFILE_PASSWORD_RESET_CONFIRMATION, [
            VueRoutes::ROUTE_PARAMETER_TOKEN => $this->services->getJwtAuthenticationService()->buildWithRolesForUser(
                $user,
                [User::RIGHT_USER_RESET_PASSWORD],
                false
            ),
        ]);

        return $url;
    }

    /**
     * Will generate link for user removal - link will be valid as long as the token payload allows for it
     *
     * @param User $user
     * @return string
     * @throws JWTDecodeFailureException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function generateActivateUserLink(User $user): string
    {
        $url = VueRoutes::buildFrontendUrlForRoute(VueRoutes::ROUTE_PATH_USER_PROFILE_ACTIVATION_CONFIRMATION, [
            VueRoutes::ROUTE_PARAMETER_TOKEN => $this->services->getJwtAuthenticationService()->buildWithRolesForUser(
                $user,
                [User::RIGHT_USER_ACTIVATE_ACCOUNT],
                false
            )
        ]);

        return $url;
    }

    /**
     * Will update user based on the jwt token which is used to extract user from it
     *
     * @param string|null $jwtToken
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateUserActivityFromJwtToken(?string $jwtToken): void
    {
        if( !empty($jwtToken) ){
            $user = $this->services->getJwtAuthenticationService()->getUserForToken($jwtToken);
            if( !empty($user) ){
                $user->setLastActivity(new DateTime());
                $this->save($user);
            }
        }
    }
}