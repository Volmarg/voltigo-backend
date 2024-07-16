<?php

namespace App\Security;

use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\Controller\Core\Services;
use App\Controller\Storage\BannedJwtTokenStorageController;
use App\Exception\LogicFlow\UnsupportedDataProvidedException;
use App\Response\Base\BaseResponse;
use App\Service\Logger\LoggerService;
use App\Service\Security\JwtAuthenticationService;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use ReflectionException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Extends the Lexit Bundle authentication logic
 */
class LexitBundleJwtTokenAuthenticator extends JWTTokenAuthenticator
{

    /**
     * @var Services $services
     */
    private Services $services;

    /**
     * @var BannedJwtTokenStorageController $bannedJwtTokenStorageController
     */
    private BannedJwtTokenStorageController $bannedJwtTokenStorageController;

    public function __construct(
        JWTTokenManagerInterface        $jwtManager,
        EventDispatcherInterface        $dispatcher,
        TokenExtractorInterface         $tokenExtractor,
        TokenStorageInterface           $preAuthenticationTokenStorage,
        Services                        $services,
        BannedJwtTokenStorageController $bannedJwtTokenStorageController
    )
    {
        parent::__construct($jwtManager, $dispatcher, $tokenExtractor, $preAuthenticationTokenStorage);
        $this->services                        = $services;
        $this->bannedJwtTokenStorageController = $bannedJwtTokenStorageController;
    }

    /**
     * @param Request $request
     * @return bool
     * @throws ReflectionException
     */
    public function supports(Request $request): bool
    {

       if(
                UriAuthenticator::isUriExcludedFromAuthenticationByRegex() // must be first due to profiler falling in this case yet crashes for other checks (Symfony issue)
           ||   $this->services->getAttributeReaderService()->hasUriAttribute($request->getRequestUri(), JwtAuthenticationDisabledAttribute::class)
       ){
            return false;
        }

        return parent::supports($request);
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $authException
     *
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $authException): JsonResponse
    {
        $apiResponse = new BaseResponse();
        if (JwtAuthenticationService::isJwtTokenException($authException)) {
            $message = $authException->getMessage() ?: $authException::class;
            $apiResponse->setCode(Response::HTTP_UNAUTHORIZED);
            $apiResponse->setMessage($message);
        } else {

            $apiResponse->setCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->services->getLoggerService()->logException($authException);
        }
        return $apiResponse->toJsonResponse();
    }

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     *
     * @return bool
     * @throws UnsupportedDataProvidedException
     * @throws JWTDecodeFailureException
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $request  = Request::createFromGlobals();
        $jwtToken = $this->services->getJwtAuthenticationService()->extractJwtFromRequest();
        if( $this->bannedJwtTokenStorageController->isBanned($jwtToken) ){
            $this->services->getLoggerService()->setLoggerService(LoggerService::LOGGER_HANDLER_SECURITY)->emergency("Someone tried to call route with banned token!", [
                "token"    => $jwtToken,
                "uri"      => $request->getRequestUri(),
                "clientIp" => $request->getClientIp(),
                "content"  => $request->getContent(),
                "method"   => $request->getMethod(),
                "headers"  => $request->headers->all(),
                "query"    => $request->query->all(),
                "request"  => $request->request->all(),
            ]);
          throw new AuthenticationException("This token is banned!");
        }

        if ($user->isDeleted()) {
            throw new AuthenticationException("Deleted user is logged in! He can't do anything anyway: " . $user->getId());
        }

        return parent::checkCredentials($credentials, $user);
    }
}