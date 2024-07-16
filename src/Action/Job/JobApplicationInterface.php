<?php

namespace App\Action\Job;

interface JobApplicationInterface
{
    const ATTACHED_FILE_IDS = "attachedFileIds";
    const JOB_SEARCH_ID     = "jobSearchId";
    const ATTACHED_FILTERS  = "filters";
    const TEMPLATE_ID       = "templateId";
    const APPLICATIONS_DATA = "applicationsData";
    const RECIPIENT         = "recipient";
    const EMAIL_BODY        = "emailBody";

    const OFFER_ID           = "offerId";
    const OFFER_TITLE        = "offerTitle";
    const OFFER_URL          = "offerUrl";
    const OFFER_COMPANY_NAME = "offerCompanyName";
}