<?php

namespace Baodao\Payment\Agent\Contracts;

use Baodao\Payment\Agent\AgentNotify;
use Baodao\Payment\Agent\AgentOrder;
use Baodao\Payment\Agent\AgentSetting;

interface AgentInterface
{
    /**
     * @param AgentSetting $setting
     * 請求三方代付建單
     * @return AgentOrder
     */
    public function createOrder(AgentSetting $setting): AgentOrder;

    /**
     * @param AgentSetting $setting
     * @param array        $response
     * 接收處理三方代付付款回調，驗證 Signature
     *
     * @return AgentNotify
     */
    public function notifyResult(AgentSetting $setting, array $response): AgentNotify;
}
