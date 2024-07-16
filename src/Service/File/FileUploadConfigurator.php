<?php

namespace App\Service\File;

use App\DTO\Internal\Upload\UploadConfigurationDTO;
use App\Enum\File\UploadedFileSourceEnum;
use Exception;

/**
 * Builds the configuration for file uploader (set of rules to be applied both on front and back)
 * It's easier to have the same set of rules defined on front and back in one place
 */
class FileUploadConfigurator
{
    /**
     * These ids must be kept in sync with front, if any will ever get changed then it has to be updated on front too
     * Not using any human friendly names to prevent manipulating it on front
     */
    private const ID_DEVELOPER_PLAYGROUND = "0609f72653203a16b04be016677047d4bc1242e0";
    private const ID_USER_CV_UPLOAD       = "308b18b64b73fdcae6d08c7974469652f8ab7343";
    private const PROFILE_PICTURE_UPLOAD  = "ef61b8bf828699f522861815c9ac8969";
    private const EASY_EMAIL_UPLOAD       = "aca76c769e16ee2f36d755e4bf468188";

    /**
     * Returns upload configuration for given identifier
     *
     * @param string $configurationId
     *
     * @return UploadConfigurationDTO
     * @throws Exception
     */
    public function getConfiguration(string $configurationId): UploadConfigurationDTO
    {
        $configuration =  match ($configurationId) {
            self::ID_DEVELOPER_PLAYGROUND => $this->buildDeveloperPlaygroundConfig(),
            self::ID_USER_CV_UPLOAD       => $this->buildUserCvUploadConfig(),
            self::PROFILE_PICTURE_UPLOAD  => $this->buildProfilePictureUploadConfig(),
            self::EASY_EMAIL_UPLOAD       => $this->buildEasyEmailUploadConfig(),
            default                       => throw new Exception("There is no configuration builder defined for identifier: {$configurationId}")
        };

        $configuration->validateSelf();

        return $configuration;
    }

    /**
     * @return UploadConfigurationDTO
     */
    public function buildUserCvUploadConfig(): UploadConfigurationDTO
    {
        return new UploadConfigurationDTO(
            self::ID_USER_CV_UPLOAD,
            3,
            false,
            true,
            UploadedFileSourceEnum::CV->value,
            ["pdf"],
            ["application/pdf"]
        );
    }

    /**
     * @return UploadConfigurationDTO
     */
    private function buildDeveloperPlaygroundConfig(): UploadConfigurationDTO
    {
        return new UploadConfigurationDTO(
            self::ID_DEVELOPER_PLAYGROUND,
            5,
            true,
            true,
            UploadedFileSourceEnum::CV->value,
            ["jpg", "png", "pdf", "jpge" , "gif", "txt"],
            ["application/pdf"]
        );
    }

    /**
     * @return UploadConfigurationDTO
     */
    private function buildProfilePictureUploadConfig(): UploadConfigurationDTO
    {
        return new UploadConfigurationDTO(
            self::PROFILE_PICTURE_UPLOAD,
            0.5,
            false,
            false,
            UploadedFileSourceEnum::PROFILE_IMAGE->value,
            ["jpg", "png", "jpge" ],
            ["image/png", "image/jpg", "image/jpeg"]
        );
    }

    /**
     * @return UploadConfigurationDTO
     */
    private function buildEasyEmailUploadConfig(): UploadConfigurationDTO
    {
        return new UploadConfigurationDTO(
            self::EASY_EMAIL_UPLOAD,
            0.25,
            false,
            false,
            UploadedFileSourceEnum::EASY_EMAIL->value,
            ["jpg", "png", "jpge" ],
            ["image/png", "image/jpg", "image/jpeg"]
        );
    }
}