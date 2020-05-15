<?php

namespace Baodao\Payment\Agent\ThirdParty;

use Baodao\Payment\Agent\AgentNotify;
use Baodao\Payment\Agent\AgentOrder;
use Baodao\Payment\Agent\AgentSetting;
use Baodao\Payment\Agent\Contracts\AgentInterface;
use DateTime;
use Exception;
use GuzzleHttp\Client;

/**
 * AE 代付
 *
 * @package Baodao\Payment\PaymentAgent
 */
class AePay implements AgentInterface
{
    const HOST = 'https://dfapi.aeyapay.com/';
    const ORDER_TYPE = 'DF_ZFBYHK';
    const RESPONSE_SUCCESS_CODE = 0;
    const ORDER_STATUS_IN_PROCESS = 0;
    const ORDER_STATUS_SUCCESS = 1;
    const ORDER_STATUS_FAILED = 2;
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * @param AgentSetting $setting
     *
     * @return \Baodao\Payment\Agent\AgentOrder|mixed
     * @throws Exception
     */
    public function createOrder(AgentSetting $setting): AgentOrder
    {
        $amount = $setting->orderAmount;
        if ($amount < 100 || $amount > 100000) {
            $result = new AgentOrder();
            $result->setFailedMessage('金额需要在 100 到 100000 之间');
            return $result;
        }

        // post data
        $postData['merchant_id'] = $setting->merchantNo;
        $postData['order_no'] = $setting->orderNo;
        $postData['order_money'] = $amount;
        $postData['ordertype'] = self::ORDER_TYPE;
        $postData['banker'] = $setting->payee;
        $postData['banknum'] = $setting->bankCard;
        if ($setting->notifyUrl) {
            $postData['callback_url'] = $setting->notifyUrl;
        }
        $postData['nonce'] = sprintf('%06d', rand(0, 999999));
        $postData['timestamp'] = (new DateTime())->getTimestamp();
        // signature
        $postData['signature'] = $this->getSignature($postData, $setting->md5Key);
        // other params
        //$postData['otherparams'] = json_encode(['test' => 'test']);
        // response
        $response = $this->client->post(self::HOST . 'dfapi/make_order', [
            'form_params' => $postData,
        ]);
        $responseData = json_decode($response->getBody()->getContents(), true);

        $result = new AgentOrder();
        if (self::RESPONSE_SUCCESS_CODE !== $responseData['success']) {
            $result->setFailedMessage($this->getSecure($responseData, 'msg'));
            return $result;
        }
        $responseData = $responseData['data'];
        $result->setStatusOK();
        $result->orderNo = $this->getSecure($responseData, 'order_no');
        $result->agentOrderNo = $this->getSecure($responseData, 'platform_orderid');
        $result->amount = $this->getSecure($responseData, 'order_money');

        return $result;
    }

    public function notifyResult(array $response): AgentNotify
    {
        $result = new AgentNotify();
        $result->agentOrderNo = $this->getSecure($response, 'platform_orderid');
        $result->orderNo = $this->getSecure($response, 'order_no');
        $result->orderAmount = $this->getSecure($response, 'order_money');
        $status = isset($response['order_status']) ? $response['order_status'] : self::ORDER_STATUS_FAILED;
        $result->agentOrderStatus = $status;
        if ($status == self::ORDER_STATUS_SUCCESS) {
            $result->status = AgentNotify::STATUS_OK;
        } elseif ($status == self::ORDER_STATUS_FAILED) {
            $result->status = AgentNotify::STATUS_FAILED;
        }

        return $result;
    }

    public function checkOrder(AgentSetting $setting, $orderNo): AgentNotify
    {
        // post data
        $postData['merchant_id'] = $setting->merchantNo;
        $postData['order_no'] = $orderNo;
        $postData['ordertype'] = static::ORDER_TYPE;
        $postData['nonce'] = sprintf('%06d', rand(0, 999999));
        $postData['timestamp'] = (new DateTime())->getTimestamp();
        // signature
        $postData['signature'] = $this->getSignature($postData, $setting->md5Key);
        // response
        $response = $this->client->post(static::HOST . 'dfapi/ser_orderstatus', [
            'form_params' => $postData,
        ]);
        $responseData = json_decode($response->getBody()->getContents(), true);
        if (self::RESPONSE_SUCCESS_CODE !== $responseData['success']) {
            return $responseData;
        }
        if (!$this->checkSignature($responseData['data'], $setting->md5Key)) {
            return sprintf('check signature fail. (%s)', var_export($responseData, true));
        }

        return $this->notifyResult($responseData['data']);
    }

    public function getBalance(AgentSetting $setting)
    {
        // post data
        $postData['merchant_id'] = $setting->merchantNo;
        $postData['nonce'] = sprintf('%06d', rand(0, 999999));
        $postData['timestamp'] = now()->timestamp;
        // signature
        $postData['signature'] = $this->getSignature($postData, $setting->md5Key);
        // response
        $response = $this->client->post(static::HOST . 'dfapi/get_balance', [
            'form_params' => $postData,
        ]);
        $responseData = json_decode($response->getBody()->getContents(), true);
        if (self::RESPONSE_SUCCESS_CODE !== $responseData['success']) {
            return sprintf('response fail. (%s)', var_export($responseData, true));
        }
        if (!$this->checkSignature($responseData['data'], $setting->md5Key)) {
            return sprintf('check signature fail. (%s)', var_export($responseData, true));
        }

        return $responseData['data'];
    }

    /**
     * check signature.
     *
     * @param array  $data
     *
     * @param string $md5Key
     *
     * @return bool
     */
    private function checkSignature(array $data, string $md5Key)
    {
        $signature = $data['signature'];
        unset($data['signature']);

        return $signature == $this->getSignature($data, $md5Key);
    }

    /**
     * get signature.
     *
     * @param array  $data
     *
     *
     * @param string $md5Key
     *
     * @return string
     */
    private function getSignature(array $data, string $md5Key)
    {
        ksort($data);
        $str = '';
        foreach ($data as $key => $val) {
            $str .= sprintf('%s%s', $key, $val);
        }

        return strtoupper(md5(sprintf('%s%s', $str, $md5Key)));
    }
    private function getSecure(array $arr, string $key)
    {
        return isset($arr[$key]) ? $arr[$key] : '';
    }
}
