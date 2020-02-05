<?php

namespace Baodao\Payment\FourthParty;

use Baodao\Payment\PaymentConfig;

class Hengrun
{
    const NAME = '恒润支付';

    const URL = 'http://api.kuaile8899.com:8088/pay/apply.shtml';

    protected $notifyUrl;

    protected $md5Key;

    public function __construct($md5)
    {
        $this->md5Key = $md5;
        $this->notifyUrl = 'http://localhost/v1/payment/notify/'.strtolower(get_class());
    }

    public function create(array $inputs)
    {
        $form = [
            'appID' => $inputs['merchant'],
            'tradeCode' => $this->getPayCode($inputs['payment']),
            'randomNo' => random_int(10000, 99999),
            'outTradeNo' => $inputs['order_no'],
            'totalAmount' => $inputs['amount'] * 100,
            'productTitle' => 'transaction',
            'notifyUrl' => $this->notifyUrl,
            'tradeIP' => $inputs['tradeIp'],
        ];
        ksort($form);

        $form['sign'] = $this->encryptSign($form, $this->md5Key);

        $response = $this->curlPost([
            'ApplyParams' => json_encode($form, true),
        ]);
        if ('0000' === $response['stateCode']) {
            return [
                'code' => 200,
                'url' => $response['payURL'],
            ];
        }

        return [
            'code' => 500,
        ];
    }

    public function notify(array $inputs)
    {
        $data = json_decode($inputs['NoticeParams'], true);

        if ('0000' != $data['payCode']) {
            return [
                'code' => 400,
                'order_no' => $data['outTradeNo'],
                'message' => null,
            ];
        }

        if ($this->signCheck($data)) {
            return [
                'code' => 200,
                'order_no' => $data['outTradeNo'],
                'message' => 'SUCCESS',
            ];
        }

        return [
            'code' => 500,
            'order_no' => $data['outTradeNo'],
            'message' => null,
        ];
    }

    public function getConfig()
    {
        return [
            'cn_name' => self::NAME,
            'en_name' => strtolower(get_class()),
            'is_alipay' => 1,
            'is_wechat' => 1,
            'is_gateway' => 1,
            'is_qqpay' => 1,
            'is_unionpay' => 0,
            'is_jdpay' => 1,
            'is_ylpay' => 1,
            'fields' => json_encode([
                                        'merchant' => PaymentConfig::MERCHANT,
                                        'md5_key' => PaymentConfig::MD5,
                                        'trade_code' => [
                                            'alipay' => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
                                            'wechat' => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
                                            'qqpay' => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
                                            'gateway' => [],
                                            'ylpay' => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
                                            'jdpay' => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
                ],
            ])
        ];
    }

    private function signCheck($data)
    {
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);

        return $sign == $this->encryptSign($data, $this->md5Key);
    }

    public function encryptSign(array $params, string $key): string
    {
        return strtoupper(
            md5(
                implode('|', array_values($params)).
                '|'.
                $key
            )
        );
    }

    private function curlPost($fields): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::URL,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $fields,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function getPayCode(string $payment): string
    {
        $code = '';
        switch ($payment) {
            case 'wechat':
                $code = '80001';
                break;
            case 'alipay':
                $code = '80002';
                break;
            case 'qqpay':
                $code = '80003';
                break;
            case 'jdpay':
                $code = '80004';
                break;
            case 'unionpay':
                $code = '80005';
                break;
            case 'gateway':
                $code = '30003';
                break;
        }

        return $code;
    }
}
