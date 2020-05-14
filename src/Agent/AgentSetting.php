<?php

namespace Baodao\Payment\Agent;

class AgentSetting
{
    //Must 有設定就必帶的欄位
    //Optional 不帶有預設值的，我們也用不到，可以不帶
    const MD5 = 'MD5';         //商戶代號, Must
    public $bankCard;          //md5 加密 key, Must
    public $md5Key;            // 訂單號 Must
    public $merchantNo;        // 訂單金額 單位 元 Must
    public $notifyUrl;         // 訂單日期 格式 DateTime Must
    public $orderAmount;       // 收款人名稱
    public $orderNo;           // 收款人卡號
    public $orderTime;         // 異步通知回調網址 Must
    public $payee;

    public function __construct(array $fields = [])
    {
        $this->setProperties($fields);
    }

    private function snakeToCamel($var): string
    {
        $camel = str_replace('_', '', ucwords($var, '_'));

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
