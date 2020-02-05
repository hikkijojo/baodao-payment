<?php

namespace Baodao\Payment\Tests;

use Baodao\Payment\FourthParty\Xinfa;
use PHPUnit\Framework\TestCase;

class XinfaTest extends TestCase
{
    /**
     * Test sending HTTP request to Xinfa.
     *
     * @return void
     */
    public function testCreate()
    {
        $merchantNo = 'XF201808160001';
        $md5Key = '9416F3C0E62E167DA02DC4D91AB2B21E';
        // it doesn't matter whether header, footer or line feeds are included
        $rsaPublicKey = "-----BEGIN PUBLIC KEY-----\r\nMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCrsYFumeMyrbPH0pe/9qQGG+yS3ofp7ooHdtqrDLumzm+x4va+9aQFIW6P12zBvmZvrPCIzFJZW9054Ucy04fEf5k42ldT4kbLSk4EInHOmcWTa6XvYHSUwDLOt79rxVSjNQbouOR3jqe4oBIVo5dSvpVs65ovYDB3A2ZdAZMC8QIDAQAB";
        // so does private key
        $rsaPrivateKey = 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAKSz130yE0nKWZwT5s/j8dTSPhoD/ITGGoX71ypSE2XEeDDsnhCO7yBp5srH9QLHo7TOCf8vGTS/Mol6IaZhsUqDYyNkWzS4UjPqGrueSF2P9CBOfj41to4qvxQIGuA+4FyNnxVBpIgsIlu8K3Aa0SjyS4L2OqTZx1EVEAtlb42PAgMBAAECgYEAi4sr2hDhMrXUsl5SQnTYYf43S4dxHXVS5432QQ8FDEYnpxvy2AiiJY5UUh6UQeSvmPKwmZpn+r67rKrjc7p1oFTezohQJ+VErchg7v+lzYJqgOqyMwJcbcLhXKqzcXpstnEHrNy5tAbEwqXdTyRJnC4UPbz7EO7U1BjCOhW+oxECQQDgSt4KGguaxpfwpXPQ50MuA2F3pCWknG8M4zrR0ccBMjQcmwTs2dWn5CccRzcO2qtu1JuTh15uWccL6AAq4eRdAkEAu/xq1JNttl63kVdxyqVvEP1yG0jLVtG8o/scBGi+cXuDRMFG+088M3IfOFAx67t5bhteZ0LMgrF+NQ4/xQLa2wJAGQ1Dr60pDqiP3/ka7oJmJoWKJWrYKYKvhKj8sOLVb3TEDU3jRvEtxArfs3Dg3W/fJgnpNpkwGvM8IEBRhHimoQJBAK+V/7r60blMEy4gfVsI1wsJkDFH9xXq5cZM4EiGBYw+D8iCt2g5BEQRTnPtBBPpkmx0B+Nvk1JnszifTJUaK40CQBFZVyP1LHdueyRtyoMV0wWV7o02zKIr+Fgg3a7AQHnWYtI+RhdVUdL6+DbGUSnPC3y9MWMSbSXIILX0lSJydFQ=';
        $orderNo = date('YmdHis').rand(10000, 99999);
        $thirdPartyPaymentType = 'WX_WAP';
        $amount = '100'; // 單位：人民幣元
        $notifyUrl = 'http://f1e1853a.ngrok.io/notify';
        $redirectUrl = 'http://f1e1853a.ngrok.io/redirect';

        $xinfa = new Xinfa();

        $xinfa
            ->setMerchantNumber($merchantNo)
            ->setMd5Key($md5Key)
            ->setRsaPublicKey($rsaPublicKey)
            ->setRsaPrivateKey($rsaPrivateKey)
            ->setOrderNumber($orderNo)
            ->setThirdPartyPaymentType($thirdPartyPaymentType)
            ->setAmount($amount)
            ->setNotifyUrl($notifyUrl)
            ->setRedirectUrl($redirectUrl);

        $res = $xinfa->create();

        $this->assertNotEmpty($res['url']);
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

        $md5Key = '9416F3C0E62E167DA02DC4D91AB2B21E';
        $rsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCrsYFumeMyrbPH0pe/9qQGG+yS3ofp7ooHdtqrDLumzm+x4va+9aQFIW6P12zBvmZvrPCIzFJZW9054Ucy04fEf5k42ldT4kbLSk4EInHOmcWTa6XvYHSUwDLOt79rxVSjNQbouOR3jqe4oBIVo5dSvpVs65ovYDB3A2ZdAZMC8QIDAQAB';
        $rsaPrivateKey = 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAKSz130yE0nKWZwT5s/j8dTSPhoD/ITGGoX71ypSE2XEeDDsnhCO7yBp5srH9QLHo7TOCf8vGTS/Mol6IaZhsUqDYyNkWzS4UjPqGrueSF2P9CBOfj41to4qvxQIGuA+4FyNnxVBpIgsIlu8K3Aa0SjyS4L2OqTZx1EVEAtlb42PAgMBAAECgYEAi4sr2hDhMrXUsl5SQnTYYf43S4dxHXVS5432QQ8FDEYnpxvy2AiiJY5UUh6UQeSvmPKwmZpn+r67rKrjc7p1oFTezohQJ+VErchg7v+lzYJqgOqyMwJcbcLhXKqzcXpstnEHrNy5tAbEwqXdTyRJnC4UPbz7EO7U1BjCOhW+oxECQQDgSt4KGguaxpfwpXPQ50MuA2F3pCWknG8M4zrR0ccBMjQcmwTs2dWn5CccRzcO2qtu1JuTh15uWccL6AAq4eRdAkEAu/xq1JNttl63kVdxyqVvEP1yG0jLVtG8o/scBGi+cXuDRMFG+088M3IfOFAx67t5bhteZ0LMgrF+NQ4/xQLa2wJAGQ1Dr60pDqiP3/ka7oJmJoWKJWrYKYKvhKj8sOLVb3TEDU3jRvEtxArfs3Dg3W/fJgnpNpkwGvM8IEBRhHimoQJBAK+V/7r60blMEy4gfVsI1wsJkDFH9xXq5cZM4EiGBYw+D8iCt2g5BEQRTnPtBBPpkmx0B+Nvk1JnszifTJUaK40CQBFZVyP1LHdueyRtyoMV0wWV7o02zKIr+Fgg3a7AQHnWYtI+RhdVUdL6+DbGUSnPC3y9MWMSbSXIILX0lSJydFQ=';
        $xinfaPayment = new Xinfa();
        $xinfaPayment
            ->setMd5Key($md5Key)
            ->setRsaPublicKey($rsaPublicKey)
            ->setRsaPrivateKey($rsaPrivateKey);

        $verifiedOrderNo = $xinfaPayment->notify($request);

        $this->assertEquals($verifiedOrderNo, $orderNo);
    }
}