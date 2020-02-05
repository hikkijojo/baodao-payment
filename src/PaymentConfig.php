<?php

namespace Baodao\Payment;

class PaymentConfig
{
    const THIRD_PARTY_WECHAT = 'wechat';
    const THIRD_PARTY_ALIPAY = 'alipay';
    const THIRD_PARTY_GATEWAY = 'gateway';
    const THIRD_PARTY_QQ = 'qqpay';
    const THIRD_PARTY_UNIONPAY = 'unionpay'; // 網關
    const THIRD_PARTY_JDPAY = 'jdpay';       // QQ
    const THIRD_PARTY_YLPAY = 'ylpay';       //雲閃付

    const TRADE_TYPES = [self::TRADE_H5,
                         self::TRADE_SCAN]; // 京東
    const TRADE_SCAN = 'scan';              // 銀聯
    const TRADE_H5 = 'h5';

    const RSA_PUB = 'RSA 公钥';
    const RSA_PRI = 'RSA 私钥';
    const MD5 = 'MD5 密钥';
    const MERCHANT = '商户号';
    const APP_NO = '应用号';
    const THIRD_PARTY_ENABLE = 1;

    public $cnName;
    public $enName;
    public $fields;

    public $isAlipay = 0;
    public $isGateway = 0;
    public $isJdpay = 0;
    public $isQqpay = 0;
    public $isUnionpay = 0;
    public $isWechat = 0;
    public $isYlpay = 0;


    public $alipay = [];
    public $gateway = [];
    public $jdpay = [];
    public $qq = [];
    public $unionpay = [];
    public $wechat = [];
    public $ylpay = [];

    public function __construct()
    {
        $this->fields = ['trade_code' => []];
    }

    public function toArray()
    {
        $attrs = ['cn_name'=>$this->cnName,
                   'en_name'=>$this->enName];
        foreach ($this as $key => $val) {
            if (substr($key, 0, 2) == 'is') {
                $newKey = $this->camelToSnake($key);
                $attrs[$newKey] = $val;
            }
        }
        $fields = ['fields' => json_encode($this->fields)];
        return array_merge($attrs, $fields);
    }

    public function setCnName($val)
    {
        $this->cnName  = $val;
        return $this;
    }

    public function setEnName($val)
    {
        $this->enName  = $val;
        return $this;
    }

    public function setThirdParty(array $types)
    {
        foreach ($types as $val) {
            $key = 'is'.ucfirst($val);
            if (property_exists($this, $key)) {
               $this->{$key} = self::THIRD_PARTY_ENABLE;
            }
        }
    return $this;
    }

    public function setFieldMerchant()
    {
        $this->fields['merchant'] = self::MERCHANT;
        return $this;
    }
    public function setFieldAppNo()
    {
        $this->fields['app_no'] = self::APP_NO;
        return $this;
    }
    public function setFieldMd5Key()
    {
        $this->fields['md5_key'] = self::MD5;
        return $this;
    }
    public function setFieldRsa()
    {
        $this->fields['rsa_pub'] = self::RSA_PUB;
        $this->fields['rsa_pri'] = self::RSA_PRI;
        return $this;
    }

    public function setFieldTradeCode($thirdPartyType, array $tradeCode)
    {
       $this->fields['trade_code'][$thirdPartyType] = $tradeCode;
        return $this;
    }

    private function camelToSnake($var)
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $var));
    }
}
