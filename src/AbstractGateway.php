<?php

namespace Omnipay\Adyen;

use Omnipay\Common\AbstractGateway as CommonAbstractGateway;
use Omnipay\Adyen\Traits\GatewayParameters;

/**
 * Adyen HPP (Hosted Payment Pages) Gateway.
 * Takes the user off the merchant site, but offers many local payment methods.
 * See https://docs.adyen.com/developers/ecommerce-integration/cse-integration-ecommerce
 */

use Omnipay\Adyen\Message\FetchPaymentMethodsRequest;
use Omnipay\Adyen\Message\AuthorizeRequest;
use Omnipay\Adyen\Message\CompleteAuthorizeRequest;
use Omnipay\Adyen\Message\CseClientRequest;

abstract class AbstractGateway extends CommonAbstractGateway
{
    use GatewayParameters;

    /**
     *
     */
    public function getDefaultParameters()
    {
        return [
            'merchantAccount' => '',
            'secret' => '',
            'skinCode' => '',
            'currency' => 'EUR',
            // publicKeyToken aka Library Token.
            'publicKeyToken' => null,
            // WebServiceUser credentials (basic auth).
            'username' => '',
            'password' => '',
        ];
    }
}
