<?php

namespace Baodao\Payment\FourthParty;

use Baodao\Payment\Contracts\PaymentInterface;
use Baodao\Payment\PaymentConfig;
use Baodao\Payment\PaymentSetting;
use Baodao\Payment\PaymentCreation;
use Baodao\Payment\PaymentNotify;
use GuzzleHttp\Client;

class Wantung implements PaymentInterface
{
    const MD5 = 'MD5';
    const PAY_TYPES = [self::WEIXIN,
                       self::WEIXIN_H5,
                       self::ZHIFUBAO,
                       self::ZHIFUBAO_H5,
                       self::QQ,
                       self::WANGGUAN,
                       //self::KUAIJIE,
                       self::YL,
                       self::JD, ];
    const WEIXIN = 'weixin';           //微信
    const WEIXIN_H5 = 'weixin-h5';     //微信H5
    const ZHIFUBAO = 'zhifubao';       //支付宝
    const ZHIFUBAO_H5 = 'zhifubao-h5'; //支付宝(原生)
    const QQ = 'qq';                   //QQ支付
    const WANGGUAN = 'wangguan';       //网关
    const KUAIJIE = 'kuaijie';         //快捷, 目前不使用的
    const YL = 'yl';                   //银联扫码
    const JD = 'jd';                   //京东
    private $appnoNo;
    private $bankCard;
    private $bankCode;
    private $bankCodeMap = [1 => 'ICBC',
                            3 => 'ABC',
                            7 => 'BOC',
                            2 => 'CCB',
                            9 => 'CITIC',
                            15 => 'CEB',
                            13 => 'GDB',
                            14 => 'PAB',
                            12 => 'SPDB',
                            8 => 'PSBC', ];
    private $host;
    private $key;
    private $merchantCode;
    private $merchantIp;
    private $notifyUrl;
    private $orderAmount;
    private $orderNo;
    private $orderTime;
    private $payType;
    private $productCode;
    private $productName;
    private $readyToConnect;
    private $returnUrl;
    private $rsaPri;
    private $rsaPub;
    private $userNo;

    public function setConnection(PaymentSetting $p)
    {
        $payType = $this->getPayType($p->thirdPartyType, $p->tradeType);
        if (false == in_array($payType, self::PAY_TYPES)) {
            throw new \Exception("Unknown pay_type $payType");
        }
        if (empty($p->redirectUrl)) {
            throw new \Exception('Empty returnUrl');
        }
        if (self::WANGGUAN == $payType && empty($p->bankCode)) {
            throw new \Exception('网关支付时，此字段必填，各银行编码详见 https://www.showdoc.cc/ufo?page_id=818330595764566');
        }
        if (self::WEIXIN_H5 == $payType && empty($p->merchantIp)) {
            throw new \Exception('微信H5，merchantIp 为必填字段');
        }
        if ($p->orderAmount < 0.01) {
            throw new \Exception("Amount has to be larger than 0.01, but {$p->orderAmount} given");
        }
        if (strlen($p->orderNo) > 30) {
            throw new \Exception('order_no length should be smaller than 31');
        }
        if (strlen($p->userNo) > 20) {
            throw new \Exception('user_no length should be smaller than 20');
        }
        if (strlen($p->productCode) > 24) {
            throw new \Exception('product_code length should be smaller than 24');
        }
        if ($p->bankCode) {
            $p->bankCode = $this->bankCodeMap[$p->bankCode];
        }
        $this->payType = $payType;
        $this->notifyUrl = $p->notifyUrl;
        $this->returnUrl = $p->redirectUrl;
        $this->bankCode = $p->bankCode;
        $this->bankCard = $p->bankCard;
        $this->merchantIp = $p->merchantIp;
        $this->orderNo = $p->orderNo;
        $this->orderAmount = $p->orderAmount;
        $this->orderTime = $p->orderTime->format('YmdHis');
        $this->productName = $p->productName;
        $this->productCode = $p->productCode;
        $this->userNo = $p->userNo;
        $this->appnoNo = $p->appNo;
        $this->merchantCode = $p->merchantNo;
        $this->key = $p->md5Key;
        $this->rsaPri = $p->rsaPrivateKey;
        $this->rsaPub = $p->rsaPublicKey;
        $this->host = empty($p->host) ? 'https://www.wantong-pay.com' : $p->host;
        $this->readyToConnect = true;
    }

    public function create(PaymentSetting $paymentConnection): PaymentCreation
    {
        $this->setConnection($paymentConnection);
        if (!$this->readyToConnect) {
            throw new \Exception('Please setConnection first');
        }
        if (empty($this->host)) {
            throw new \Exception('Empty host');
        }
        $client = new Client();
        $response = $client->request('POST', $this->host.'/WTPay', [
            'headers' => ['Accept' => 'application/json',
                          'Content-type' => 'application/json', ],
            'json' => $this->prepareBody(),
        ]);
        $resultArr = json_decode($response->getBody(), true);
        if (isset($resultArr['payment']) && true == $resultArr['payment']) {
            $result = new PaymentCreation();
            $result->code = 200;
            if (isset($resultArr['payUrl'])) {
                $result->url = $resultArr['payUrl'];
            }
            if (isset($resultArr['html'])) {
                $result->html = $resultArr['html'];
            }

            return $result;
        } elseif (isset($resultArr['message'])) {
            throw new \Exception($resultArr['message']);
        }
        throw new \Exception('Failed to get recognized response '.print_r($resultArr));
    }

    public function notify(PaymentSetting $p, array $response): PaymentNotify
    {
        if (isset($response['transdata']) && isset($response['sign'])) {
            $transData = urldecode($response['transdata']);
            if (false == is_array($transData)) {
                $transData = json_decode($transData, true);
            }
            $sign = $response['sign'];
            //$sign = urldecode($response['sign']);
            //$sign = utf8_decode($sign);
            if ($this->checkMD5($transData, $p->md5Key, $sign)) {
                $result = new PaymentNotify();
                $result->code = 200;
                $result->message = $result['payment'];
                $result->orderNo = $result['order_no'];
                $result->orderAmount = $result['order_amount'];

                return $result;
            }
        }
        throw new \Exception('Failed to check MD5 from response '.print_r($response));
    }

    public function prepareBody()
    {
        if (!$this->readyToConnect) {
            throw new \Exception('Please setConnection first');
        }
        $paymentArr = [
            'order_no' => (string) $this->orderNo,
            'order_amount' => (string) $this->orderAmount,
            'order_time' => (string) $this->orderTime,
            'product_name' => (string) $this->productName,
            'product_code' => (string) $this->productCode,
            'user_no' => (string) $this->userNo,
            'notify_url' => (string) $this->notifyUrl ?: 'https://dev.33tech.cc/v1/paid/wangtung',
            'pay_type' => (string) $this->payType,
            'bank_code' => (string) $this->bankCode ?: '',
            'return_url' => (string) $this->returnUrl ?: 'https://dev.33tech.cc/member',
            'merchant_ip' => (string) $this->merchantIp ?: '',
            'bank_card' => (string) $this->bankCard ?: '', ];
        $credentialArr = $this->getCredentials();
        $paymentArr = array_merge($credentialArr, $paymentArr);
        $transdata = json_encode($paymentArr, JSON_UNESCAPED_SLASHES);
        $transdata = utf8_encode($transdata);

        return ['transdata' => urlencode($transdata),
                'sign' => urlencode($this->signMD5($paymentArr)),
                'signtype' => self::MD5, ];
    }

    public function getBanks(): array
    {
        return array_keys($this->bankCodeMap);
    }

    public function getConfig(): PaymentConfig
    {
        $c = new PaymentConfig();

        return $c->setCnName('万通支付')
                 ->setEnName('wantung')
                 ->setThirdParty([PaymentConfig::THIRD_PARTY_ALIPAY,
                                  PaymentConfig::THIRD_PARTY_WECHAT,
                                  PaymentConfig::THIRD_PARTY_QQ,
                                  PaymentConfig::THIRD_PARTY_GATEWAY,
                                  PaymentConfig::THIRD_PARTY_YLPAY,
                                  PaymentConfig::THIRD_PARTY_JDPAY, ])
                 ->setFieldAppNo()
                 ->setFieldMerchant()
                 ->setFieldMd5Key()
                 //->setFieldRsa()
                 ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_ALIPAY,
                                     [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5])
                 ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_WECHAT,
                                     [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5])
                 ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_QQ,
                                     [PaymentConfig::TRADE_SCAN])
                 ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_GATEWAY, [])
                 ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_YLPAY,
                                     [PaymentConfig::TRADE_SCAN])
                 ->setFieldTradeCode(PaymentConfig::THIRD_PARTY_JDPAY,
                                     [PaymentConfig::TRADE_SCAN]);
    }

    public function signMD5(array $paymentArr): string
    {
        try {
            $transArr = array_filter($paymentArr, function ($val) {
                return !empty($val);
            });
            ksort($transArr);
            $str = http_build_query($transArr)."&key={$this->key}";
            $str = urldecode($str);

            return $this->genUpperMD5($str);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function checkMD5(array $transData, string $key, string $sign): bool
    {
        $str = http_build_query($transData)."&key={$key}";

        return $this->genUpperMD5($str) == $sign;
    }

    public function getCredentials(): array
    {
        return ['appno_no' => $this->appnoNo,
                'merchant_code' => $this->merchantCode, ];
    }

    private function getPayType($thirdPartyType, $tradeType)
    {
        if (PaymentConfig::THIRD_PARTY_WECHAT == $thirdPartyType) {
            if (PaymentConfig::TRADE_H5 == $tradeType) {
                return self::WEIXIN_H5;
            }

            return self::WEIXIN;
        }
        if (PaymentConfig::THIRD_PARTY_ALIPAY == $thirdPartyType) {
            if (PaymentConfig::TRADE_H5 == $tradeType) {
                return self::ZHIFUBAO_H5;
            }

            return self::ZHIFUBAO;
        }
        if (PaymentConfig::THIRD_PARTY_QQ == $thirdPartyType) {
            return self::QQ;
        }
        if (PaymentConfig::THIRD_PARTY_GATEWAY == $thirdPartyType) {
            return self::WANGGUAN;
        }
        if (PaymentConfig::THIRD_PARTY_YLPAY == $thirdPartyType) {
            return self::YL;
        }
        if (PaymentConfig::THIRD_PARTY_JDPAY == $thirdPartyType) {
            return self::JD;
        }
    }

    private function genUpperMD5(string $str): string
    {
        return strtoupper(md5($str));
    }
}
