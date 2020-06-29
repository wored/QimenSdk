<?php

namespace Wored\QimenSdk;

use Hanson\Foundation\AbstractAPI;
use Hanson\Foundation\Log;
use Illuminate\Support\Str;

class Api extends AbstractAPI
{
    public $config;
    public $timestamp;
    public $loginData;

    /**
     * Api constructor.
     * @param $appkey
     * @param $appsecret
     * @param $sid
     * @param $baseUrl
     */
    public function __construct(QimenSdk $qimenSdk)
    {
        $this->config = $qimenSdk->getConfig();
    }

    /**
     * api请求方法
     * @param $method域名后链接
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function request(string $method, array $bodys = [], string $format = 'xml')
    {
        $params = [
            'method'         => $method,
            'app_key'        => $this->config['app_key'],
            'target_app_key' => $this->config['target_app_key'] ?? '',
            'sign_method'    => 'md5',
            'session'        => $this->config['session'] ?? '',
            'timestamp'      => date('Y-m-d H:i:s'),
            'format'         => $format,
            'v'              => '2.0',
            'partner_id'     => $this->config['partner_id'] ?? '',
            'customerId'     => $this->config['customerId'] ?? '',
        ];
        $body = $this->arrayToXml($bodys, false);
        $params['sign'] = $this->makeSign($params, $body);
        $url = $this->config['rootUrl'] . '?' . http_build_query($params);
        $response = $this->https_request($url, $body);
        return $response;
    }

    /**
     * 生成签名
     * @param array $params请求的所有参数
     * @return string
     */
    private function makeSign(array $params, string $body)
    {
        unset($params['sign']);
        ksort($params);
        $sign = $this->config['app_secret'];
        foreach ($params as $k => $v) {
            if ($k != '' and $v != '' and $k != 'sign') {
                $sign .= $k . $v;
            }
        }
        $sign .= $body . $this->config['app_secret'];
        $sign = strtoupper(md5($sign));
        return $sign;
    }

    /**
     * 验证签名
     * @param array $params 请求参数
     * @param string $body 请求body
     * @param string $secret 秘钥
     * @return bool
     * @throws \Exception
     */
    public function verifySign(array $params, string $body)
    {
        $sign = $this->makeSign($params, $body);
        if (!isset($params['sign'])) {//没有签名参数
            throw new \Exception('签名字段不存在');
        }
        if ($sign == $params['sign']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * http 请求
     * @param $url 请求的链接url
     * @param null $data 请求的参数，参数为空get请求，参数不为空post请求
     * @return mixed
     */
    private function https_request($url, $data = null)
    {
        Log::debug('Client Request:', compact('url', 'data'));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        Log::debug('API response:', compact('output'));
        return $output;
    }

    /**
     * 数组数据转成xml格式
     * @param $data数组数据
     * @param bool $root 是否是根节点
     * @return string 返回xml数据
     */
    private function arrayToXml(array $data, $root = true)
    {
        if ($root) {
            $xml = '<?xml version="1.0" encoding="utf-8"?>';
        } else {
            $xml = '';
        }
        foreach ($data as $key => $vo) {
            if ($key === 'attributes') {//判断是否是属性字段
                continue;
            }
            if (!is_numeric($key)) {
                $xml .= "<{$key}";
                if (!empty($vo['attributes'])) {//添加属性
                    foreach ($vo['attributes'] as $item => $attribute) {
                        $xml .= " {$item}=\"{$attribute}\"";
                    }
                }
                $xml .= '>';
            }
            if (is_array($vo) and count($vo) > 0) {
                $xml .= $this->arrayToXml($vo, false);
            } else {
                $xml .= $vo;
            }
            if (!is_numeric($key)) {
                $xml .= "</{$key}>";
            }
        }
        return $xml;
    }
}