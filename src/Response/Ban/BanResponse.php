<?php

namespace App\Response\Ban;

use App\Entity\Storage\Ban\BaseBanStorage;
use App\Response\Base\BaseResponse;
use App\Security\Ddos\DdosUserMonitor;
use App\Service\System\Restriction\AccountActivationEmailRequestRestrictionService;
use App\Service\System\Restriction\PasswordResetRestrictionService;
use App\Service\TypeProcessor\ArrayTypeProcessor;

/**
 * Indicates that user was banned
 */
class BanResponse extends BaseResponse
{
    /**
     * Mostly related to {@see DdosUserMonitor},
     * TL:DR; user is banned for accessing the page
     */
    public const BAN_TYPE_IP   = "IP";

    /**
     * Mostly related to {@see DdosUserMonitor},
     * TL:DR; user is banned for accessing the page
     */
    public const BAN_TYPE_USER = "USER";

    /**
     * This happens when user for example spams some button (be it for example password reset)
     * User will not be able to do something until some criteria are matched, see for example:
     * - {@see PasswordResetRestrictionService}
     * - {@see AccountActivationEmailRequestRestrictionService}
     *
     * There is no {@see BaseBanStorage} for this, it's based only on "on-fly" checks
     */
    public const BAN_TYPE_RESOURCE = "RESOURCE";

    private const KEY_REDIRECT_URL     = "redirectUrl";
    private const KEY_ACTIVE_BAN_TYPES = "activeBanTypes";
    private const KEY_VALID_TILL       = "validTill";

    private array $activeBanTypes;
    private string $redirectUrl;
    private ?string $validTill;

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @param string $redirectUrl
     */
    public function setRedirectUrl(string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * @return array
     */
    public function getActiveBanTypes(): array
    {
        return $this->activeBanTypes;
    }

    /**
     * @param array $activeBanTypes
     */
    public function setActiveBanTypes(array $activeBanTypes): void
    {
        $this->activeBanTypes = $activeBanTypes;
    }

    /**
     * @param string $type
     */
    public function addBanType(string $type): void
    {
        $this->activeBanTypes[] = $type;
    }

    /**
     * @return string|null
     */
    public function getValidTill(): ?string
    {
        return $this->validTill;
    }

    /**
     * @param string|null $validTill
     */
    public function setValidTill(?string $validTill): void
    {
        $this->validTill = $validTill;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $json
     * @return static
     */
    public static function fromJson(string $json): BanResponse
    {
        $baseApiResponse = parent::fromJson($json);
        $response        = BanResponse::buildFromBaseApiResponse($baseApiResponse);

        $dataArray      = json_decode($json, true);
        $url            = ArrayTypeProcessor::checkAndGetKey($dataArray, self::KEY_REDIRECT_URL);
        $activeBanTypes = ArrayTypeProcessor::checkAndGetKey($dataArray, self::KEY_ACTIVE_BAN_TYPES);
        $validTill      = ArrayTypeProcessor::checkAndGetKey($dataArray, self::KEY_VALID_TILL);

        $response->setRedirectUrl($url);
        $response->setActiveBanTypes($activeBanTypes);
        $response->setValidTill($validTill);

        return $response;
    }

}