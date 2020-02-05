<?php

namespace Baodao\Payment\Exceptions;

use Exception;
use Throwable;

class DepositPaymentFailedException extends Exception
{
    public function __construct($message = '存款单送出失败，请检查网络环境或稍待一会再试一次', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        return response()->json([
            'code' => config('httpCode.400.deposit_payment_failed'),
            'message' => $this->getMessage(),
        ], 400);
    }
}
