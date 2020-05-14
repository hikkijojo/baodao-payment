<?php

namespace Baodao\Payment\FourthParty;

use Baodao\Payment\Contracts\PaymentInterface;
use Baodao\Payment\PaymentConfig;
use Baodao\Payment\PaymentCreation;
use Baodao\Payment\PaymentNotify;
use Baodao\Payment\PaymentSetting;
use Exception;

class Xinfa implements PaymentInterface
{
    const CN_NAME = '鑫发支付';
    const EN_NAME = 'xinfa';

    const PAY_TYPES = [
        self::ZFB, // 支付寶掃碼
        self::ZFB_WAP, // 手机端跳转支付宝支付
        self::UNION_WAP, // 手机端银联快捷在线支付
        self::WX, // 微信掃碼
        self::WX_WAP, // 手机端跳转微信支付
        self::WX_H5, // 微信跳轉
        self::QQ, // QQ 掃碼
        self::QQ_WAP, // 手机端跳转QQ钱包支付
        self::JD, // 京東掃碼
        self::JD_WAP, // 手机端跳转京东钱包支付
        self::UNION_WALLET, // 云闪付扫码支付
    ];

    const ZFB = 'ZFB';
    const ZFB_WAP = 'ZFB_WAP';
    const UNION_WAP = 'UNION_WAP';
    const WX = 'WX';
    const WX_WAP = 'WX_WAP';
    const WX_H5 = 'WX_H5';
    const QQ = 'QQ';
    const QQ_WAP = 'QQ_WAP';
    const JD = 'JD';
    const JD_WAP = 'JD_WAP';
    const UNION_WALLET = 'UNION_WALLET';

    const VERSION = 'V3.3.0.0';
    const CHARSET = 'UTF-8';
    const ENCRYPT_CHUNK_SIZE = 117;
    const DECRYPT_CHUNK_SIZE = 128;
    const URL_PAY = 'http://netway.xfzfpay.com:90/api/pay';
    const GOODS_NAME = 'Baodao';
    const STATE_SUCCESS = '00';

    private $merchNo;
    private $md5Key;
    private $rsaPublicKey;
    private $rsaPrivateKey;
    private $orderNo;
    private $thirdPartyPaymentType;
    private $amount;
    private $notifyUrl;
    private $redirectUrl;

    private $encodeOptions = JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES;

    /**
     * Send a new payment to Xinfa server and get the payment QR code.
     *
     * @return PaymentCreation
     */
    public function create(PaymentSetting $paymentSetting): PaymentCreation
    {
        $this->setConnection($paymentSetting);
        $this->checkProperties();

        $data = [];
        $this->prepareRsa();

        $data['orderNo'] = $this->orderNo;
        $data['version'] = self::VERSION;
        $data['charsetCode'] = self::CHARSET;
        $data['randomNum'] = (string) rand(1000, 9999);
        $data['merchNo'] = $this->merchNo;
        $data['payType'] = $this->thirdPartyPaymentType; // WX: 微信支付, ZFB:支付宝支付
        $data['amount'] = bcmul($this->amount, 100); // 人民幣元 轉為 鑫發使用的人民幣分
        $data['goodsName'] = self::GOODS_NAME;
        $data['notifyUrl'] = $this->notifyUrl;
        $data['notifyViewUrl'] = $this->redirectUrl;

        $data['sign'] = $this->createSign($data, $this->md5Key);

        $json = json_encode($data, $this->encodeOptions);
        $dataStr = $this->encodePaymentData($json);
        $param = 'data='.urlencode($dataStr).'&merchNo='.$this->merchNo;

        $result = $this->post($param);
        $verified = $this->verifyCreation($result, $this->md5Key);

        if (!empty($verified['qrcodeUrl'])) {
            $result = new PaymentCreation();
            $result->code = 200;
            $result->url = $verified['qrcodeUrl'];

            return $result;
        } elseif (isset($verified['msg'])) {
            throw new Exception($verified['msg']);
        }

        throw new Exception('Failed to get recognized response '.print_r($verified, true));
    }

    /**
     * Receive asynchronous notifications from Xinfa and thereafter return order number.
     *
     * @param array          $request
     * @param PaymentSetting $p
     *
     * @return PaymentNotify
     */
    public function notify(PaymentSetting $p, array $request): PaymentNotify
    {
        $this->prepareBeingNotified($p);

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
                $paymentNotify = new PaymentNotify();

                $paymentNotify->code = 400;
                $paymentNotify->message = 'Inconsistent merchNo or orderNo.';
                $paymentNotify->orderNo = $request['orderNo'];

                return $paymentNotify;
            }

            $paymentNotify->code = 200;
            $paymentNotify->message = 'SUCCESS';
            $paymentNotify->orderNo = $verified['orderNo'];

            return $paymentNotify;
        }

        $paymentNotify->code = 500;
        $paymentNotify->message = 'Empty key data in request.';
        $paymentNotify->orderNo = $request['orderNo'];

        return $paymentNotify;
    }

    /**
     * Get config for DB seeding.
     *
     * @return PaymentConfig
     */
    public function getConfig(): PaymentConfig
    {
        $c = new PaymentConfig();

        return $c->setCnName(self::CN_NAME)
                 ->setEnName(self::EN_NAME)
                 ->setThirdParty([
                     PaymentConfig::THIRD_PARTY_ALIPAY,
                     PaymentConfig::THIRD_PARTY_WECHAT,
                     PaymentConfig::THIRD_PARTY_QQ,
                     PaymentConfig::THIRD_PARTY_UNIONPAY,
                     PaymentConfig::THIRD_PARTY_YLPAY,
                     PaymentConfig::THIRD_PARTY_JDPAY, ])
                 ->setFieldMerchant()
                 ->setFieldMd5Key()
                 ->setFieldRsa()
                 ->setFieldTradeCode(
                     PaymentConfig::THIRD_PARTY_ALIPAY,
                     [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_WAP]
                 )
                 ->setFieldTradeCode(
                     PaymentConfig::THIRD_PARTY_JDPAY,
                     [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_WAP]
                 )
                 ->setFieldTradeCode(
                     PaymentConfig::THIRD_PARTY_QQ,
                     [PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_WAP]
                 )
                 ->setFieldTradeCode(
                     PaymentConfig::THIRD_PARTY_UNIONPAY,
                     [PaymentConfig::TRADE_SCAN]
                 )
                 ->setFieldTradeCode(
                     PaymentConfig::THIRD_PARTY_WECHAT,
                     [PaymentConfig::TRADE_H5, PaymentConfig::TRADE_SCAN, PaymentConfig::TRADE_WAP]
                 )
                 ->setFieldTradeCode(
                     PaymentConfig::THIRD_PARTY_YLPAY,
                     [PaymentConfig::TRADE_WAP]
                 );
    }

    /**
     * There is no bank supported by Xinfa.
     *
     * @return array
     */
    public function getBanks(): array
    {
        return [];
    }

    /**
     * Set properties for create() function.
     *
     * @param PaymentSetting $p
     *
     * @return void
     */
    private function setConnection(PaymentSetting $p)
    {
        $this->rsaPrivateKey = $p->rsaPrivateKey;
        $this->rsaPublicKey = $p->rsaPublicKey;

        $payType = $this->getPayType($p->thirdPartyType, $p->tradeType);
        if (false == in_array($payType, self::PAY_TYPES)) {
            throw new \Exception("Unknown pay_type $payType");
        }

        $this->orderNo = $p->orderNo;
        $this->merchNo = $p->merchantNo;
        $this->thirdPartyPaymentType = $payType;
        $this->amount = $p->orderAmount;

        $this->host = empty($p->host) ? 'https://www.wantong-pay.com' : $p->host;
        $this->notifyUrl = $p->notifyUrl;
        $this->redirectUrl = $p->redirectUrl;
        $this->md5Key = $p->md5Key;
        $this->readyToConnect = true;
    }

    /**
     * Prepare properties for being notified by Xinfa.
     *
     * @param PaymentSetting $p
     *
     * @return void
     */
    private function prepareBeingNotified(PaymentSetting $p)
    {
        $this->rsaPrivateKey = $p->rsaPrivateKey;
        $this->rsaPublicKey = $p->rsaPublicKey;
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
        if (!isset($this->rsaPrivateKey)) {
            throw new Exception('rsaPrivateKey are not ready.');
        }
        if (!isset($this->rsaPublicKey)) {
            throw new Exception('rsaPublicKey are not ready.');
        }
        if (!isset($this->orderNo)) {
            throw new Exception('orderNo are not ready.');
        }
        if (!isset($this->merchNo)) {
            throw new Exception('merchNo are not ready.');
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
    }

    private function getPayType($thirdPartyType, $tradeType)
    {
        if (PaymentConfig::THIRD_PARTY_ALIPAY == $thirdPartyType) {
            if (PaymentConfig::TRADE_WAP == $tradeType) {
                return self::ZFB_WAP;
            }

            return self::ZFB;
        }

        if (PaymentConfig::THIRD_PARTY_YLPAY == $thirdPartyType) {
            return self::UNION_WAP;
        }

        if (PaymentConfig::THIRD_PARTY_WECHAT == $thirdPartyType) {
            if (PaymentConfig::TRADE_H5 == $tradeType) {
                return self::WX_H5;
            } elseif (PaymentConfig::TRADE_WAP == $tradeType) {
                return self::WX_WAP;
            }

            return self::WX;
        }

        if (PaymentConfig::THIRD_PARTY_QQ == $thirdPartyType) {
            if (PaymentConfig::TRADE_WAP == $tradeType) {
                return self::QQ_WAP;
            }

            return self::QQ;
        }

        if (PaymentConfig::THIRD_PARTY_JDPAY == $thirdPartyType) {
            if (PaymentConfig::TRADE_WAP == $tradeType) {
                return self::JD_WAP;
            }

            return self::JD;
        }

        if (PaymentConfig::THIRD_PARTY_UNIONPAY == $thirdPartyType) {
            return self::UNION_WALLET;
        }
    }
}
