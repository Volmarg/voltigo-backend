<?php

namespace App\Service\Templates\Email;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class JobSearchTemplatesService
{
    /**
     * @param Environment $environment
     */
    public function __construct(
        private readonly Environment $environment
    ) {
    }

    /**
     * Will render E-mail which consists of information about the job search done with success
     *
     * @param array $templateData
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderSearchSuccess(array $templateData): string
    {
        return $this->environment->render("mail/job-search/success.twig", $templateData);
    }

    /**
     * Will render E-Mail which consist of information that the job search was done with success
     *
     * @param array $templateData
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function renderSearchFailure(array $templateData): string
    {
        return $this->environment->render("mail/job-search/failure.twig", $templateData);
    }
}