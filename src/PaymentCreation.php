<?php

namespace Baodao\Payment;

class PaymentCreation
{
    public $code;
    public $message;
    public $url;
    public $html;
    public function __construct($code=null, $message=null, $url=null, $html=null)
    {
        $this->code = $code;
        $this->message =$message;
        $this->url = $url;
        $this->html = $html;
    }
    public function toArray():array
    {
        $arr = [];
        foreach ($this as $key=>$val) {
            $arr[$key]=$val;
        }
        return $arr;
    }
}
