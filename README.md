# Omnipay: Adyen

**Adyen driver (HPP, CSE and API integration) for the Omnipay PHP payment processing library**

FIX: [![Build Status](https://travis-ci.org/thephpleague/omnipay-dummy.png?branch=master)](https://travis-ci.org/thephpleague/omnipay-dummy)
[![Latest Stable Version](https://poser.pugx.org/omnipay/dummy/version.png)](https://packagist.org/packages/omnipay/dummy)
[![Total Downloads](https://poser.pugx.org/omnipay/dummy/d/total.png)](https://packagist.org/packages/omnipay/dummy)

[Omnipay](https://github.com/thephpleague/omnipay) is a framework agnostic, multi-gateway payment
processing library for PHP 5.3+. This package implements Dummy support for Omnipay.

Table of Contents
=================

   * [Omnipay: Adyen](#omnipay-adyen)
      * [Installation](#installation)
      * [Hosted Payment Pages (HPP)](#hosted-payment-pages-hpp)
         * [Server Fetches Payment Methods](#server-fetches-payment-methods)
         * [Client Fetches Payment Methods](#client-fetches-payment-methods)
         * [HPP Authorises a Payment](#hpp-authorises-a-payment)
            * [Prepare for Redirect](#prepare-for-redirect)
            * [Complete Transaction on Return](#complete-transaction-on-return)
      * [Client Side Encryption (CSE)](#client-side-encryption-cse)
         * [An Encypted Card Authorises a Payment](#an-encypted-card-authorises-a-payment)
      * [Support](#support)

## Installation

Omnipay is installed via [Composer](http://getcomposer.org/). To install, simply require `league/omnipay` and `omnipay/dummy` with Composer:

```
composer require acadweme/omnipay-adyen
```

**NOTE: this package is in development, so things will change, break, and change direction**

## Hosted Payment Pages (HPP)

This method hosts the payment pages on the gateway, and the user is
sent to those pages to make a payment.

A number of payment methods are supported, and they will vary depending
on the location of the merchant site (known here as local payment methods).
The list may change also depending on the amount being paid and the currency
being used, as well as the country of the merchant site and the end user.

Options for choosing the payment method include:

* Merchant site gets list of what is available and presents them to the
  end user to choose from. This may be filtered if required.
* The merchant site server chooses one payment method and the end user is
  taken directory to that one choice.
* The front end fetches the options and offers the user a choice.

### Server Fetches Payment Methods

```php
$gateway = Omnipay\Omnipay::create('Adyen\Hpp');

$gateway->initialize([
    'secret' => $hmac,
    'skinCode' => $skinCode,
    'merchantAccount' => $merchantAccount,
    'testMode' => true,
    // Optional; default set in account:
    'currency' => 'EUR',
    'countryCode' => 'GB',
]);

$request = $gateway->fetchPaymentMethods([
    'transactionId' => $transactionId,
    'amount' => 9.99,
]);

$response = $request->send();

// Options for $index:
// `false` (default) - the results will be indexed numerically
// `true` - the results will be indexed by `brandeCode`

$response->getPaymentMethods($index)
```

This gives you an array of this this form:

```php
array(7) {
  ["diners"]=>
  array(3) {
    ["brandCode"]=>
    string(6) "diners"
    ["logos"]=>
    array(3) {
      ["normal"]=>
      string(44) "https://test.adyen.com/hpp/img/pm/diners.png"
      ["small"]=>
      string(50) "https://test.adyen.com/hpp/img/pm/diners_small.png"
      ["tiny"]=>
      string(49) "https://test.adyen.com/hpp/img/pm/diners_tiny.png"
    }
    ["name"]=>
    string(11) "Diners Club"
  }
  ...
}
```

Some payment methods will also have a list of `issuers` that may also be
used to refine the options offered to the end user.
At this time, there are no further parsing of this data into objects
by this driver.

### Client Fetches Payment Methods

Use the same method as the previous section (Server Fetches Payment Methods)
but do not `send()` the request.
Instead, get its data and endpoint for use by the client:

```php
$data = $request->getData();
$endpoint = $request->getEndpoint();
```

`POST` the `$data` to the `$endpoint` to get the JSON response.
Rememeber this data is signed, so parameters cannot be changed at
the client side.

### HPP Authorises a Payment

#### Prepare for Redirect

The gateway object is instantiated first, as before.

```php
$gateway = Omnipay\Omnipay::create('Adyen\Hpp');

$gateway->initialize([
    'secret' => $hmac,
    'skinCode' => $skinCode,
    'merchantAccount' => $merchantAccount,
    'testMode' => true,
    'currency' => 'EUR',
    'countryCode' => 'DE',
]);
```

The `CreditCard` class is used to suply any billing details.
Shipping detais are not supported at this time, but may be later.

```php
$card = new Omnipay\Common\CreditCard([
    'firstName' => 'Joe',
    'lastName' => 'Bloggs',

    'billingAddress1' => '88B',
    'billingAddress2' => 'Address 2B',
    'billingState' => 'StateB',
    'billingCity' => 'CityB',
    'billingPostcode' => '412B',
    'billingCountry' => 'GB',
    'billingPhone' => '01234 567 890',

    'email' =>  'jason@example.co.uk',
]);
```

The request sets up the redirect:

```php
$request = $gateway->authorize([
    'transactionId' => $transactionId,
    'amount' => 9.99,
    // The returnUrl can be defined in the account, and overridden here.
    'returnUrl' => 'https://example.co.uk/your/return/endpoint',
    'card' => $card,
]);
```

Now there are a few additional parameters that need some explanation.

The `brandCode` can be used to redirect the user to a specific payment method:

    $request->setBrandCode('visa');
    $request->setIssuerId('optional issuer ID for the brandCode');

Specifying the `brandCode` will skip any pages asking the user how they
want to pay, and take the user direct to that payment method.

Setting `addressHidden` to `true` will hide the address being submitted to
the payment gateway.
The user will not see their address, but what you submit will be stored at
the gateway.
By defauit it is shown.

    $request->setAddressHidden(true);

Setting `addressLocked` will prevent the user from changing their address
details on the gateway.
Although all these details are sent to he gateway in the redirect, they are
signed, so any attempt by the user to change them will result in a rejection.

    $request->setAddressLocked(true);

Additional parameters will be available in time.

Now "send" the request to get the redirection response.

    $response = $request->send();

The redirect will be a `POST`.
The details to include are in `$response->getData()` and `$response->getRedirectUrl()`,
so you can build a form to post or auto-post, either to the top page
or to an iframe. Or you can just issue `echo $response->redirect()` as a rough-and-ready
redirection.

The user will be redirected, will enter their authorisation details on the
gateway hosted page, then will be returned to the `returnUrl`.
This is where the transaction is completed.

#### Complete Transaction on Return

The user will be returned with the result of the authorisation as query parameters.
These are read and parsed like this:

    $response = $gateway->completeAuthorize()->send();

From the `$response` you can get the result, the `transactionReference`
(if the result is successful) and the raw data.

```php
var_dump($response->getdata());
var_dump($response->getAuthResult());
var_dump($response->isSuccessful());
var_dump($response->isPending());
var_dump($response->isCancelled());
var_dump($response->getTransactionReference());
var_dump($response->getTransactionId());
```

The data is signed, and if the signature is invalid then an exception will
be thrown during the `send()` operation.

The get further details about the authorisation, the transaction will need to
be fetched from the gateway using the API.
This result just gives you the overall result, and returns your `transactionId`
so you can confirm it is the result of the transaction you are expecting
(it is vital to check the `transactionId`) so URLs from pevious authorisations
cannot be injected by an end user.

TODO: API `fetchTransaction()`

## Client Side Encryption (CSE)

The Adyen gateway allows a credit card form to be used directly in your application page.
The credit card details are not directly submitted to your merchant site,
but are encrypted at the client (browser), and the encrypted string is then submitted to
your site, along with any additional details.

The encrpyted details are then used in place of credit card details when making
an autorisation request to the API, server-to-server.

The client-side fuctionality can be completely built by hand, but the following
minimal example shows how this library can help build it.
The *laravel blade* view format is used in the example.

```php
$gateway = Omnipay\Omnipay::create('Adyen\Payment');

$gateway->initialize([
    'testMode' => true,
    'publicKeyToken' => $cseLibraryPublicKeyToken,
]);

$request = $gateway->encryptionClient([
    'returnUrl' => 'https://example.com/payment-handler',
]);

```

```html
<html>

<head>
    <script type="text/javascript" src="{{ $request->getLibraryUrl() }}"></script>
</head>

<body>
    <form method="POST" action="{{ $response->getReturnUrl() }}" id="adyen-encrypted-form">
        <input type="text" size="20" data-encrypted-name="number" value="4444333322221111" />
        <input type="text" size="20" data-encrypted-name="holderName" value="User Name" />
        <input type="text" size="2" data-encrypted-name="expiryMonth" value="10" />
        <input type="text" size="4" data-encrypted-name="expiryYear" value="2020" />
        <input type="text" size="4" data-encrypted-name="cvc" value="737" />
        <input type="hidden" value="{{ $request->getGenerationtime() }}" data-encrypted-name="generationtime" />
        <input type="submit" value="Pay" />
    </form>

    <script>
    // The form element to encrypt.
    var form = document.getElementById('adyen-encrypted-form');
    // See https://github.com/Adyen/CSE-JS/blob/master/Options.md for details on the options to use.
    var options = {};
    // Bind encryption options to the form.
    adyen.createEncryptedForm(form, options);
    </script>
</body>

</html>
```

The `Pay` button will not be enabled until the credit card fields are completed and valid.

The JavaScript library included in the header will then encrypt the card details and
add the result to the hidden `POST` field `adyen-encrypted-data` by default.
You can specify an alternative field name through the options.
This field must be accepted by the `https://example.com/payment-handler` page for the
next step.

### An Encypted Card Authorises a Payment

This is the server-side handling.

TODO...

## Support

If you are having general issues with Omnipay, we suggest posting on
[Stack Overflow](http://stackoverflow.com/). Be sure to add the
[omnipay tag](http://stackoverflow.com/questions/tagged/omnipay) so it can be easily found.

If you want to keep up to date with release anouncements, discuss ideas for the project,
or ask more detailed questions, there is also a [mailing list](https://groups.google.com/forum/#!forum/omnipay) which
you can subscribe to.

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/thephpleague/omnipay-dummy/issues),
or better yet, fork the library and submit a pull request.
