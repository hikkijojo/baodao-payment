<?php

namespace Baodao\Payment\Tests;

use Baodao\Payment\FourthParty\Wantung;
use Baodao\Payment\PaymentConfig;
use Baodao\Payment\PaymentConnection;
use DateTime;
use PHPUnit\Framework\TestCase;

class WantungTest extends TestCase
{
    private $connection;
    private $payment;

    public function test_config()
    {
        $actual = $this->payment->getConfig()->toArray();
        $expected = ['cn_name'     => '万通支付',
                     'en_name'     => 'wantung',
                     'is_alipay'   => 1,
                     'is_wechat'   => 1,
                     'is_gateway'  => 1,
                     'is_qqpay'    => 1,
                     'is_unionpay' => 0,
                     'is_jdpay'    => 1,
                     'is_ylpay'    => 1];
        $expectedFields = ['merchant'   => '商户号', 'app_no' => '应用号', 'md5_key' => 'MD5 密钥',
                           'trade_code' => ['alipay'  => ['h5', 'scan'], 'wechat' => ['h5', 'scan'],
                                            'qqpay'   => ['scan'],
                                            'gateway' => [], 'ylpay' => ['scan'], 'jdpay' => ['scan']]];
        self::assertJson($actual['fields']);
    }

    public function test_notify()
    {
        $json = '{"transdata":"%7B%22pay_type%22%3A%2210072%22%2C%22user_no%22%3A%22Neo%22%2C%22product_name%22%3A%22pidai%22%2C%22product_code%22%3A%22product-123%22%2C%22order_no%22%3A%221507704879000%22%2C%22order_time%22%3A%222017-10-12T12%3A22%3A05.452Z%22%2C%22order_amount%22%3A0.1%2C%22payment%22%3A%22%E6%94%AF%E4%BB%98%E6%88%90%E5%8A%9F%22%7D", "sign":"RLiujUr8AHm7V%2BNfPmdzkZgFuwiluyxJJNkso9nep3YY2wCO4lCh444Nk%2Fr1SxN2CxmpJ333DuaZfNPsBd647Q%2FYpH89fIYz3A07H7NE8EWN008FNBwDhBr6N3hyisJMNdwsJof7D3tCTtc28adOlC5k1naToseOP3x38H%2Fe5Vg%3D" }';
        $response = json_decode($json, true);
        $paymentNotify = $this->payment->notify($response);
        self::assertEquals($paymentNotify->code, 200);
        self::assertNotEmpty($paymentNotify->orderNo);
        self::assertNotEmpty($paymentNotify->message);
        self::assertTrue($paymentNotify->orderAmount > 0 );
    }

    public function test_md5_create()
    {
        $actual = $this->payment->prepareBody();
        $transdata = '%7B%22appno_no%22%3A%22test1234%22%2C%22merchant_code%22%3A%22test1111%22%2C%22order_no%22%3A%22order123456%22%2C%22order_amount%22%3A%2250%22%2C%22order_time%22%3A%2220200201154559%22%2C%22product_name%22%3A%2210.0%22%2C%22product_code%22%3A%221%22%2C%22user_no%22%3A%2251070173%22%2C%22notify_url%22%3A%22https%3A%2F%2Fdev.33tech.cc%2Fv1%2Fpaid%2Fwangtung%22%2C%22pay_type%22%3A%22weixin-h5%22%2C%22bank_code%22%3A%22%22%2C%22return_url%22%3A%22https%3A%2F%2Fdev.33tech.cc%2Fmember%22%2C%22merchant_ip%22%3A%2236.203.104.105%22%2C%22bank_card%22%3A%22%22%7D';
        $sign = '938DA952D0F3FE849C88933A441789C2';
        self::assertEquals($transdata, $actual['transdata']);
        self::assertEquals($sign, $actual['sign']);
        self::assertEquals('MD5', $actual['signtype']);
    }

    public function test_md5_create_payment_weishin_h5()
    {
        $now = new DateTime();
        $this->connection->orderNo = 'order' . rand(0, 9) . $now->format('Ymdhis');
        $this->payment->setConnection($this->connection);
        $result = $this->payment->create();
        fwrite(STDOUT, print_r($result, true));
        self::assertNotEmpty($result->url);
    }

    public function test_md5_create_payment_alipay()
    {
        $now = new DateTime();
        $this->connection->orderNo = 'order' . rand(0, 9) . $now->format('Ymdhis');
        $this->connection->thirdPartyType = PaymentConfig::THIRD_PARTY_ALIPAY;
        $this->connection->tradeType = PaymentConfig::TRADE_H5;
        $this->connection->bankCode = 1;
        $this->payment->setConnection($this->connection);
        $result = $this->payment->create();
        fwrite(STDOUT, print_r($result, true));
        self::assertNotEmpty($result->url);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $params = ['app_no'           => 'test1234',
                   'merchant_no'      => 'test1111',
                   'md5_key'          => 'F1C14F3DEFF6A38450BA97161A3DF3CF',
                   'order_no'         => 'order123456',
                   'order_amount'     => 50,
                   'order_time'       => DateTime::createFromFormat('Y-m- d H:i:s', '2020-02-01 15:45:59'),
                   'product_name'     => '10.0',
                   'product_code'     => '1',
                   'user_no'          => '51070173',
                   'merchant_ip'      => '36.203.104.105',
                   'third_party_type' => PaymentConfig::THIRD_PARTY_WECHAT,
                   'trade_type'       => PaymentConfig::TRADE_H5,
                   'notify_url'       => 'https://dev.33tech.cc/v1/paid/wangtung',
                   'redirect_url'     => 'https://dev.33tech.cc/member',
                   'host'             => 'https://www.wantong-pay.com',
                   'bank_code'        => null,
                   'bank_card'        => null];
        $this->connection = new PaymentConnection($params);
        $this->payment = new Wantung();
        $this->payment->setConnection($this->connection);
    }
}
