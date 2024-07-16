<?php

namespace App\Response\UploadedFile;

use App\Action\File\UploadedFileAction;
use App\Response\Base\BaseResponse;

/**
 * Response for {@see UploadedFileAction::getCvList()}
 */
class GetUserCvList extends BaseResponse
{
    /**
     * @var array $cvListData
     */
    private array $cvListData;

    /**
     * @return array
     */
    public function getCvListData(): array
    {
        return $this->cvListData;
    }

    /**
     * @param array $cvListData
     */
    public function setCvListData(array $cvListData): void
    {
        $this->cvListData = $cvListData;
    }

}