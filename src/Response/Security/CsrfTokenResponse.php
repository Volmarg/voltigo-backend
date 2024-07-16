<?php


namespace App\Response\Security;

use App\Response\Base\BaseResponse;
use App\Service\TypeProcessor\ArrayTypeProcessor;

/**
 * This response transfers the generated csrf token to the frontend
 * With this the forms submitted via VUE are valid and can be handled by internal symfony logic
 */
class CsrfTokenResponse extends BaseResponse
{

    const KEY_CSRF_TOKEN = "csrfToken";

    /**
     * @var string $csrfToken
     */
    private string $csrfToken = "";

    /**
     * @return string
     */
    public function getCsrfToken(): string
    {
        return $this->csrfToken;
    }

    /**
     * @param string $csrfToken
     */
    public function setCsrfToken(string $csrfToken): void
    {
        $this->csrfToken = $csrfToken;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $json
     * @return static
     */
    public static function fromJson(string $json): CsrfTokenResponse
    {
        $baseApiResponse   = parent::fromJson($json);
        $csrfTokenResponse = CsrfTokenResponse::buildFromBaseApiResponse($baseApiResponse);

        $dataArray = json_decode($json, true);
        $csrfToken = ArrayTypeProcessor::checkAndGetKey($dataArray, self::KEY_CSRF_TOKEN, "");
        $csrfTokenResponse->setCsrfToken($csrfToken);

        return $csrfTokenResponse;
    }

}