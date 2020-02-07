<?php

namespace Baodao\Payment\Tests;

use Baodao\Payment\FourthParty\Xinfa;
use Baodao\Payment\PaymentConfig;
use Baodao\Payment\PaymentSetting;
use PHPUnit\Framework\TestCase;

class XinfaTest extends TestCase
{
    /**
     * @var PaymentSetting
     */
    private $setting;

    /**
     * @var Xinfa
     */
    private $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $params = [
            'merchant_no' => 'XF201808160001',
            'md5_key' => '9416F3C0E62E167DA02DC4D91AB2B21E',
            // it doesn't matter whether header, footer or line feeds are included
            'rsa_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCrsYFumeMyrbPH0pe/9qQGG+yS3ofp7ooHdtqrDLumzm+x4va+9aQFIW6P12zBvmZvrPCIzFJZW9054Ucy04fEf5k42ldT4kbLSk4EInHOmcWTa6XvYHSUwDLOt79rxVSjNQbouOR3jqe4oBIVo5dSvpVs65ovYDB3A2ZdAZMC8QIDAQAB',
            // so does private key
            'rsa_private_key' => 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAKSz130yE0nKWZwT5s/j8dTSPhoD/ITGGoX71ypSE2XEeDDsnhCO7yBp5srH9QLHo7TOCf8vGTS/Mol6IaZhsUqDYyNkWzS4UjPqGrueSF2P9CBOfj41to4qvxQIGuA+4FyNnxVBpIgsIlu8K3Aa0SjyS4L2OqTZx1EVEAtlb42PAgMBAAECgYEAi4sr2hDhMrXUsl5SQnTYYf43S4dxHXVS5432QQ8FDEYnpxvy2AiiJY5UUh6UQeSvmPKwmZpn+r67rKrjc7p1oFTezohQJ+VErchg7v+lzYJqgOqyMwJcbcLhXKqzcXpstnEHrNy5tAbEwqXdTyRJnC4UPbz7EO7U1BjCOhW+oxECQQDgSt4KGguaxpfwpXPQ50MuA2F3pCWknG8M4zrR0ccBMjQcmwTs2dWn5CccRzcO2qtu1JuTh15uWccL6AAq4eRdAkEAu/xq1JNttl63kVdxyqVvEP1yG0jLVtG8o/scBGi+cXuDRMFG+088M3IfOFAx67t5bhteZ0LMgrF+NQ4/xQLa2wJAGQ1Dr60pDqiP3/ka7oJmJoWKJWrYKYKvhKj8sOLVb3TEDU3jRvEtxArfs3Dg3W/fJgnpNpkwGvM8IEBRhHimoQJBAK+V/7r60blMEy4gfVsI1wsJkDFH9xXq5cZM4EiGBYw+D8iCt2g5BEQRTnPtBBPpkmx0B+Nvk1JnszifTJUaK40CQBFZVyP1LHdueyRtyoMV0wWV7o02zKIr+Fgg3a7AQHnWYtI+RhdVUdL6+DbGUSnPC3y9MWMSbSXIILX0lSJydFQ=',
            'order_no' => date('YmdHis').rand(10000, 99999),
            'order_amount' => 100,
            'third_party_type' => PaymentConfig::THIRD_PARTY_WECHAT,
            'trade_type' => PaymentConfig::TRADE_WAP,
            'notify_url' => 'https://dev.33tech.cc/v1/payment/notify/xinfa',
            'redirect_url' => 'https://dev.33tech.cc/member',
        ];
        $this->setting = new PaymentSetting($params);
        $this->payment = new Xinfa();
    }

    public function testGetConfig()
    {
        $expected = [
            'cn_name' => '鑫发支付',
            'en_name' => 'xinfa',
            'is_alipay' => 1,
            'is_wechat' => 1,
            'is_gateway' => 0,
            'is_qqpay' => 1,
            'is_unionpay' => 1,
            'is_jdpay' => 1,
            'is_ylpay' => 1,
        ];
        $expectedFields = [
            'merchant' => '商户号',
            'md5_key' => 'MD5 密钥',
            'rsa_pri' => 'RSA 私钥',
            'rsa_pub' => 'RSA 公钥',
            'trade_code' => [
                'alipay' => ['scan', 'wap'],
                'jdpay' => ['scan', 'wap'],
                'qqpay' => ['scan', 'wap'],
                'unionpay' => ['scan'],
                'wechat' => ['h5', 'scan', 'wap'],
                'ylpay' => ['wap'],
            ],
        ];

        $actual = $this->payment->getConfig()->toArray();

        foreach ($expected as $key => $val) {
            if ('fields' !== $key) {
                self::assertEquals($expected[$key], $actual[$key]);
            }
        }

        $actualFields = json_decode($actual['fields'], true);

        $this->assertEquals($expectedFields, $actualFields);
    }

    /**
     * Test sending HTTP request to Xinfa.
     *
     * @return void
     */
    public function testCreate()
    {
        $result = $this->payment->create($this->setting);
        fwrite(STDERR, print_r($result, true));
        self::assertNotEmpty($result->url);
    }

    /**
     * Test receiving async notificaiton from Xinfa.
     *
     * @return void
     */
    public function testNotify()
    {
        $data = 'FWnbArKKMOsgeb2a594A8bM3J9rzg3o9xZ11LHhdM85u%2BtnEEsPM%2BXGVAqdka2UTgRGx423pGxgvHwpX885HfdjQxfNn'.
            'qULf8aj84bC2V7Nwgc%2FQdKfaPuM%2BtUHL2DpKYDDvqPiDMTlt%2B7bEd8uAOcTUezUoY86PoYqfCzuBKx5oJU3iTxwXfRCeiYH'.
            'ttNkqeTiuzbaEBV07Q8xJughNY4%2BABjFYqpaMjSj7354Xd4Q5pRQ7zU0jpOvniFFnz3k4%2FGtQwEeg5IX0a73xDIx2Dn0vcgBg'.
            'AS4BN7GMEgO3QdJ6MepsbUuRDDR%2FwJlN1dc26wpUa4PUlcYQi8EgEvG12w%3D%3D';
        $merchantNo = 'XF201806040000';
        $orderNo = '20180801102543215VCqeRK';

        $request = [
            'data' => $data,
            'merchNo' => $merchantNo,
            'orderNo' => $orderNo,
        ];

        $this->setting->md5Key = '9416F3C0E62E167DA02DC4D91AB2B21E';
        $this->setting->rsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCrsYFumeMyrbPH0pe/9qQGG+yS3ofp7ooHdtqrDLumzm+x4va+9aQFIW6P12zBvmZvrPCIzFJZW9054Ucy04fEf5k42ldT4kbLSk4EInHOmcWTa6XvYHSUwDLOt79rxVSjNQbouOR3jqe4oBIVo5dSvpVs65ovYDB3A2ZdAZMC8QIDAQAB';
        $this->setting->rsaPrivateKey = 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAKSz130yE0nKWZwT5s/j8dTSPhoD/ITGGoX71ypSE2XEeDDsnhCO7yBp5srH9QLHo7TOCf8vGTS/Mol6IaZhsUqDYyNkWzS4UjPqGrueSF2P9CBOfj41to4qvxQIGuA+4FyNnxVBpIgsIlu8K3Aa0SjyS4L2OqTZx1EVEAtlb42PAgMBAAECgYEAi4sr2hDhMrXUsl5SQnTYYf43S4dxHXVS5432QQ8FDEYnpxvy2AiiJY5UUh6UQeSvmPKwmZpn+r67rKrjc7p1oFTezohQJ+VErchg7v+lzYJqgOqyMwJcbcLhXKqzcXpstnEHrNy5tAbEwqXdTyRJnC4UPbz7EO7U1BjCOhW+oxECQQDgSt4KGguaxpfwpXPQ50MuA2F3pCWknG8M4zrR0ccBMjQcmwTs2dWn5CccRzcO2qtu1JuTh15uWccL6AAq4eRdAkEAu/xq1JNttl63kVdxyqVvEP1yG0jLVtG8o/scBGi+cXuDRMFG+088M3IfOFAx67t5bhteZ0LMgrF+NQ4/xQLa2wJAGQ1Dr60pDqiP3/ka7oJmJoWKJWrYKYKvhKj8sOLVb3TEDU3jRvEtxArfs3Dg3W/fJgnpNpkwGvM8IEBRhHimoQJBAK+V/7r60blMEy4gfVsI1wsJkDFH9xXq5cZM4EiGBYw+D8iCt2g5BEQRTnPtBBPpkmx0B+Nvk1JnszifTJUaK40CQBFZVyP1LHdueyRtyoMV0wWV7o02zKIr+Fgg3a7AQHnWYtI+RhdVUdL6+DbGUSnPC3y9MWMSbSXIILX0lSJydFQ=';

        $verifiedOrderNo = $this->payment->notify($this->setting, $request);

        $this->assertEquals($verifiedOrderNo, $orderNo);
    }
}
