<?php

namespace Baodao\Payment\Agent;

class AgentOrder
{
    const STATUS_OK = 1;
    const STATUS_FAILED = 0;
    public $agentOrderNo;
    public $amount;
    public $orderNo;
    private $failedMsg = '';
    private $status = self::STATUS_FAILED;

    public function isSuccessCreated(): bool
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
