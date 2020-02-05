<?php

namespace Baodao\Payment\Enums;

final class PaymentEnum
{
    const RSA_PUB = 'RSA 公钥';
    const RSA_PRI = 'RSA 私钥';
    const MD5 = 'MD5 密钥';
    const MERCHANT = '商户号';
    const APP_NO = '应用号';

    const TRADE_SCAN = 'scan';
    const TRADE_WAP = 'wap';
    Const TRADE_H5 = 'h5';
}