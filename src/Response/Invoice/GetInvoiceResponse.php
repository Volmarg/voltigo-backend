<?php

namespace App\Response\Invoice;

use App\Response\Base\BaseResponse;

/**
 * Response delivering the path of the invoice file inside the public folder
 */
class GetInvoiceResponse extends BaseResponse
{
    private string $invoicePathInPublic;

    /**
     * @return string
     */
    public function getInvoicePathInPublic(): string
    {
        return $this->invoicePathInPublic;
    }

    /**
     * @param string $invoicePathInPublic
     */
    public function setInvoicePathInPublic(string $invoicePathInPublic): void
    {
        $this->invoicePathInPublic = $invoicePathInPublic;
    }

}