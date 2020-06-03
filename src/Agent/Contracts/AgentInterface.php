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
     * 接收處理三方代付付款回調
     *
     * @return AgentNotify
     */
    public function notifyResult(AgentSetting $setting, array $response): AgentNotify;

    /**
     * @param AgentSetting $setting
     * 提供假的回調，讓我們自己處理 三方間單失敗，不提供回調的流程
     * @return array
     */
    public function prepareFailedNotify(AgentSetting $setting): array;
}
