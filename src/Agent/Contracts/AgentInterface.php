<?php

namespace Baodao\Payment\Agent\Contracts;

use Baodao\Payment\Agent\AgentNotify;
use Baodao\Payment\Agent\AgentOrder;
use Baodao\Payment\Agent\AgentSetting;

interface AgentInterface
{
    public function createOrder(AgentSetting $connection): AgentOrder;

    public function notifyResult(array $response): AgentNotify;

    public function prepareFailedNotify(): array;
}
