<?php

namespace Baodao\Payment\Tests\Agent;

use Baodao\Payment\Agent\AgentOrder;
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
        $this->setting->callBackAuth = md5($this->setting->orderNo.$this->setting->payee.$this->setting->bankCard);
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
