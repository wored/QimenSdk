<?php

namespace Wored\QimenSdk;


use Hanson\Foundation\Foundation;

/***
 * Class QimenSdk
 * @package \Wored\QimenSdk
 *
 * @property Api $api
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

    public function request(string $apiName, array $params, string $httpMethod = 'post')
    {
        return $this->api->request($apiName, $params, $httpMethod);
    }

    public function verifySign(array $request)
    {
        return $this->verifySign($request);
    }
}