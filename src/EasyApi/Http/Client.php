<?php

namespace EasyApi\Http;

class Client
{
    private $host;

    // the request key
    private $apiKey;

    // the request secret
    private $apiSecret;

    // establish connection timeout time
    private $connectTimeout;

    // the request timeout time, send request but not receive response in some time
    private $socketTimeout;

    private $sslVerification = true;

    private $curlOpts = [];

    private $headers = [];

    private $adapter;

    public static $instance;

    public static function instance()
    {
        if (!static::$instance instanceof static) {
            static::$instance = new static(...func_get_args());
        }
        return static::$instance;
    }

    public function __construct($connectTimeout = 10000, $socketTimeout = 120000)
    {
        $apiKey    = trim(Config::getConfig()['apiKey']);
        $apiSecret = trim(Config::getConfig()['apiSecret']);
        $host      = trim(trim(Config::$host), "/");

        if (empty($apiKey)) {
            throw new RequestException("apiKey is empty");
        }
        if (empty($apiSecret)) {
            throw new RequestException("apiSecret is empty");
        }
        if (empty($host)) {
            throw new RequestException("host is empty");
        }

        $this->host           = $host;
        $this->apiKey         = $apiKey;
        $this->apiSecret      = $apiSecret;
        $this->connectTimeout = $connectTimeout;
        $this->socketTimeout  = $socketTimeout;
    }

    public function getAdapter()
    {
        $adapter = $this->adapter;
        if (!isset($adapter)) {
            $adapter = new Adapter($this->headers, $this->connectTimeout, $this->socketTimeout);
            $adapter->setCacertLocation(false);
            $adapter->setCurlOpts($this->curlOpts);
            $this->adapter = $adapter;
        }

        return $adapter;
    }

    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }

    public function getPublicForms()
    {
        return [
            'appid'  => $this->apiKey,
            'secret' => $this->apiSecret,
        ];
    }

    public function request($request, $options)
    {
        $forms = new MultiPartForm();

        $publics = $this->getPublicForms();

        $forms->addForms($publics);

        //业务请求数据
        $bizContent = json_encode($request->getBizContent(), JSON_UNESCAPED_UNICODE);
        //签名
        $sign = $this->generateBizContentSign($bizContent);

        foreach ($options as $key => $value) {
            if (is_file($value)) {
                $forms->addFile($key, $value, file_get_contents($value));
            } else {
                $forms->addForm($key, $value);
            }
        }

        $adapter = $this->getAdapter();

        $apiMethodName = $request->getService();

        $url   = $this->generateUrl($apiMethodName);

        $this->setHeaders($request->getHeaders());

        $quest = $request->getType();

        return $adapter->$quest($url, $forms, $this->headers);
    }

    public function generateUrl($path)
    {
        return $this->host . '/' . trim($path, '/');
    }

    //签名
    public function generateBizContentSign($params)
    {
        return $params;
    }

    public function setSocketTimeout($ms)
    {
        $this->socketTimeout = $ms;
    }

    public function setConnectTimeout($ms)
    {
        $this->connectTimeout = $ms;
    }

    public function setHeaders($headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function setSslVerification($ssl)
    {
        $this->sslVerification = $ssl;
    }

    public function setCurlOpts($conf)
    {
        $this->curlOpts = $conf;
    }
}

