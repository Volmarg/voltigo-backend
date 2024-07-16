<?php

namespace App\Action\Security;

use App\Attribute\JwtAuthenticationDisabledAttribute;
use App\Entity\File\UploadedFile;
use App\Entity\Security\User;
use App\Exception\NotFoundException;
use App\Exception\Security\PublicFolderAccessDeniedException;
use App\Kernel;
use App\Repository\File\UploadedFileRepository;
use App\Response\Base\BaseResponse;
use App\Service\Security\JwtAuthenticationService;
use App\Service\Security\PublicFolderSecurityService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Relies on the token added to the file that is getting to be downloaded because the standard jwt authentication has
 * been disabled on the download links. Had to be because it has to work natively in browser and not with all the ajax
 * requests which are all over the place as this would at some point need handling of reading binary file on front
 * and saving it etc.
 */
class PublicFolderAction extends AbstractController
{
    public const ROUTE_NAME_DOWNLOAD  = "file.download";

    public function __construct(
        private readonly UploadedFileRepository      $uploadedFileRepository,
        private readonly Kernel                      $kernel,
        private readonly PublicFolderSecurityService $publicFolderSecurityService,
        private readonly JwtAuthenticationService    $jwtAuthenticationService
    ){}

    /**
     * Handles downloading file in the secure manners, meaning that downloaded data is controlled with who got actually
     * access to it, instead of just serving everything from public folder.
     * @throws Exception
     */
    #[Route("/download/{path}", name: self::ROUTE_NAME_DOWNLOAD, requirements: ["path" => ".+"], methods: [Request::METHOD_OPTIONS, Request::METHOD_GET])]
    #[JwtAuthenticationDisabledAttribute]
    public function getFromFolder(string $path, Request $request): Response
    {
        if (!$this->jwtAuthenticationService->isAnyGrantedToUser([User::ROLE_USER, User::RIGHT_PUBLIC_FOLDER_ACCESS])) {
            return BaseResponse::buildAccessDeniedResponse()->toJsonResponse();
        }

        $this->publicFolderSecurityService->denyIfNotLogged($request);
        $this->publicFolderSecurityService->validateFilePath($path);
        $user = $this->publicFolderSecurityService->getUserFromToken($request);

        $filePathInsidePublicDir      = $this->kernel->getContainer()->getParameter('public.access.data.dir') . DIRECTORY_SEPARATOR . $path;
        $directoryPathInsidePublicDir = dirname($filePathInsidePublicDir) . DIRECTORY_SEPARATOR;
        $fileName                     = basename($filePathInsidePublicDir);
        $uploadedFile                 = $this->uploadedFileRepository->findByPublicAccess($directoryPathInsidePublicDir, $fileName);

        // the one download way is getting uploaded file which is cleaner,
        if (!empty($uploadedFile)) {
            return $this->handleUploadedFile($uploadedFile, $fileName, $user);
        }

        return $this->handlePathBasedDownload($filePathInsidePublicDir, $fileName, $user);
    }

    /**
     * @param string $fileName
     * @param string $fullFilePathWithName
     *
     * @return Response
     * @throws Exception
     */
    private function buildResponse(string $fileName, string $fullFilePathWithName): Response
    {
        $fileContent = file_get_contents($fullFilePathWithName);
        if (is_bool($fileContent)) {
            throw new Exception("Could not open the file: {$fullFilePathWithName}");
        }

        $response    = new Response($fileContent);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @param string       $fileName
     * @param User         $user
     *
     * @return Response
     * @throws Exception
     */
    private function handleUploadedFile(UploadedFile $uploadedFile, string $fileName, User $user): Response
    {
        if ($uploadedFile->getUser()->getId() !== $user->getId()) {
            throw new PublicFolderAccessDeniedException("
                User: {$user->getId()}, tried to access file
                which does not belong to him!
            ");
        }

        $response = $this->buildResponse($fileName, $uploadedFile->getPathWithFileName());
        return $response;
    }

    /**
     * The other way is getting the user by the provided path but with necessity of extracting user id from path
     * and this follows the convention that there is always user id in given place of path
     *
     * @param string $filePathInsidePublicDir
     * @param string $fileName
     * @param User   $user
     *
     * @return Response
     *
     * @throws NotFoundException
     * @throws PublicFolderAccessDeniedException
     * @throws Exception
     */
    private function handlePathBasedDownload(string $filePathInsidePublicDir, string $fileName, User $user): Response
    {
        preg_match("#([a-zA-Z]*)/(?<USER_ID>[0-9]+)/#", $filePathInsidePublicDir, $matches);
        $userId = (int) $matches['USER_ID'] ?? null;
        if (empty($userId)) {
            throw new Exception("This public path does not contain user id in it, got: {$filePathInsidePublicDir}");
        }

        $absoluteFilePathOnServer = $this->kernel->getPublicDirectoryPath() . "{$filePathInsidePublicDir}";
        if (!file_exists($absoluteFilePathOnServer)) {
            throw new NotFoundException("File: {$absoluteFilePathOnServer} - does not exist");
        }

        if ($userId !== $user->getId()) {
            throw new PublicFolderAccessDeniedException("
                User: {$user->getId()}, tried to access file
                which does not belong to him!
            ");
        }

        $response = $this->buildResponse($fileName, $absoluteFilePathOnServer);

        return $response;
    }

}