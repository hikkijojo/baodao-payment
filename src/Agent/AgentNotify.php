<?php

namespace Baodao\Payment\Agent;

class AgentNotify
{
    const STATUS_OK = 1;
    const STATUS_FAILED = 0;
    public $agentOrderNo;
    public $agentOrderStatus;
    private $failedMsg = '';
    public $orderAmount;
    public $orderNo;
    public $callbackAuth;
    private $status = self::STATUS_FAILED;

    public function isSuccess():bool
    {
        return $this->status == self::STATUS_OK;
    }
    public function setStatusOK()
    {
        $this->status = self::STATUS_OK;
    }

    public function setFailedMessage(string $str)
    {
        $this->status = self::STATUS_FAILED;
        $this->failedMsg = $str;
    }
    public function getFailedMessage()
    {
        return $this->failedMsg;
    }
}
