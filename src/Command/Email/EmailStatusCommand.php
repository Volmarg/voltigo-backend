<?php

namespace App\Command\Email;

use App\Command\AbstractCommand;
use App\Controller\Core\ConfigLoader;
use App\Controller\Core\Services;
use App\Controller\Email\EmailController;
use App\Controller\Job\JobApplicationController;
use App\Entity\Email\Email;
use App\Entity\Job\JobApplication;
use App\Response\Mail\GetMailStatusResponse;
use App\Service\Messages\Email\EmailingServiceInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Will handle checkin email status, and updating the application entry,
 * once the email is sent with success it's removed from the {@see Email} table
 */
class EmailStatusCommand extends AbstractCommand
{
    const COMMAND_NAME = "email:check-status";

    /**
     * {@inheritDoc}
     */
    protected function setConfiguration(): void
    {
        $this->setDescription("This command handles checking the E-Mail status in external tool, and updating the application entry, alongside with removing no longer needed E-Mails");
    }

    public function __construct(
        private EmailController          $emailController,
        private EmailingServiceInterface $emailingService,
        protected ConfigLoader           $configLoader,
        private Services                 $services,
        private JobApplicationController $jobApplicationController,
        private readonly KernelInterface $kernel

    )
    {
        parent::__construct($configLoader, $kernel);
    }

    /**
     * {@inheritDoc}
     */
    protected function executeLogic(): int
    {
        $allEmails = $this->emailController->getAllSentEmails();
        if( empty($allEmails) ){
            $this->io->info("There are no E-mails to check statuses for");
        }

        foreach($allEmails as $email){

            try{
                $response = $this->emailingService->getEmailStatus($email->getExternalId());

                if( $response->isSuccess() ){
                    $this->handleSuccessResponse($response, $email);
                }else{
                    $this->services->getLoggerService()->critical("Tried to get sending status of an E-Mail with id {$email->getId()}. Api was reached, but response was not successful", [
                        "emailId"         => $email->getId(),
                        "externalEmailId" => $email->getExternalId(),
                        "response" => [
                            "code"    => $response->getCode(),
                            "message" => $response->getMessage(),
                        ]
                    ]);

                    continue;
                }

                if( $response->isError() ){
                    $this->services->getLoggerService()->critical("Got success response but E-Mail sending has an Error", [
                        "emailId"         => $email->getId(),
                        "externalEmailId" => $email->getExternalId(),
                    ]);
                }

            }catch(Exception | TypeError $e){
                $this->services->getLoggerService()->logException($e, ["Could not get status of E-Mail with id from external tool: {$email->getId()}"]);
                return self::FAILURE;
            }

        }

        return Command::SUCCESS;
    }

    /**
     * @param GetMailStatusResponse $dto
     * @param Email                 $email
     *
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handleSuccessResponse(GetMailStatusResponse $dto, Email $email): void
    {
        if(
                $dto->isSent()
            &&  $email->hasJobApplication()
        ){
            $email->getJobApplication()->setStatus(JobApplication::STATUS_EMAIL_SENT);
            $email->setStatus(Email::KEY_STATUS_SENT_BY_EXTERNAL_TOOL);

            $this->jobApplicationController->save($email->getJobApplication());
            $this->emailController->save($email);

            return;
        }

        if( $dto->isError() ){
            $this->services->getLoggerService()->critical("Got response for checking sending status of E-Mail  with id {$email->getId()}. Api was reached, but E-Mail sending status = ERROR", [
                "emailId"         => $email->getId(),
                "externalEmailId" => $email->getExternalId(),
            ]);

            return;
        }

    }

}
