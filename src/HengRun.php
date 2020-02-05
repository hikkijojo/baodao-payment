<?php

namespace Baodao\Payment;

use Baodao\Payment\Exceptions\DepositPaymentFailedException;

class HengRun
{
    const NAME = 'hengrun';

    const URL = 'http://api.kuaile8899.com:8088/pay/apply.shtml';

    const MERCHANT = 'HR181031122711824';

    const MD5_KEY = 'F70FAB45528A72774D597999A5FF534D';

    const NOTIFY_URL = 'http://localhost/v1/payment/notify/'.self::NAME;

    public function create(array $inputs)
    {
        $form = [
            'appID' => self::MERCHANT,
            'tradeCode' => $this->getPayCode($inputs['payment']),
            'randomNo' => random_int(10000, 99999),
            'outTradeNo' => now()->format('YmdHis').floor((float) microtime() * 1000),
            'totalAmount' => $inputs['amount'] * 100,
            'productTitle' => 'transaction',
            'notifyUrl' => self::NOTIFY_URL,
            'tradeIP' => request()->ip(),
        ];
        ksort($form);

        $form['sign'] = $this->encryptSign($form, self::MD5_KEY);

        $response = $this->curlPost([
            'ApplyParams' => json_encode($form, true),
        ]);

        if ('0000' === $response['stateCode']) {
            return [
                'url' => $response['payURL'],
            ];
        }

        throw new DepositPaymentFailedException();
    }

    public function notify(array $inputs)
    {
        $data = json_decode($inputs['NoticeParams'], true);

        return ($this->signCheck($data)) ? ['order_no' => $data['outTradeNo']] : [];
    }

    private function signCheck($data)
    {
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);

        return $sign == $this->encryptSign($data, self::MD5_KEY);
    }

    private function encryptSign(array $params, string $key): string
    {
        return strtoupper(
            md5(
                implode('|', array_values($params)).
                '|'.
                $key
            )
        );
    }

    private function curlPost($fields): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::URL,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $fields,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function getPayCode(string $payment): string
    {
        $code = '';
        switch ($payment) {
            case 'wechat':
                $code = '80001';
                break;
            case 'alipay':
                $code = '80002';
                break;
            case 'qqpay':
                $code = '80003';
                break;
            case 'jdpay':
                $code = '80004';
                break;
            case 'unionpay':
                $code = '80005';
                break;
        }

        return $code;
    }
}
