<?php

namespace App\Controller\Core;

use App\DTO\Internal\DatabaseConnectionDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Handles reading operations from .env file
 */
class Env extends AbstractController {

    const VAR_FRONTEND_BASE_URL         = "FRONTEND_BASE_URL";
    const VAR_MAILER_DSN                = "MAILER_DSN";
    const VAR_DATABASE_URL              = "DATABASE_URL";
    const VAR_WEBSOCKET_PORT            = "WEBSOCKET_PORT";
    const VAR_WEBSOCKET_CONNECTION_URL  = "WEBSOCKET_CONNECTION_URL";
    const VAR_DEBUG                     = "APP_DEBUG";
    const VAR_DEPRECATION_LOGGING       = "APP_DEPRECATION_LOGGING";
    const VAR_IS_EMAIL_MODIFIER_ENABLED = "IS_EMAIL_MODIFIER_ENABLED";
    const VAR_PROJECT_LANDING_PAGE_URL  = "PROJECT_LANDING_PAGE_URL";

    const VAR_IS_JOB_SEARCH_DISABLED = "IS_JOB_SEARCH_DISABLED";

    const VAR_ADMIN_EMAIL = "ADMIN_EMAIL";
    const VAR_ADMIN_NAME  = "ADMIN_NAME";

    const VAR_APP_ENV       = "APP_ENV";
    const APP_ENV_MODE_DEV  = "dev";
    const APP_ENV_MODE_PROD = "prod";

    /**
     * Return frontend base url:
     * - protocol
     * - domain
     * @return string
     */
    public static function getFrontendBaseUrl(): string
    {
        return $_ENV[self::VAR_FRONTEND_BASE_URL];
    }

    /**
     * Return the mailer dsn
     *
     * @return string
     */
    public static function getMailerDsn(): string
    {
        return $_ENV[self::VAR_MAILER_DSN];
    }

    /**
     * Will return admin E-Mail
     *
     * @return string
     */
    public static function getAdminEmail(): string
    {
        return $_ENV[self::VAR_ADMIN_EMAIL];
    }

    /**
     * Will return admin Name
     *
     * @return string
     */
    public static function getAdminName(): string
    {
        return $_ENV[self::VAR_ADMIN_NAME];
    }

    /**
     * Will return the websocket port
     *
     * @return int
     */
    public static function getWebsocketPort(): int
    {
        return (int)$_ENV[self::VAR_WEBSOCKET_PORT];
    }

    /**
     * Will return the websocket port
     *
     * @return string
     */
    public static function getWebsocketConnectionUrl(): string
    {
        return $_ENV[self::VAR_WEBSOCKET_CONNECTION_URL];
    }

    /**
     * Will return url of the project that is basically landing page which presents the tool
     *
     * @return string
     */
    public static function getProjectLandingPageUrl(): string
    {
        return $_ENV[self::VAR_PROJECT_LANDING_PAGE_URL];
    }

    /**
     * Will return database connection dto
     *
     * @return DatabaseConnectionDTO
     */
    public static function getDatabaseConnectionCredentials(): DatabaseConnectionDTO
    {
        $host         = parse_url($_ENV[self::VAR_DATABASE_URL], PHP_URL_HOST);
        $user         = parse_url($_ENV[self::VAR_DATABASE_URL], PHP_URL_USER);
        $port         = parse_url($_ENV[self::VAR_DATABASE_URL], PHP_URL_PORT);
        $password     = parse_url($_ENV[self::VAR_DATABASE_URL], PHP_URL_PASS);
        $databasePath = parse_url($_ENV[self::VAR_DATABASE_URL], PHP_URL_PATH);

        $databaseConnectionDto = new DatabaseConnectionDTO();
        $databaseConnectionDto->setHost($host);
        $databaseConnectionDto->setUser($user);
        $databaseConnectionDto->setPassword($password);
        $databaseConnectionDto->setPort($port);
        $databaseConnectionDto->setDatabaseName($databasePath);

        return $databaseConnectionDto;
    }

    /**
     * Check if the project runs on the production system
     *
     * @return bool
     */
    public static function isProd(): bool
    {
        return ($_ENV[self::VAR_APP_ENV] === self::APP_ENV_MODE_PROD);
    }

    /**
     * Check if the project runs on the development system
     *
     * @return bool
     */
    public static function isDev(): bool
    {
        return ($_ENV[self::VAR_APP_ENV] === self::APP_ENV_MODE_DEV);
    }

    /**
     * Returns the current environment in which the app runs in
     *
     * @return string
     */
    public static function getEnvironment(): string
    {
        return $_ENV[self::VAR_APP_ENV];
    }

    /**
     * Check if app is running in debug mode
     *
     * @return bool
     */
    public static function isDebug(): bool
    {
        $isDebug = $_ENV[self::VAR_DEBUG];
        if (is_string($isDebug)) {
            return ($isDebug === "true");
        }

        return $isDebug;
    }

    /**
     * Check if email template modifiers are enabled
     *
     * @return bool
     */
    public static function isEmailTemplateModifier(): bool
    {
        $isEmailModifierEnabled = $_ENV[self::VAR_IS_EMAIL_MODIFIER_ENABLED];
        if (is_string($isEmailModifierEnabled)) {
            return ($isEmailModifierEnabled === "true");
        }

        return $isEmailModifierEnabled;
    }

    /**
     * Check if deprecations should be logged or not
     *
     * @return bool
     */
    public static function isDeprecationLogging(): bool
    {
        $isDeprecationLogging = $_ENV[self::VAR_DEPRECATION_LOGGING];
        if (is_string($isDeprecationLogging)) {
            return ($isDeprecationLogging === "true");
        }

        return $isDeprecationLogging;
    }

    /**
     * Check if job search functionality is disabled
     *
     * @return bool
     */
    public static function isJobSearchDisabled(): bool
    {
        if (!array_key_exists(self::VAR_IS_JOB_SEARCH_DISABLED, $_ENV)) {
            return false;
        }

        return ("true" === $_ENV[self::VAR_IS_JOB_SEARCH_DISABLED]);
    }
}
