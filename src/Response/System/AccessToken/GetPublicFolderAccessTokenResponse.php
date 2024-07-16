<?php

namespace App\Response\System\AccessToken;

use App\Action\System\SystemAccessTokenAction;
use App\Response\Base\BaseResponse;

/**
 * Response for {@see SystemAccessTokenAction::getPublicFolderAccessToken()}
 */
class GetPublicFolderAccessTokenResponse extends BaseResponse
{
    private string $publicFolderAccessToken;

    /**
     * @return string
     */
    public function getPublicFolderAccessToken(): string
    {
        return $this->publicFolderAccessToken;
    }

    /**
     * @param string $publicFolderAccessToken
     */
    public function setPublicFolderAccessToken(string $publicFolderAccessToken): void
    {
        $this->publicFolderAccessToken = $publicFolderAccessToken;
    }

}