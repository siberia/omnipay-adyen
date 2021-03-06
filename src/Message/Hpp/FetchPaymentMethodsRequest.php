<?php

namespace Omnipay\Adyen\Message\Hpp;

/**
 *
 */

use Omnipay\Adyen\Message\AbstractHppRequest;

class FetchPaymentMethodsRequest extends AbstractHppRequest
{
    /**
     * Get the raw data array for this message.
     *
     * @return mixed
     */
    public function getData()
    {
        $data = [];

        // Mandatory base data.

        $data['currencyCode'] = $this->getCurrency();
        $data['merchantReference'] = $this->getTransactionId();
        $data['skinCode'] = $this->getSkinCode();
        $data['merchantAccount'] = $this->getMerchantAccount();
        $data['sessionValidity'] = $this->getSessionValidity();

        // Optional parameters that may still filter the payment
        // methods returned.

        if ($this->getAmountInteger() !== null) {
            $data['paymentAmount'] = $this->getAmountInteger();
        }

        if ($this->getCountryCode() !== null) {
            $data['countryCode'] = $this->getCountryCode();
        }

        // Finally add the HMAC signature for the data.

        $data['merchantSig'] = $this->generateSignature($data);

        return $data;
    }

    /**
     * Create the response object from array data.
     */
    public function createResponse(array $data)
    {
        return new FetchPaymentMethodsResponse($this, $data);
    }

    public function getEndpoint($service = null)
    {
        return $this->getDirectoryUrl();
    }
}
