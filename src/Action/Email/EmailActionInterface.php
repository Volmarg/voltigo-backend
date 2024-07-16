<?php

namespace App\Action\Email;

/**
 * Either provides predefined logic or is used to remove bloat logic from class
 */
interface EmailActionInterface
{
    const TEMPLATE_ID = "templateId";
    const EMAILS      = "emails";
    const RECIPIENT   = "recipient";
    const EMAIL_BODY  = "emailBody";

    const OFFER_ID           = "offerId";
    const OFFER_TITLE        = "offerTitle";
    const OFFER_URL          = "offerUrl";
    const OFFER_COMPANY_NAME = "offerCompanyName";
}