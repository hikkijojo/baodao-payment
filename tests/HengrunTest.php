<?php

namespace Baodao\Payment\Tests;

use Baodao\Payment\FourthParty\Hengrun;
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

    public function test_create()
    {
        $now = new \DateTime();
        $params = [
            'merchant' => 'HR181031122711824',
            'order_no' => 'order' . rand(0, 9) . $now->format('Ymdhis'),
            'amount'   => '300',
            'payment'  => 'alipay',
            'tradeIp'  => '11.107.3.21',
        ];
        $actual = $this->payment->create($params);
        dd($actual);
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->payment = new Hengrun($this->md5Key);
    }

}
