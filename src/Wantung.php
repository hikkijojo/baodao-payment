<?php

namespace Baodao\Payment;

use GuzzleHttp\Client;

class Wantung
{
    const PAY_TYPES = [self::WEIXIN,
                       self::WEIXIN_H5,
                       self::ZHIFUBAO,
                       self::ZHIFUBAO_H5,
                       self::QQ,
                       self::WANGGUAN,
                       //self::KUAIJIE,
                       self::YL,
                       self::JD, ];
    const WEIXIN = 'weixin';           //微信
    const WEIXIN_H5 = 'weixin-h5';     //微信H5
    const ZHIFUBAO = 'zhifubao';       //支付宝
    const ZHIFUBAO_H5 = 'zhifubao-h5'; //支付宝(原生)
    const QQ = 'qq';                   //QQ支付
    const WANGGUAN = 'wangguan';       //网关
    const KUAIJIE = 'kuaijie';         //快捷, 目前不使用的
    const YL = 'yl';                   //银联扫码
    const JD = 'jd';                   //京东
    /**
     * @var string
     */
    private $bankCard;
    /**
     * @var string
     */
    private $bankCode;
    /**
     * @var WangtungCredential
     */
    private $credential;
    /**
     * @var string
     */
    private $host;
    /**
     * @var string
     */
    private $merchantIp;
    /**
     * @var string
     */
    private $notifyUrl;
    /**
     * @var WantungOrder
     */
    private $order;
    /**
     * @var string
     */
    private $payType;
    /**
     * @var string
     */
    private $returnUrl;

    public function __construct(
        WangtungCredential $credential,
        WantungOrder $order,
        string $payType,
        string $notifyUrl = null,
        string $host = null,
        string $returnUrl = null,
        string $bankCode = null,
        string $bankCard = null,
        string $merchantIp = null
    ) {
        if (false == in_array($payType, self::PAY_TYPES)) {
            throw new \Exception("Unknown pay_type $payType");
        }
        if (empty($returnUrl)) {
            throw new \Exception('Empty returnUrl');
        }
        if (self::WANGGUAN == $payType && empty($bankCard)) {
            throw new \Exception('网关支付时，此字段必填，各银行编码详见 https://www.showdoc.cc/ufo?page_id=818330595764566');
        }
        if (self::WEIXIN_H5 == $payType && empty($merchantIp)) {
            throw new \Exception('微信H5，此字段为必填字段');
        }
        $this->credential = $credential;
        $this->order = $order;
        $this->payType = $payType;
        $this->notifyUrl = $notifyUrl;
        $this->returnUrl = $returnUrl;
        $this->bankCode = $bankCode;
        $this->bankCard = $bankCard;
        $this->merchantIp = $merchantIp;
        $this->host = $host;
    }

    public function create(): array
    {
        if (empty($this->host)) {
            throw new \Exception('Empty host');
        }
        $client = new Client();
        $response = $client->request('POST', $this->host.'/WTPay', [
            'headers' => ['Accept' => 'application/json',
                          'Content-type' => 'application/json', ],
            'json' => $this->prepareBody(),
        ]);
        $resultArr = json_decode($response->getBody(), true);
        if (isset($resultArr['payment']) && true == $resultArr['payment']) {
            $result = [];
            if (isset($resultArr['payUrl'])) {
                $result['url'] = $resultArr['payUrl'];
            }
            if (isset($resultArr['html'])) {
                $result['html'] = $resultArr['html'];
            }

            return $result;
        } elseif (isset($resultArr['message'])) {
            throw new \Exception($resultArr['message']);
        }
        throw new \Exception('Failed to get recognized response '.print_r($resultArr));
    }

    public function notify(array $response): string
    {
        if (isset($response['transdata']) && isset($response['sign'])) {
            $transData = urldecode($response['transdata']);
            if ($this->credential->checkMD5($transData, $response['sign'])) {
                return $transData['order_no'];
            }
            throw new \Exception("Failed to check MD5 sign {$response['sign']}");
        }
        throw new \Exception('Empty keys transdata and sign in response');
    }

    public function prepareBody()
    {
        $paymentArr = [
            'order_no' => (string) $this->order->getNo(),
            'order_amount' => (string) $this->order->getAmount(),
            'order_time' => (string) $this->order->getTime(),
            'product_name' => (string) $this->order->getProductName(),
            'product_code' => (string) $this->order->getProductCode(),
            'user_no' => (string) $this->order->getUserNo(),
            'notify_url' => (string) $this->notifyUrl ?: 'https://dev.33tech.cc/v1/paid/wangtung',
            'pay_type' => (string) $this->payType,
            'bank_code' => (string) $this->bankCode ?: '',
            'return_url' => (string) $this->returnUrl ?: 'https://dev.33tech.cc/member',
            'merchant_ip' => (string) $this->merchantIp ?: '',
            'bank_card' => (string) $this->bankCard ?: '', ];

        $credentialArr = $this->credential->getCredentials();
        $paymentArr = array_merge($credentialArr, $paymentArr);
        $transdata = json_encode($paymentArr, JSON_UNESCAPED_SLASHES);
        $transdata = utf8_encode($transdata);

        return ['transdata' => urlencode($transdata),
                'sign' => urlencode($this->credential->signMD5($paymentArr)),
                'signtype' => WangtungCredential::MD5, ];
    }
}
