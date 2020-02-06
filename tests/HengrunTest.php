<?php

namespace Baodao\Payment\Tests;

use Baodao\Payment\FourthParty\Hengrun;
use Baodao\Payment\PaymentConfig;
use Baodao\Payment\PaymentSetting;
use PHPUnit\Framework\TestCase;

class HengrunTest extends TestCase
{
    private $md5Key = "F70FAB45528A72774D597999A5FF534D";
    private $payment;

    public function test_sign()
    {
        $params = [
            'appID'        => '181031122711824',
            //'appID'        => '180305164013272',
            'notifyUrl'    => 'http://xxxx.com/pay/apply.shtml',
            'outTradeNo'   => '18080141679932823007',
            'productTitle' => 'vivo',
            'randomNo'     => '8417',
            'totalAmount'  => '100',
            'tradeCode'    => '60001',
            'tradeIP'      => '11.107.3.21',
        ];
        ksort($params);
        $actual = $this->payment->encryptSign($params, $this->md5Key);
        $expected = "E22D02AFD75D0374D3EA8D142FC2B219";
        self::assertEquals($expected, $actual);
    }

    public function test_config()
    {
        $fields = [
            'merchant'   => PaymentConfig::MERCHANT,
            'md5_key'    => PaymentConfig::MD5,
            'trade_code' => [
                'alipay'  => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
                'wechat'  => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
                'qqpay'   => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
                'gateway' => [],
                'ylpay'   => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
                'jdpay'   => [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_H5],
            ]
        ];
        $expected =
            [
                'cn_name'     => '恒润支付',
                'en_name'     => 'hengrun',
                'is_alipay'   => 1,
                'is_wechat'   => 1,
                'is_gateway'  => 1,
                'is_qqpay'    => 1,
                'is_unionpay' => 0,
                'is_jdpay'    => 1,
                'is_ylpay'    => 1,
                'fields'      => json_encode($fields)
            ];
        $actual = $this->payment->getConfig()->toArray();
        foreach ($expected as $key=>$val) {
            if($key !== 'fields') {
                self::assertEquals($expected[$key], $actual[$key]);
            }
        }
    }

    public function test_create()
    {
        $now = new \DateTime();
        $setting = new PaymentSetting([]);
        $setting->merchantNo = 'HR181031122711824';
        $setting->orderNo = 'order' . rand(0, 9) . $now->format('Ymdhis');
        $setting->orderAmount = '300';
        $setting->thirdPartyType = 'alipay';
        $setting->merchantIp = '11.107.3.21';
        $setting->productName = 'TestProduct';
        $setting->md5Key = $this->md5Key;
        $setting->notifyUrl  = 'http://localhost/v1/payment/notify/'.strtolower((new \ReflectionClass($this->payment))->getShortName());
        $actual = $this->payment->create($setting);

        fwrite(STDOUT, print_r($actual, true));
        self::assertNotEmpty($actual->url);
        /*
         *
         * [
  "appID" => "HR181031122711824"
  "outTradeNo" => "order220200205055910"
  "payURL" => "http://www.lantunpay.com/alipayment.html?p=LB7pQfErRTqrUrHBAW5gkTgUVQkfEgGj_iyK7PlzUPI3n3Tpixe-rgKhrewuMMXZ&t=alipay"
  "sign" => "3A5C7D97A0770226C0112878AF0B56B1"
  "stateCode" => "0000"
  "stateInfo" => "提交成功"
]
         * */
    }

//    public function test_notify()
//    {
//    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->payment = new Hengrun();
    }

}
