<?php

namespace App\Utils;

use App\Entity\Invoice;
use Symfony\Component\Dotenv\Dotenv;
use \DateTime;

class Paymaster
{
    public static function builtinPaymentInitonlyAction(Invoice $invoice)
    {
        /** @var $invoice \App\Entity\Invoice */
        $dotenv = new Dotenv();
        $dotenv->load(getcwd() . '/../.env');

        $LMI_MERCHANT_ID    = getenv('LMI_MERCHANT_ID');
        $LMI_SECRET_KEY     = getenv('LMI_SECRET_KEY');
        $LMI_PAYMENT_AMOUNT = $invoice->getSum();
        $LMI_PAYMENT_DESC   = $invoice->getTitle();
        $LMI_CURRENCY       = 'RUB';
        $url                = 'https://paymaster.ru/BuiltinPayment/initonly';

        $params = [
            'LMI_MERCHANT_ID'    => $LMI_MERCHANT_ID,
            'LMI_PAYMENT_AMOUNT' => $LMI_PAYMENT_AMOUNT,
            'LMI_CURRENCY'       => $LMI_CURRENCY,
            'LMI_SECRET_KEY'     => $LMI_SECRET_KEY,
        ];

        $auth_str  = implode(';', $params);
        $auth_hash = base64_encode(hash('sha256', $auth_str, true));

        //$LMI_PAYMENT_METHOD = '[Test]';
        //$params['LMI_PAYMENT_METHOD'] = $LMI_PAYMENT_METHOD;
        $params['invoice_id']         = $invoice->getId();
        $params['number']             = $invoice->getNumber();
        $params['LMI_PAYMENT_DESC']   = $LMI_PAYMENT_DESC;
        $params['LMI_EXPIRES']        = (new DateTime('now + 10 days'))->format('Y-m-d\TH:i:s');
        $params['authhash']           = $auth_hash;
        $params['json']               = 1;

        $result = file_get_contents($url, false, stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($params),
            ]
        ]));

        $result = json_decode($result);
        $uri    = $result->PaymentUrl;

        return $uri;
    }
}

