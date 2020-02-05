<?php

namespace Baodao\Payment;

class PaymentCreation
{
    public $url;
    public $html;
    public function __construct($url=null, $html=null)
    {
        $this->url = $url;
        $this->html = $html;
    }
    public function toArray():array
    {
        $arr = [];
        foreach($this as $key=>$val) {
            $arr[$key]=$val;
        }
        return $arr;
    }
}
