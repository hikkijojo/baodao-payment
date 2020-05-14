<?php

namespace Baodao\Payment\Agent\ThirdParty;

use DateTime;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

/**
 * AE 代付
 *
 * @package Baodao\Payment\PaymentAgent
 */
class AePay
{
    const URL = 'https://dfapi.aeyapay.com/';
    const MERCHANT_ID = 'AEMYUM5HB1';
    const ORDER_TYPE = 'DF_ZFBYHK';
    const MD5_KEY = 'kG312HljR26bS4sJ772D4Y67WE';
    const RESPONSE_SUCCESS_CODE = 0;
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function createOrder($order, $callBackUrl=null)
    {
        $amount = $order['amount'];
        if ($amount < 100 || $amount > 100000) {
            throw new Exception("amount $amount is not in [100, 100000]");
        }
        // post data
        $postData['merchant_id'] = static::MERCHANT_ID;
        $postData['order_no'] = $order['order_no'];
        $postData['order_money'] = $amount;
        $postData['ordertype'] = static::ORDER_TYPE;
        $postData['banker'] = $order['full_name'];
        $postData['banknum'] = $order['bank_card'];
        if ($callBackUrl) {
            $postData['callback_url'] = $callBackUrl;
        }
        $postData['nonce'] = sprintf('%06d', rand(0, 999999));
        $postData['timestamp'] = (new DateTime())->getTimestamp();

        // signature
        $postData['signature'] = $this->getSignature($postData);

        // other params
        $postData['otherparams'] = json_encode(['test' => 'test']);

        // response
        $response = $this->client->post(static::URL.'dfapi/make_order', [
            'form_params' => $postData,
        ]);
        $responseData = json_decode($response->getBody()->getContents(), true);
        if (0 !== $responseData['success']) {
            return $responseData;
        }
        return $responseData['data'];
    }

    public function checkOrder($orderNo)
    {
        // post data
        $postData['merchant_id'] = static::MERCHANT_ID;
        $postData['order_no'] = $orderNo;
        $postData['ordertype'] = static::ORDER_TYPE;
        $postData['nonce'] = sprintf('%06d', rand(0, 999999));
        $postData['timestamp'] = (new DateTime())->getTimestamp();

        // signature
        $postData['signature'] = $this->getSignature($postData);

        // response
        $response = $this->client->post(static::URL.'dfapi/ser_orderstatus', [
            'form_params' => $postData,
        ]);
        $responseData = json_decode($response->getBody()->getContents(), true);

        if (0 !== $responseData['success']) {
            return $responseData;
        }

        if (!$this->checkSignature($responseData['data'])) {
            return sprintf('check signature fail. (%s)', var_export($responseData, true));
        }

        return $responseData['data'];
    }


    public function getBalance()
    {
        // post data
        $postData['merchant_id'] = static::MERCHANT_ID;
        $postData['nonce'] = sprintf('%06d', rand(0, 999999));
        $postData['timestamp'] = now()->timestamp;

        // signature
        $postData['signature'] = $this->getSignature($postData);

        // response
        $response = $this->client->post(static::URL.'dfapi/get_balance', [
            'form_params' => $postData,
        ]);
        $responseData = json_decode($response->getBody()->getContents(), true);
        if (0 !== $responseData['success']) {
            return sprintf('response fail. (%s)', var_export($responseData, true));
        }
        if (!$this->checkSignature($responseData['data'])) {
            return sprintf('check signature fail. (%s)', var_export($responseData, true));
        }

        return $responseData['data'];
    }

    /**
     * check signature.
     *
     * @param array $data
     *
     * @return bool
     */
    private function checkSignature(array $data)
    {
        $signature = $data['signature'];
        unset($data['signature']);

        return $signature == $this->getSignature($data);
    }


    /**
     * get signature.
     *
     * @param array $data
     *
     * @return string
     */
    private function getSignature(array $data)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key=>$val) {
            $str.=sprintf('%s%s', $key, $val);
        }

        return strtoupper(md5(sprintf('%s%s', $str, static::MD5_KEY)));
    }
}
