<?php

require_once __DIR__ . '/autoload.php';

class TestClient
{
    private $client;

    public function client($request, $options)
    {
        $this->client = EasyApi\Http\Client::instance();
        $headers      = [
            'Cache-Control' => 'no-cache',
        ];
        $this->client->setHeaders($headers);
        return $this->client->request($request, $options);
    }

    public function testWechat()
    {
        //或者从写到env文件中
        EasyApi\Http\Config::$config = [
            'apiKey'    => '',
            'apiSecret' => '',
        ];

        $forms = ['args' => ''];
        $r     = new EasyApi\Request\WechatTest();
        $v     = $this->client($r, $forms);
        return $v;
    }
}

$tester = new TestClient();
$resp   = $tester->testWechat();
var_dump($resp);