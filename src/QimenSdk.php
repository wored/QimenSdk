<?php

namespace Wored\QimenSdk;


use Hanson\Foundation\Foundation;

/***
 * Class QimenSdk
 * @package \Wored\QimenSdk
 *
 * @property \Wored\QimenSdk\Api $api
 */
class QimenSdk extends Foundation
{

    protected $providers = [
        ServiceProvider::class
    ];

    public function __construct($config)
    {
        $config['debug'] = $config['debug'] ?? false;
        parent::__construct($config);
    }

    public function request(string $method, array $bodys = [], string $format = 'xml')
    {
        return $this->api->request($method, $bodys, $format);
    }

    public function verifySign(array $params, string $body)
    {
        return $this->verifySign($params, $body);
    }
}