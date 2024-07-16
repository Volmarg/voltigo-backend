<?php

namespace App\Vue;

use App\Controller\Core\Env;

/**
 * Handles the vue routes based logic
 */
class VueRoutes
{
    /**
     * Landing page for activating the account
     */
    const ROUTE_PATH_USER_PROFILE_ACTIVATION_CONFIRMATION = "/user/profile-activation-confirmation/:token";

    /**
     * Landing page for use confirming email change
     */
    const ROUTE_PATH_USER_EMAIL_CHANGE_CONFIRMATION = "/user/email-change-confirmation/:token";

    /**
     * Landing page for confirming that profile password should be reset
     */
    const ROUTE_PATH_USER_PROFILE_PASSWORD_RESET_CONFIRMATION = "/user/profile-password-reset-confirmation/:token";

    /**
     * Page with job offers during given search
     */
    const ROUTE_PATH_JOB_SEARCH_RESULT_DETAILS = "/panel/job-offer/search/details/:searchId";

    /**
     * Jwt token
     */
    const ROUTE_PARAMETER_TOKEN = ":token";

    public const ROUTE_PARAM_SEARCH_ID = ":searchId";

    /**
     * Will build frontend url with given path and route parameters
     *
     * @param string $routePath
     * @param array $routeParameters
     * @return string
     */
    public static function buildFrontendUrlForRoute(string $routePath, array $routeParameters): string
    {
        foreach($routeParameters as $parameterName => $parameterValue){
            $routePath = str_replace($parameterName, $parameterValue, $routePath);
        }

        $fullUrl = Env::getFrontendBaseUrl() . DIRECTORY_SEPARATOR . "#" .$routePath;
        return $fullUrl;
    }
}