<?php

namespace Baodao\Payment\Tests\Agent;

use Baodao\Payment\Agent\ThirdParty\AePay;
use DateTime;
use PHPUnit\Framework\TestCase;

class AePayTest extends TestCase
{
    public function test_create_order()
    {
        $order = [];

        $now = new DateTime();
        $order['order_no']=  'order' . rand(0, 9) . $now->format('YmdHis');
        $order['amount'] = 100.01;
        $order['full_name'] = 'Tester';
        $order['bank_card'] = '999999999999';

        $aePay = new AePay();
        $response = $aePay->createOrder($order, 'https://dev.33tech.cc/v1/third-party-payment');

        fwrite(STDOUT, print_r($response, true));

        self::assertNotEmpty($response['platform_orderid']);
        self::assertEquals($response['order_no'], $order['order_no']);
        $orderNo = $response['order_no'];
        sleep(5);
        $response = $aePay->checkOrder($orderNo);

        fwrite(STDOUT, print_r($response, true));

        self::assertIsNumeric($response['order_status']);
    }

    public function test_get_balance()
    {
        $aePay = new AePay();
        $response = $aePay->getBalance();

        fwrite(STDOUT, print_r($response, true));

        self::assertIsNumeric($response['balance']);
        self::assertIsNumeric($response['lockbalance']);
    }
}
