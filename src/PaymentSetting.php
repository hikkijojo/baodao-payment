<?php

namespace Baodao\Payment;

class PaymentSetting
{
    public $appNo; //應用代號
    public $merchantNo; //商戶代號

    public $host; //連線位置
    public $md5Key; //md5 加密 key
    public $rsaPublicKey;
    public $rsaPrivateKey;

    public $orderNo; // 訂單號
    public $orderAmount; // 訂單金額 單元元
    public $orderTime; // 訂單日期 格式 DateTime，需自行 format
    public $productCode; // 產品代號
    public $productName; // 產品名稱
    public $userNo; // 使用者代號
    public $merchantIp; // 連線 ip

    public $thirdPartyType; // 三方支付類型
    public $tradeType; // 第三方接口模式

    public $notifyUrl; // 異步通知回調網址
    public $redirectUrl; // 結帳後用戶轉向網址

    public $bankCard; // 銀行卡號
    public $bankCode; // 銀行代碼

    const MD5='MD5';
    const RSA='RSA';

    public function __construct(array $fields)
    {
        $this->setProperties($fields);
    }

    private function snakeToCamel($var): string
    {
        $camel= str_replace('_', '', ucwords($var, '_'));
        return lcfirst($camel);
    }

    private function setProperties(array $fields)
    {
        foreach ($fields as $key => $val) {
            $newKey = $this->snakeToCamel($key);
            if (property_exists($this, $newKey)) {
                $this->{$newKey} = $val;
            }
        }
    }
}
