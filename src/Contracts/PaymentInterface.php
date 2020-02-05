<?php

namespace Baodao\Payment\Contracts;

use Baodao\Payment\PaymentConfig;
use Baodao\Payment\PaymentConnection;
use Baodao\Payment\PaymentCreation;
use Baodao\Payment\PaymentNotify;

interface PaymentInterface
{
    public function create():PaymentCreation;
    public function setConnection(PaymentConnection $connection);
    public function notify(array $response):PaymentNotify;
    public function getBanks();
    public function getConfig():PaymentConfig;
}
