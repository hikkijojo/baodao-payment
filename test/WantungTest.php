<?php

namespace Baodao\Payment\Tests;

use Baodao\Payment\WangtungCredential;
use Baodao\Payment\WantungOrder;
use Baodao\Payment\Wantung;
use DateTime;
use PHPUnit\Framework\TestCase;

class WantungTest extends TestCase
{
    private $credential;
    private $order;
    private $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->credential = new WangtungCredential('test1234',
                                                   'test1111',
                                                   'F1C14F3DEFF6A38450BA97161A3DF3CF');
        $this->order = new WantungOrder('order123456',
                                        50,
                                        DateTime::createFromFormat('Y-m- d H:i:s', '2020-02-01 15:45:59'),
                                        '10.0', '1', '51070173');
        $this->payment = new Wantung();
        $this->payment->setConnection($this->credential,
                                      $this->order,
                                      Wantung::WEIXIN_H5,
                                      'https://dev.33tech.cc/v1/paid/wangtung',
                                      'https://www.wantong-pay.com',
                                      'https://dev.33tech.cc/member',
                                      null,
                                      null,
                                      '36.203.104.105');
    }

    public function test_md5_sign()
    {
        $paymentArr = ['order_no' => 'W20190305170057XKIPL',
                       'order_amount' => '50',
                       'order_time' => DateTime::createFromFormat('Y-m-d H:i:s', '2020-02-01 15:45:59')
                                                 ->format('YmdHis'),
                       'product_name' => '10.0',
                       'product_code' => 1,
                       'user_no' => 51070173,
                       'pay_type' => WantungPayment::WEIXIN_H5,
                       'notify_url' => 1,
                       'return_url' => 1,
                       'merchant_ip' => '36.203.104.105', ];
        $paymentArr = array_merge($this->credential->getCredentials(), $paymentArr);
        $expected = '7D80D7D9BEB88982887ADD36EF605048';
        self::assertEquals($expected, $this->credential->signMD5($paymentArr));
    }

    public function test_md5_check()
    {
        $response = '{"pay_type":"10072","user_no":"Neo","product_name":"pidai","product_code":"product-123","order_no":"1507704879000","order_time":"2017-10-12T12:22:05.452Z","order_amount":0.1,"payment":"支付成功"}';
        $expected = '6FDD82978D36773AF95022F611442CA6';
        $actual = $this->credential->checkMD5(json_decode($response, true), $expected);
        self::assertTrue($actual);
    }

    public function test_md5_payment()
    {
        $actual = $this->payment->prepareBody();
        $transdata = '%7B%22appno_no%22%3A%22test1234%22%2C%22merchant_code%22%3A%22test1111%22%2C%22order_no%22%3A%22order123456%22%2C%22order_amount%22%3A%2250%22%2C%22order_time%22%3A%2220200201154559%22%2C%22product_name%22%3A%2210.0%22%2C%22product_code%22%3A%221%22%2C%22user_no%22%3A%2251070173%22%2C%22notify_url%22%3A%22https%3A%2F%2Fdev.33tech.cc%2Fv1%2Fpaid%2Fwangtung%22%2C%22pay_type%22%3A%22weixin-h5%22%2C%22bank_code%22%3A%22%22%2C%22return_url%22%3A%22https%3A%2F%2Fdev.33tech.cc%2Fmember%22%2C%22merchant_ip%22%3A%2236.203.104.105%22%2C%22bank_card%22%3A%22%22%7D';
        $sign = '938DA952D0F3FE849C88933A441789C2';
        self::assertEquals($transdata, $actual['transdata']);
        self::assertEquals($sign, $actual['sign']);
        self::assertEquals('MD5', $actual['signtype']);
    }

    public function test_md5_payment_weishin_h5()
    {
        $now = new DateTime();
        $this->order->setNo('order'.$now->format('Ymdhis'));
        $result = $this->payment->create();
        fwrite(STDOUT, print_r($result, true));
        self::assertNotEmpty($result['url']);
    }

    public function test_md5_payment_wangguan()
    {
        $now = new DateTime();
        $this->order->setNo('order'.$now->format('Ymdhis'));
        $this->payment->setConnection($this->credential,
                                      $this->order,
                                      WantungPayment::WANGGUAN,
                                      'https://dev.33tech.cc/v1/paid/wangtung',
                                      'https://www.wantong-pay.com',
                                      'https://dev.33tech.cc/member',
                                      1,
                                      null,
                                      '36.203.104.105');
        $result = $this->payment->create();
        fwrite(STDOUT, print_r($result, true));
        self::assertNotEmpty($result['url']);
    }
}
