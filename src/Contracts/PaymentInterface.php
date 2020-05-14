<?php

namespace Baodao\Payment\Contracts;

use Baodao\Payment\PaymentConfig;
use Baodao\Payment\PaymentSetting;
use Baodao\Payment\PaymentCreation;
use Baodao\Payment\PaymentNotify;

interface PaymentInterface
{
    public function create(PaymentSetting $connection):PaymentCreation;
    public function notify(PaymentSetting $connection, array $response):PaymentNotify;
    public function getBanks();
    public function getConfig():PaymentConfig;
}
