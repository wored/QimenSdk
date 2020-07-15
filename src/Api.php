<?php

namespace Wored\QimenSdk;

use Hanson\Foundation\AbstractAPI;

class Api extends AbstractAPI
{
    public $config;
    public $timestamp;

    /**
     * Api constructor.
     * @param QimenSdk $qimenSdk
     */
    public function __construct(QimenSdk $qimenSdk)
    {
        $this->config = $qimenSdk->getConfig();
    }

    /**
     * 生成签名
     * @param array $allParams 所有请求参数
     * @return string
     */
    private function makeSign(array $allParams): string
    {
        ksort($allParams);
        $str = $this->config['app_secret'];
        foreach ($allParams as $k => $v) {
            if ($k != '' and $v != '') {
                $str .= $k . $v;
            }
        }
        $str .= $this->config['app_secret'];

        return strtoupper(md5($str));
    }

    /**
     * 获取加入sign的公共请求参数
     * @param string $apiName
     * @param array $params 业务参数
     * @return array
     */
    private function getCommonParams(string $apiName, array $params): array
    {
        $commonParams = [
            'method' => $apiName,
            'app_key' => $this->config['app_key'],
            'target_app_key' => $this->config['target_app_key'] ?? '',
            'sign_method' => 'md5',
            'session' => $this->config['session'] ?? '',
            'timestamp' => date('Y-m-d H:i:s'),
            'format' => 'json',
            'v' => '2.0',
            'partner_id' => $this->config['partner_id'] ?? '',
        ];
        $commonParams['sign'] = $this->makeSign(array_merge($commonParams, $params));

        return $commonParams;
    }

    /**
     * 验证签名
     * @param array $params 请求参数
     * @return bool
     * @throws \Exception
     */
    public function verifySign(array $params): bool
    {
        if (!isset($params['sign'])) {//没有签名参数
            throw new \Exception('签名字段不存在');
        } else {
            $sign = $params['sign'];
        }
        unset($params['sign']);

        return $this->makeSign($params) === $sign ? true : false;
    }

    /**
     * @param string $apiName
     * @param array $params 业务参数
     * @param string $httpMethod 可以是post/get等等，具体根据接口文档进行选择
     * @return mixed
     */
    public function request(string $apiName, array $params, string $httpMethod = 'post')
    {
        $http = $this->getHttp();
        $http->addMiddleware($this->headerMiddleware([
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ]));
        $commonParams = $this->getCommonParams($apiName, $params);
        $url = $this->config['rootUrl'] . '?' . http_build_query($commonParams);

        $response = call_user_func([$http, $httpMethod], [$url, $params]);

        return json_decode(strval($response->getBody()), true);
    }
}