<?php

namespace Baodao\Payment\FourthParty;

use Baodao\Payment\Contracts\PaymentInterface;
use Baodao\Payment\PaymentConfig;
use Baodao\Payment\PaymentCreation;
use Baodao\Payment\PaymentNotify;
use Baodao\Payment\PaymentSetting;

class Hengrun implements PaymentInterface
{
    const NAME = '恒润支付';
    const URL = 'http://api.kuaile8899.com:8088/pay/apply.shtml';
    protected $md5Key;

    public function create(PaymentSetting $p):PaymentCreation
    {
        $this->md5Key = $p->md5Key;
        $form = [
            'appID'        => $p->merchantNo,
            'tradeCode'    => $this->getPayCode($p->thirdPartyType),
            'randomNo'     => random_int(10000, 99999),
            'outTradeNo'   => $p->orderNo,
            'totalAmount'  => $p->orderAmount * 100,
            'productTitle' => $p->productName,
            'notifyUrl'    => $p->notifyUrl,
            'tradeIP'      => $p->merchantIp,
        ];
        ksort($form);
        $form['sign'] = $this->encryptSign($form, $this->md5Key);
        $response = $this->curlPost([
                                        'ApplyParams' => json_encode($form, true),
                                    ]);
        $result = new PaymentCreation();
        if ('0000' === $response['stateCode']) {
            $result->url = $response['payURL']??null;
            $result->code = 200;
            $result->message = $response['stateInfo']??null;
            return $result;
        }
        $result->code = 500;
        $result->message = $response['stateInfo']??null;
        return $result;
    }

    public function notify(PaymentSetting $p,  array $inputs):PaymentNotify
    {
        $data = json_decode($inputs['NoticeParams'], true);
        $result = new PaymentNotify();
        if ('0000' != $data['payCode']) {
            $result->code = 400;
            $result->orderNo = $data['outTradeNo'];
            $result->message=null;
            return $result;
        }
        if ($this->signCheck($data, $p->md5Key)) {
            $result->code = 200;
            $result->orderNo = $data['outTradeNo'];
            $result->message='SUCCESS';
            return $result;
        }

        $result->code = 500;
        $result->orderNo = $data['outTradeNo'];
        $result->message=null;
        return $result;
    }

    public function getConfig():PaymentConfig
    {
        $c = new PaymentConfig();
        $enName = (new \ReflectionClass($this))->getShortName();

        return $c->setCnName(self::NAME)
                 ->setEnName(strtolower($enName))
                 ->setThirdParty([PaymentConfig::THIRD_PARTY_ALIPAY,
                             PaymentConfig::THIRD_PARTY_WECHAT,
                             PaymentConfig::THIRD_PARTY_QQ,
                             PaymentConfig::THIRD_PARTY_GATEWAY,
                             PaymentConfig::THIRD_PARTY_YLPAY,
                             PaymentConfig::THIRD_PARTY_JDPAY])
            ->setFieldMerchant()
            ->setFieldMd5Key()
            ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_ALIPAY,
                                [PaymentConfig::TRADE_SCAN,PaymentConfig::TRADE_H5 ])
            ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_WECHAT,
                                [PaymentConfig::TRADE_SCAN,PaymentConfig::TRADE_H5 ])
            ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_QQ,
                                [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5])
            ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_GATEWAY, [])
            ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_YLPAY,
                                [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5])
            ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_GATEWAY, [])
            ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_JDPAY,
                                [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5]);
//        return [
//            'cn_name'     => self::NAME,
//            'en_name'     => strtolower(get_class()),
//            'is_alipay'   => 1,
//            'is_wechat'   => 1,
//            'is_gateway'  => 1,
//            'is_qqpay'    => 1,
//            'is_unionpay' => 0,
//            'is_jdpay'    => 1,
//            'is_ylpay'    => 1,
//            'fields'      => json_encode([
//                                             'merchant'   => PaymentConfig::MERCHANT,
//                                             'md5_key'    => PaymentConfig::MD5,
//                                             'trade_code' => [
//                                                 'alipay'  => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
//                                                 'wechat'  => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
//                                                 'qqpay'   => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
//                                                 'gateway' => [],
//                                                 'ylpay'   => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
//                                                 'jdpay'   => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
//                                             ],
//                                         ])
//        ];
    }

    public function encryptSign(array $params, string $key): string
    {
        return strtoupper(
            md5(
                implode('|', array_values($params)) .
                '|' .
                $key
            )
        );
    }
    public function getBanks()
    {
        return [];
    }

    private function signCheck($data,$md5Key)
    {
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);

        return $sign == $this->encryptSign($data, $md5Key);
    }

    private function curlPost($fields): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => self::URL,
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $fields,
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
