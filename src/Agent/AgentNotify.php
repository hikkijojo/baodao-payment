<?php

namespace Baodao\Payment\Agent;

class AgentNotify
{
    const STATUS_OK = 1;
    const STATUS_FAILED = 0;
    public $agentOrderNo;
    public $agentOrderStatus;
    public $message = '';
    public $orderAmount;
    public $orderNo;
    public $status = self::STATUS_FAILED;

    public function isSuccess():bool
    {
        return $this->status == self::STATUS_OK;
    }
}
