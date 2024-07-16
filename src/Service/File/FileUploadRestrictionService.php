<?php

namespace App\Service\File;

use App\Action\User\Setting\BaseDataAction;
use App\DTO\Validation\ValidationResultDTO;
use App\Entity\Ecommerce\Account\AccountType;
use App\Entity\Security\User;
use App\Enum\File\UploadedFileSourceEnum;
use App\Service\Security\JwtAuthenticationService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Don't get confused with {@see FileUploadValidator} as that class is used for validating the file itself toward
 * it's configuration and so on,
 *
 * While THIS class handles checking restriction added to upload process, like for example checking
 * if user has reached limit of max uploaded CV etc.
 *
 * The uploads are handled by switching between {@see UploadedFileSourceEnum} as each source has it's own limitations
 * and if file is marked with this source then it's ALWAYS related to certain logic in project, it won't happen that suddenly
 * CV upload means something in one place, other thing in another one
 */
class FileUploadRestrictionService
{
    /**
     * @return Array<string, Array<string, int>>
     */
    private function getMaxSourceUploadForAccountType(): array
    {
        return [
            UploadedFileSourceEnum::PROFILE_IMAGE->name => null, /** @see BaseDataAction::changeProfileImage()*/
            UploadedFileSourceEnum::CV->name => [
                AccountType::TYPE_FREE                => 1,
                AccountType::TYPE_MEMBERSHIP_STANDARD => 3 ,
            ]
        ];
    }

    public function __construct(
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly TranslatorInterface      $translator
    ){}

    /**
     * @param UploadedFileSourceEnum $sourceEnum
     *
     * @return ValidationResultDTO
     */
    public function validate(UploadedFileSourceEnum $sourceEnum): ValidationResultDTO
    {
        $user   = $this->jwtAuthenticationService->getUserFromRequest();
        $max    = $this->getAllowedMax($sourceEnum, $user);
        $active = $this->getCountOfActiveCv($user, $sourceEnum);

        $result = new ValidationResultDTO();
        $result->setSuccess(true);

        if (
                !is_null($max)
            &&  $active >= $max
        ) {
            $message = $this->translator->trans('file.upload.put.message.maxUploadReached');
            $result->setSuccess(false);
            $result->setMessage($message);
        }

        return $result;

    }

    /**
     * Get max allowed uploaded file of given resource.
     *
     * Returns int / null
     * Int means that there is a limit defined,
     * Null means that there is no limit
     *
     * @param UploadedFileSourceEnum $fileSourceEnum
     * @param User                   $user
     *
     * @return int|null
     */
    private function getAllowedMax(UploadedFileSourceEnum $fileSourceEnum, User $user): ?int
    {
        $maxPerSourceAndAccount = $this->getMaxSourceUploadForAccountType();
        $accountType            = $user->getAccount()->getType()->getName();
        if (!array_key_exists($fileSourceEnum->name, $maxPerSourceAndAccount)) {
            return null;
        }

        $maxForSource = $maxPerSourceAndAccount[$fileSourceEnum->name];

        // same value for all account types
        if (
                is_numeric($maxForSource)
            ||  is_null($maxForSource)
        ) {
            return $maxForSource;
        }

        // no limit for this account type
        if (!array_key_exists($accountType, $maxForSource)) {
            return null;
        }

        $limit = $maxForSource[$accountType];

        return $limit;
    }

    /**
     * Returns count of currently stored files of given source
     *
     * @param User                   $user
     * @param UploadedFileSourceEnum $fileSourceEnum
     *
     * @return int
     */
    private function getCountOfActiveCv(User $user, UploadedFileSourceEnum $fileSourceEnum): int
    {
        return $user->getUploadedFilesBySource($fileSourceEnum)->count();
    }
}