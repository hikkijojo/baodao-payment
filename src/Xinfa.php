<?php

namespace Baodao\Payment;

use Baodao\Payment\Enums\PaymentEnum;
use Exception;

class Xinfa
{
    const NAME = '鑫发支付';

    const LIST_THIRD_PARTY_PAYMENT_TYPE = [
        'ZFB',
        'ZFB_WAP', // 手机端跳转支付宝支付
        'UNION_WAP', // 手机端银联快捷在线支付
        'WX',
        'WX_WAP', // 手机端跳转微信支付
        'WX_H5',
        'QQ',
        'QQ_WAP', // 手机端跳转QQ钱包支付
        'JD',
        'JD_WAP', // 手机端跳转京东钱包支付
        'UNION_WALLET', // 云闪付扫码支付
    ];

    const VERSION = 'V3.3.0.0';
    const CHARSET = 'UTF-8';
    const ENCRYPT_CHUNK_SIZE = 117;
    const DECRYPT_CHUNK_SIZE = 128;
    const URL_PAY = 'http://netway.xfzfpay.com:90/api/pay';
    const GOODS_NAME = 'Baodao';
    const STATE_SUCCESS = '00';

    private $merchantNumber;
    private $md5Key;
    private $rsaPublicKey;
    private $rsaPrivateKey;
    private $orderNumber;
    private $thirdPartyPaymentType;
    private $amount;
    private $notifyUrl;
    private $redirectUrl;

    private $encodeOptions = JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES;

    /**
     * Send a new payment to Xinfa server and get the payment QR code.
     *
     * @return array|bool
     */
    public function create()
    {
        $this->checkProperties();

        $data = [];
        $this->prepareRsa();

        $data['orderNo'] = $this->orderNumber;
        $data['version'] = self::VERSION;
        $data['charsetCode'] = self::CHARSET;
        $data['randomNum'] = (string) rand(1000, 9999);
        $data['merchNo'] = $this->merchantNumber;
        $data['payType'] = $this->thirdPartyPaymentType; // WX: 微信支付, ZFB:支付宝支付
        $data['amount'] = bcmul($this->amount, 100); // 人民幣元 轉為 鑫發使用的人民幣分
        $data['goodsName'] = self::GOODS_NAME;
        $data['notifyUrl'] = $this->notifyUrl;
        $data['notifyViewUrl'] = $this->redirectUrl;

        $data['sign'] = $this->createSign($data, $this->md5Key);

        $json = json_encode($data, $this->encodeOptions);
        $dataStr = $this->encodePaymentData($json);
        $param = 'data='.urlencode($dataStr).'&merchNo='.$this->merchantNumber;

        $result = $this->post($param);
        $verified = $this->verifyCreation($result, $this->md5Key);

        if (empty($verified['qrcodeUrl'])) {
            return false;
        }

        return ['url' => $verified['qrcodeUrl'], 'html' => ''];
    }

    /**
     * Receive asynchronous notifications from Xinfa and thereafter return order number.
     *
     * @param array $request
     *
     * @return array
     */
    public function notify(array $request): array
    {
        if (isset($request['data'], $request['merchNo'], $request['orderNo'])) {
            $data = urldecode($request['data']);
            $this->prepareRsa();
            $data = $this->decryptNotification($data);

            $verified = $this->verifyNotification($data, $this->md5Key);
            /*
            |--------------------------------------------------------------------------
            | $verified['amount'] 的單位為人民幣分
            |--------------------------------------------------------------------------
            */

            if ($verified['merchNo'] != $request['merchNo'] || $verified['orderNo'] != $request['orderNo']) {
                return [
                    'code' => 400,
                    'order_no' => $verified['orderNo'],
                    'message' => 'Inconsistent merchNo or orderNo.',
                ];
            }

            return [
                'code' => 200,
                'order_no' => $verified['orderNo'],
                'message' => 'SUCCESS',
            ];
        }

        return [
            'code' => 500,
            'order_no' => $verified['orderNo'],
            'message' => 'Empty key data in request.',
        ];
    }

    /**
     * Get config for DB seeding.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'cn_name' => self::NAME,
            'en_name' => strtolower(get_class()),
            'is_alipay' => 1,
            'is_wechat' => 1,
            'is_gateway' => 0,
            'is_qqpay' => 1,
            'is_unionpay' => 1,
            'is_jdpay' => 1,
            'is_ylpay' => 1,
            'fields' => json_encode([
                'merchant' => PaymentEnum::MERCHANT,
                'md5_key' => PaymentEnum::MD5,
                'rsa_pub' => PaymentEnum::RSA_PUB,
                'rsa_pri' => PaymentEnum::RSA_PRI,
                'trade_code' => [
                    'alipay' => [PaymentEnum::TRADE_SCAN, PaymentEnum::TRADE_WAP],
                    'wechat' => [PaymentEnum::TRADE_SCAN, PaymentEnum::TRADE_WAP, PaymentEnum::TRADE_H5],
                    'qqpay' => [PaymentEnum::TRADE_SCAN, PaymentEnum::TRADE_WAP],
                    'jdpay' => [PaymentEnum::TRADE_SCAN, PaymentEnum::TRADE_WAP],
                    'unionpay' => [PaymentEnum::TRADE_SCAN],
                    'ylpay' => [PaymentEnum::TRADE_WAP],
                ],
            ]),
            'created_at' => new \Datetime(),
            'updated_at' => new \Datetime(),
        ];
    }

    /**
     * Set the value of merchantNumber.
     *
     * @return self
     */
    public function setMerchantNumber($merchantNumber)
    {
        $this->merchantNumber = $merchantNumber;

        return $this;
    }

    /**
     * Set the value of md5Key.
     *
     * @return self
     */
    public function setMd5Key($md5Key)
    {
        $this->md5Key = $md5Key;

        return $this;
    }

    /**
     * Set the value of rsaPublicKey.
     *
     * @return self
     */
    public function setRsaPublicKey($rsaPublicKey)
    {
        $this->rsaPublicKey = $rsaPublicKey;

        return $this;
    }

    /**
     * Set the value of rsaPrivateKey.
     *
     * @return self
     */
    public function setRsaPrivateKey($rsaPrivateKey)
    {
        $this->rsaPrivateKey = $rsaPrivateKey;

        return $this;
    }

    /**
     * Set the value of orderNumber.
     *
     * @return self
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * Set the value of thirdPartyPaymentType.
     *
     * @return self
     */
    public function setThirdPartyPaymentType($thirdPartyPaymentType)
    {
        if (false === array_search($thirdPartyPaymentType, self::LIST_THIRD_PARTY_PAYMENT_TYPE)) {
            throw new Exception('Not allowed third party payment type.');
        }

        $this->thirdPartyPaymentType = $thirdPartyPaymentType;

        return $this;
    }

    /**
     * Set the value of amount.
     *
     * @return self
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Set the value of notifyUrl.
     *
     * @return self
     */
    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;

        return $this;
    }

    /**
     * Set the value of redirectUrl.
     *
     * @return self
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     * Re-format RSA keys for API calling.
     *
     * @return bool
     */
    private function prepareRsa(): bool
    {
        // RSA private key preparing
        $rsaPrivateKeyHeader = '-----BEGIN RSA PRIVATE KEY-----';
        $rsaPrivateKeyFooter = '-----END RSA PRIVATE KEY-----';

        // remove header, footer and line feeds if they exist
        $this->rsaPrivateKey = str_replace($rsaPrivateKeyHeader, '', $this->rsaPrivateKey);
        $this->rsaPrivateKey = str_replace($rsaPrivateKeyFooter, '', $this->rsaPrivateKey);
        $this->rsaPrivateKey = trim($this->rsaPrivateKey, "\t\r\n");

        $rsaPrivateKey = $rsaPrivateKeyHeader."\r\n";
        foreach (str_split($this->rsaPrivateKey, 64) as $str) {
            $rsaPrivateKey = $rsaPrivateKey.$str."\r\n";
        }
        $rsaPrivateKey = $rsaPrivateKey.$rsaPrivateKeyFooter;

        // replace with re-formatted key
        $this->rsaPrivateKey = $rsaPrivateKey;

        // RSA public key preparing
        $rsaPublicKeyHeader = '-----BEGIN PUBLIC KEY-----';
        $rsaPublicKeyFooter = '-----END PUBLIC KEY-----';

        // remove header, footer and line feeds if they exist
        $this->rsaPublicKey = str_replace($rsaPublicKeyHeader, '', $this->rsaPublicKey);
        $this->rsaPublicKey = str_replace($rsaPublicKeyFooter, '', $this->rsaPublicKey);
        $this->rsaPublicKey = trim($this->rsaPublicKey, "\t\r\n");

        $rsaPublicKey = $rsaPublicKeyHeader."\r\n";
        foreach (str_split($this->rsaPublicKey, 64) as $str) {
            $rsaPublicKey = $rsaPublicKey.$str."\r\n";
        }
        $rsaPublicKey = $rsaPublicKey.$rsaPublicKeyFooter;

        // replace with re-formatted key
        $this->rsaPublicKey = $rsaPublicKey;

        return true;
    }

    /**
     * Encrypt payment data, and thereafter base-64-encode them.
     *
     * @param string $data
     *
     * @return string
     */
    private function encodePaymentData(string $data): string
    {
        $publicyKey = openssl_pkey_get_public($this->rsaPublicKey);
        if (false == $publicyKey) {
            throw new Exception('Something went wrong with public key.');
        }

        $encryptData = '';
        $crypto = '';

        foreach (str_split($data, self::ENCRYPT_CHUNK_SIZE) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $publicyKey);
            $crypto = $crypto.$encryptData;
        }

        $crypto = base64_encode($crypto);

        return $crypto;
    }

    /**
     * Make a POST HTTP request.
     *
     * @param string $data
     *
     * @return string
     */
    private function post(string $data): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::URL_PAY);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            return $error;
        }

        return $result;
    }

    /**
     * Create sign with MD5 key.
     *
     * @param array  $data
     * @param string $key
     *
     * @return string
     */
    private function createSign(array $data, string $key): string
    {
        ksort($data);
        $sign = strtoupper(md5(json_encode($data, $this->encodeOptions).$key));

        return $sign;
    }

    /**
     * Verify responded data of payment creation and return data without sign.
     *
     * @param string $json
     * @param string $md5Key
     *
     * @return array
     */
    private function verifyCreation(string $json, string $md5Key): array
    {
        $array = json_decode($json, true);
        if (self::STATE_SUCCESS == $array['stateCode']) {
            $signString = $array['sign'];
            ksort($array);
            $signArray = [];
            foreach ($array as $k => $v) {
                if ('sign' !== $k) {
                    $signArray[$k] = $v;
                }
            }

            $md5 = strtoupper(md5(json_encode($signArray, $this->encodeOptions).$md5Key));
            if ($md5 == $signString) {
                return $signArray;
            } else {
                throw new Exception('返回签名验证失败');
            }
        } else {
            throw new Exception(($array['stateCode'] ?? '').($array['msg'] ?? 'Unknown Xinfa error'));
        }
    }

    /**
     * Base-64-decode async notification data and RSA decrypt it.
     *
     * @param string $data
     *
     * @return string
     */
    private function decryptNotification(string $data): string
    {
        $privateKey = openssl_get_privatekey($this->rsaPrivateKey, '');
        if (false == $privateKey) {
            throw new Exception('打开密钥出错');
        }

        $data = base64_decode($data);
        $plain = '';
        foreach (str_split($data, self::DECRYPT_CHUNK_SIZE) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $privateKey);
            $plain .= $decryptData;
        }

        return $plain;
    }

    /**
     * Verify notified data and return data without sign.
     *
     * @param string $json
     * @param string $key
     *
     * @return void
     */
    private function verifyNotification(string $json, string $key)
    {
        $array = json_decode($json, true);
        $signString = $array['sign'];
        ksort($array);
        $signArray = [];
        foreach ($array as $k => $v) {
            if ('sign' !== $k) {
                $signArray[$k] = $v;
            }
        }

        $md5 = strtoupper(md5(json_encode($signArray, $this->encodeOptions).$key));
        if ($md5 == $signString) {
            return $signArray;
        } else {
            throw new Exception('返回签名验证失败');
        }
    }

    /**
     * Check if properties exist.
     *
     * @return void
     */
    private function checkProperties()
    {
        if (!isset($this->orderNumber)) {
            throw new Exception('orderNumber are not ready.');
        }
        if (!isset($this->merchantNumber)) {
            throw new Exception('merchantNumber are not ready.');
        }
        if (!isset($this->thirdPartyPaymentType)) {
            throw new Exception('thirdPartyPaymentType are not ready.');
        }
        if (!isset($this->amount)) {
            throw new Exception('amount are not ready.');
        }
        if (!isset($this->notifyUrl)) {
            throw new Exception('notifyUrl are not ready.');
        }
        if (!isset($this->redirectUrl)) {
            throw new Exception('redirectUrl are not ready.');
        }
        if (!isset($this->rsaPrivateKey)) {
            throw new Exception('rsaPrivateKey are not ready.');
        }
        if (!isset($this->rsaPublicKey)) {
            throw new Exception('rsaPublicKey are not ready.');
        }
    }
}
