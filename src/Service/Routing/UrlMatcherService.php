<?php

namespace App\Service\Routing;

use App\Service\Logger\LoggerService;
use Exception;
use Monolog\Logger;
use Symfony\Bundle\WebProfilerBundle\Controller\ProfilerController;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * This class handles logic for finding matching controllers/methods for given url
 *
 * Class UrlMatcherService
 * @package App\Service\Routing
 */
class UrlMatcherService
{

    const URL_MATCHER_RESULT_CONTROLLER_WITH_METHOD = "_controller";
    const URL_MATCHER_RESULT_ROUTE                  = "_route";
    const URL_PROFILER_URI                          = "_wdt";
    const CLASS_METHOD_SEPARATION_CHARACTER         = "::";


    /**
     * @var UrlMatcherInterface $urlMatcher
     */
    private UrlMatcherInterface $urlMatcher;

    public function __construct(
        UrlMatcherInterface            $urlMatcher,
        private readonly LoggerService $loggerService
    )
    {
        $this->urlMatcher = $urlMatcher;
    }

    /**
     * Will return `class:method` for given url or null if nothing was found
     *
     * @param string $uri
     * @return string|null
     */
    public function getClassAndMethodForCalledUrl(string $uri): ?string
    {
        try{
            $dataArray            = $this->urlMatcher->match($uri);
            $route                = $dataArray[self::URL_MATCHER_RESULT_ROUTE];
            $controllerWithMethod = $dataArray[self::URL_MATCHER_RESULT_CONTROLLER_WITH_METHOD];

            /**
             * This check is required as the matcher returns controllerWithMethod defined in `yml` file that is provided with profiler
             */
            if(self::URL_PROFILER_URI === $route){
                $controllerWithMethodPartials = explode(self::CLASS_METHOD_SEPARATION_CHARACTER, $controllerWithMethod);
                $controller                   = ProfilerController::class;
                $method                       = $controllerWithMethodPartials[1];
                $controllerWithMethod         = $controller . self::CLASS_METHOD_SEPARATION_CHARACTER . $method;
            }
        }catch(Exception $e){
            $this->loggerService->logException($e, [
                "No class with method was found for url", [
                    "url" => $uri,
                ]
            ], Logger::WARNING);
            return null;
        }

        return $controllerWithMethod;
    }

    /**
     * Will return matching route for called uri
     *
     * @param string $uri
     * @return string|null
     */
    public function getRouteForCalledUri(string $uri): ?string
    {
        try{
            $uriWithoutQueryParams = preg_replace("#\?.*#", "", $uri);

            $dataArray = $this->urlMatcher->match($uriWithoutQueryParams);
            $route     = $dataArray[self::URL_MATCHER_RESULT_ROUTE];
        }catch(Exception){
            $this->loggerService->warning("No route found for called uri", [
                "uri" => $uri,
            ]);
            return null;
        }

        return $route;
    }
}