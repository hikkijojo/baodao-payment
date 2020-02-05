<?php

namespace Baodao\Payment;

use DateTime;

class WantungOrder
{
    private $amount;
    private $no;
    private $product_code;
    private $product_name;
    private $time;
    private $user_no;

    public function __construct($no, $amount, DateTime $time, $productName, $productCode, $userNo)
    {
        if ($amount < 0.01) {
            throw new \Exception("Amount has to be larger than 0.01, but $amount given");
        }
        if (strlen($no) > 30) {
            throw new \Exception('order_no length should be smaller than 31');
        }
        if (strlen($this->user_no) > 20) {
            throw new \Exception('user_no length should be smaller than 20');
        }
        if (strlen($this->product_code) > 24) {
            throw new \Exception('product_code length should be smaller than 24');
        }

        $this->no = $no;
        $this->amount = $amount;
        $this->time = $time->format('YmdHis');
        $this->product_name = $productName;
        $this->product_code = $productCode;
        $this->user_no = $userNo;
    }

    public function setNo($val)
    {
        if (strlen($val) > 30) {
            throw new \Exception('order_no length should be smaller than 31');
        }
        $this->no = $val;
    }

    public function getUserNo()
    {
        return $this->user_no;
    }

    public function getProductName()
    {
        return $this->product_name;
    }

    public function getProductCode()
    {
        return $this->product_code;
    }

    public function getNo()
    {
        return $this->no;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getAmount()
    {
        return $this->amount;
    }
}
