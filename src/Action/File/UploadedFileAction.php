<?php

namespace App\Action\File;

use App\Exception\Security\OtherUserResourceAccessException;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use App\DTO\Internal\Upload\UploadConfigurationDTO;
use App\Entity\File\UploadedFile;
use App\Enum\File\UploadedFileSourceEnum;
use App\Enum\File\UploadStatusEnum;
use App\Enum\Service\Serialization\SerializerType;
use App\Exception\File\UploadRestrictionException;
use App\Exception\File\UploadValidationException;
use App\Exception\Security\MaliciousFileException;
use App\Repository\File\UploadedFileRepository;
use App\Response\Base\BaseResponse;
use App\Response\UploadedFile\GetUserCvList;
use App\Response\UploadedFile\UploadConfigurationResponse;
use App\Response\UploadedFile\UploadResponse;
use App\Service\File\FileUploadConfigurator;
use App\Service\File\FileUploadService;
use App\Service\Logger\LoggerService;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Serialization\ObjectSerializerService;
use App\Service\Validation\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;

/**
 * Provides endpoints for uploaded file related logic
 */
class UploadedFileAction extends AbstractController
{

    public function __construct(
        private readonly UploadedFileRepository   $uploadedFileRepository,
        private readonly JwtAuthenticationService $jwtAuthenticationService,
        private readonly ObjectSerializerService  $objectSerializerService,
        private readonly ValidationService        $validationService,
        private readonly FileUploadService        $fileUploadService,
        private readonly LoggerService            $loggerService,
        private readonly FileUploadConfigurator   $fileUploadConfigurator,
        private readonly EntityManagerInterface   $entityManager,
        private readonly TranslatorInterface      $translator
    ) {
    }

    /**
     * Provides user uploaded cvs
     *
     * @return JsonResponse
     */
    #[Route("/get-user-cv-list", name: "user_cv_list_get", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getCvList(): JsonResponse
    {
        $user  = $this->jwtAuthenticationService->getUserFromRequest();
        $files = $this->uploadedFileRepository->findForUserBySource($user, UploadedFileSourceEnum::CV);

        $mappingCallback = function(UploadedFile $file) {
            return $this->objectSerializerService->toArray($file, SerializerType::STANDARD, [UploadedFile::SERIALIZATION_GROUP_BASE_DATA]);
        };

        $filesDataArray    = array_map($mappingCallback, $files);
        $jobOffersResponse = GetUserCvList::buildOkResponse();
        $jobOffersResponse->setCvListData($filesDataArray);

        return $jobOffersResponse->toJsonResponse();
    }

    /**
     * Handles the file upload.
     * Keep in mind that trailing slash in route is A MUST, had already issues with that.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/upload/", name: "upload", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function upload(Request $request): JsonResponse
    {
        $json = $request->getContent();
        if (!$this->validationService->validateJson($json)) {
            throw new Exception("This is not a valid json: " . $json);
        }

        $data            = json_decode($json, true);
        $encodedContent  = $data['fileContent'];
        $uploadId        = $data['uploadId'] ?? null;
        $fileName        = $data['fileName'];
        $fileSizeBytes   = $data['fileSize'];
        $uploadConfigId  = $data['uploadConfigId'];
        $userDefinedName = $data['userDefinedName'] ?? null; //due to being optional
        $fileContent     = base64_decode($encodedContent);

        $response = $this->handleFileUpload(
            $fileContent,
            $fileName,
            $uploadConfigId,
            $userDefinedName,
            $fileSizeBytes,
            $uploadId
        );

        return $response->toJsonResponse();
    }

    /**
     * Returns the serialized {@see UploadConfigurationDTO} from {@see FileUploadConfigurator}
     *
     * @param string $id
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/upload/get-configuration/{id}", name: "upload.get.configuration", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function getUploadConfiguration(string $id): JsonResponse
    {
        $configurationDto = $this->fileUploadConfigurator->getConfiguration($id);
        $response         = UploadConfigurationResponse::buildOkResponse();
        $response->setConfiguration($configurationDto);

        return $response->toJsonResponse();
    }

    /**
     * Will update the give file data
     *
     * @param UploadedFile $uploadedFile
     * @param Request      $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/upload/update/{id}", name: "upload.update", methods: [Request::METHOD_POST, Request::METHOD_OPTIONS])]
    public function update(UploadedFile $uploadedFile, Request $request): JsonResponse
    {
        $json = $request->getContent();
        if (!$this->validationService->validateJson($json)) {
            throw new Exception("This is not a valid json: " . $json);
        }

        $uploadedFile->ensureBelongsToUser($this->jwtAuthenticationService->getUserFromRequest());

        $data            = json_decode($json, true);
        $userDefinedName = $data['userDefinedName'] ?? null; //due to being optional

        $uploadedFile->setUserBasedName($userDefinedName);

        $this->entityManager->persist($uploadedFile);
        $this->entityManager->flush();

        return BaseResponse::buildOkResponse()->toJsonResponse();
    }

    /**
     * Will remove given file
     *
     * @param UploadedFile $uploadedFile
     *
     * @return JsonResponse
     *
     * @throws OtherUserResourceAccessException
     */
    #[Route("/upload/delete/{uploadedFile}", name: "upload.delete", methods: [Request::METHOD_GET, Request::METHOD_OPTIONS])]
    public function delete(UploadedFile $uploadedFile): JsonResponse
    {
        $message   = $this->translator->trans('file.upload.remove.message.fileRemoved');
        $response  = BaseResponse::buildOkResponse();
        $response->setMessage($message);

        $uploadedFile->ensureBelongsToUser($this->jwtAuthenticationService->getUserFromRequest());

        $isRemoved = $this->fileUploadService->deleteFile($uploadedFile);
        if (!$isRemoved) {
            $message = $this->translator->trans('file.upload.remove.message.couldNotRemove');
            $response->prefillBaseFieldsForBadRequestResponse();
            $response->setMessage($message);
        }

        return $response->toJsonResponse();
    }

    /**
     * Handles the file upload alongside with the exceptions
     *
     * @param string      $fileContent
     * @param string      $fileName
     * @param string      $uploadConfigId
     * @param string|null $userDefinedName
     * @param float       $fileSizeBytes
     * @param string|null $uploadId - this can be used to track upload response on front, that's just some random uuid value
     *
     * @return UploadResponse
     */
    private function handleFileUpload(
        string  $fileContent,
        string  $fileName,
        string  $uploadConfigId,
        ?string $userDefinedName,
        float   $fileSizeBytes,
        ?string $uploadId
    ): UploadResponse
    {
        $errorMessage = $this->translator->trans('file.upload.put.message.genericError');
        $isError      = false;
        $status       = UploadStatusEnum::SUCCESS;
        $response     = UploadResponse::buildOkResponse();

        try {
            $uploadedFile       = $this->fileUploadService->saveInTemporaryDir($fileContent, $fileName);
            $uploadedFileEntity = $this->fileUploadService->handleUpload($uploadedFile, $uploadConfigId, $userDefinedName, $fileSizeBytes);

            $response->setLocalFileName($uploadedFileEntity->getLocalFileName());
            $response->setPublicPath($uploadedFileEntity->getPublicPath() ?? '');
            $response->setUploadId($uploadId);
        } catch (MaliciousFileException $mfe) {
            $status  = UploadStatusEnum::MALICIOUS;
            $isError = true;
            $this->loggerService->logException($mfe);
        } catch (UploadRestrictionException $uve) {
            $status       = UploadStatusEnum::ERROR;
            $errorMessage = $uve->getValidationResult()->getMessage();
            $isError      = true;
        } catch (UploadValidationException $uve) {
            $status  = UploadStatusEnum::ERROR;
            $isError = true;
        } catch (Exception|TypeError $e) {
            $status = UploadStatusEnum::ERROR;
            $this->loggerService->logException($e);
            $isError = true;
        } finally {
            $passedUploadedFile = ($uploadedFile ?? null);
            $passedUploadEntity = ($uploadedFileEntity ?? null);
            $this->cleanupOnUploadFail($isError, $passedUploadedFile, $passedUploadEntity);
        }

        if ($isError) {
            $response->prefillBaseFieldsForBadRequestResponse();
            $response->setMessage($errorMessage);
        }

        $response->setStatus($status->value);

        return $response;
    }

    /**
     * Cleanup handled upload if something goes wrong
     *
     * @param bool                     $isError
     * @param SymfonyUploadedFile|null $uploadedFile
     * @param UploadedFile|null        $uploadedFileEntity
     */
    private function cleanupOnUploadFail(bool $isError, ?SymfonyUploadedFile $uploadedFile, ?UploadedFile $uploadedFileEntity): void
    {
        if (!$isError) {
            return;
        }

        // should not happen but just in case since something crashed then the file should be removed
        if(
                !is_null($uploadedFile)
            &&  file_exists($uploadedFile->getPathname())
        ){
            $isRemovedTmp = unlink($uploadedFile->getPathname());
            if (!$isRemovedTmp) {
                $this->loggerService->critical("Could not remove the file: {$uploadedFile->getPathname()}. Remove it manually!");
            }
        }

        if (!is_null($uploadedFileEntity)) {
            $this->entityManager->remove($uploadedFileEntity);
            $this->entityManager->flush();

            if (
                    file_exists($uploadedFileEntity->getPathWithFileName())
                &&  !unlink($uploadedFileEntity->getPathWithFileName())
            ) {
                $this->loggerService->critical("Could not remove the file: {$uploadedFileEntity->getPath()}. Remove it manually!");
            }
        }

    }
}