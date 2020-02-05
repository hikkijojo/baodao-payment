<?php

namespace Baodao\Payment\FourthParty;

class WangtungCredential
{
    const MD5 = 'MD5';
    private $appnoNo;
    private $key;
    private $merchantCode;
    private $rsaPri;
    private $rsaPub;

    public function __construct($appnoNo, $merchantCode, $key, $rsaPub = null, $rsaPri = null)
    {
        $this->appnoNo = $appnoNo;
        $this->merchantCode = $merchantCode;
        $this->key = $key;
        $this->rsaPub = $rsaPub;
        $this->rsaPri = $rsaPri;
    }

    public function signMD5(array $paymentArr): string
    {
        try {
            $transArr = array_filter($paymentArr, function ($val) {return !empty($val); });
            ksort($transArr);
            $str = http_build_query($transArr)."&key={$this->key}";
            $str = urldecode($str);

            return $this->genUpperMD5($str);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function checkMD5(array $transData, string $sign): bool
    {
        $str = http_build_query($transData)."&key={$this->key}";

        return $this->genUpperMD5($str) == $sign;
    }

    public function getCredentials(): array
    {
        return ['appno_no' => $this->appnoNo,
                'merchant_code' => $this->merchantCode, ];
    }

    private function genUpperMD5(string $str): string
    {
        return strtoupper(md5($str));
    }
}
