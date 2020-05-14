<?php

namespace Baodao\Payment;

class PaymentSetting
{
    //Must 有設定就必帶的欄位
    //Optional 不帶有預設值的，我們也用不到，可以不帶

    public $appNo; //應用代號, Must
    public $merchantNo; //商戶代號, Must
    public $md5Key; //md5 加密 key, Must

    public $rsaPublicKey; //Must
    public $rsaPrivateKey; //Must

    public $orderNo; // 訂單號 Must
    public $orderAmount; // 訂單金額 單位 元 Must
    public $orderTime; // 訂單日期 格式 DateTime Must

    public $merchantIp; // 連線 ip Must
    public $thirdPartyType; // 三方支付類型 Must
    public $tradeType; // 第三方接口模式 Must

    public $notifyUrl; // 異步通知回調網址 Must
    public $redirectUrl; // 結帳後用戶轉向網址 Must
//----------------------------------------------------------------------------------------
    public $bankCard; // 銀行卡號 Optional 表單有的話，就需要
    public $bankCode; // 銀行代碼 Optional 表單有的話，就需要

    public $productCode; // 產品代號 Optional
    public $productName; // 產品名稱 Optional
    public $userNo; // 使用者代號 Optional

    public $host; //連線位置 Optional

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
        $now = new \DateTime();
        if (empty($this->productName)) {
            $this->productName = "Product".$now->format("YmdHis");
        }
        if (empty($this->productCode)) {
            $this->productCode = $this->productName;
        }
        if (empty($this->userNo)) {
            $this->userNo = "User".$now->format("YmdHis");
        }
    }
}
