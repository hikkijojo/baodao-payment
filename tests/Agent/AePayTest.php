<?php

namespace Baodao\Payment\Tests\Agent;

use Baodao\Payment\Agent\AgentSetting;
use Baodao\Payment\Agent\ThirdParty\AePay;
use DateTime;
use PHPUnit\Framework\TestCase;

class AePayTest extends TestCase
{
    const MERCHANT_ID = 'AEMYUM5HB1';
    const MD5_KEY = 'kG312HljR26bS4sJ772D4Y67WE';

    private $setting;

    public function test_create_order()
    {
        $now = new DateTime();
        $this->setting->orderNo = 'order' . rand(0, 9) . $now->format('YmdHis');
        $this->setting->orderAmount = 100.01;
        $this->setting->payee = 'Tester';
        $this->setting->bankCard = '999999999999';
        $this->setting->notifyUrl = 'https://dev-admin.33tech.cc/v1/third-party-payment/1/orderNo';
        $aePay = new AePay();
        $agentOrder = $aePay->createOrder($this->setting);
        fwrite(STDOUT, print_r($agentOrder, true));
        if ($agentOrder->isSuccessCreated()) {
            sleep(5);
            $agentNotify = $aePay->checkOrder($this->setting, $agentOrder->orderNo);
            fwrite(STDOUT, print_r($agentNotify, true));
            self::assertNotEmpty($agentOrder->agentOrderNo);
            self::assertEquals($agentOrder->orderNo, $this->setting->orderNo);
            self::assertEquals($agentOrder->amount, $this->setting->orderAmount);
        } else {
            self::assertFalse($agentOrder->isSuccessCreated());
            self::assertIsString($agentOrder->getFailedMessage());
            self::assertNotEmpty($agentOrder->getFailedMessage());
        }
    }

    public function test_notify()
    {
        $fakeResponse = [];
        $fakeResponse["completetime"] = "1591169212";
        $fakeResponse["create_time"] = "1591169210";
        $fakeResponse["merchant_id"] = "AEMYUM5HB1";
        $fakeResponse["msg"] = "卡号错误";
        $fakeResponse["nonce"] = "8Un6keC";
        $fakeResponse["order_money"] = "100.01";
        $fakeResponse["order_no"] = "order520200603072649";
        $fakeResponse["order_status"] = "2";
        $fakeResponse["order_sxf"] = "1";
        $fakeResponse["ordertype"] = "DF_ZFBYHK";
        $fakeResponse["otherparams"] = "0e617a821e9ba6023827779ee0906341";
        $fakeResponse["platform_orderid"] = "1591169210209442";
        $fakeResponse["signature"] = "01195D79BC80F0F332A93313983B2A69";
        $fakeResponse["timestamp"] = "1591169212";
        $aePay = new AePay();
        $agentNotify = $aePay->notifyResult($this->setting, $fakeResponse);
        self::assertFalse($agentNotify->isSuccess());
        self::assertNotEquals('签名验证失败', $agentNotify->getFailedMessage());
        self::assertNotEmpty($agentNotify->getFailedMessage());
    }

    public function test_get_balance()
    {
        $aePay = new AePay();
        $response = $aePay->getBalance($this->setting);
        fwrite(STDOUT, print_r($response, true));
        self::assertIsNumeric($response['balance']);
        self::assertIsNumeric($response['lockbalance']);
    }

    protected function setUp(): void
    {
        $this->setting = new AgentSetting();
        $this->setting->merchantNo = self::MERCHANT_ID;
        $this->setting->md5Key = self::MD5_KEY;
    }
}
