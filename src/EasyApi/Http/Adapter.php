<?php

namespace EasyApi\Http;

class Adapter
{
    private $cacertLocation = true;

    /**
     * whether use ssl for request
     */
    private $sslVerification = true;

    /**
     * The request public headers
     * @var array
     */
    private $headers = [];

    /**
     * The connection timeout time, which is 10 seconds by default
     * @var int
     */
    private $connectTimeout = 10;

    /**
     * The request timeout time, which is 120 seconds
     * @var int
     */
    private $socketTimeout = 120;

    /**
     * The curl options
     */
    private $curlOpts = [];

    /**
     * HttpClient
     * @param array $headers HTTP header
     * @param int $connectTimeout
     * @param int $socketTimeout
     */
    public function __construct($headers = [], $connectTimeout = 10000, $socketTimeout = 120000)
    {
        $this->headers        = $headers;
        $this->connectTimeout = $connectTimeout;
        $this->socketTimeout  = $socketTimeout;
    }

    /**
     * connect timeout
     * @param int $ms
     */
    public function setConnectionTimeoutInMillis($ms)
    {
        $this->connectTimeout = $ms;
    }

    /**
     * response timeout
     * @param int $ms
     */
    public function setSocketTimeoutInMillis($ms)
    {
        $this->socketTimeout = $ms;
    }

    /**
     * 配置
     * @param array $conf
     */
    public function setCurlOpts($conf)
    {
        $this->curlOpts = $conf;
    }

    public function setCacertLocation($location)
    {
        $this->cacertLocation = $location;
    }

    /**
     * @param bool $ssl the current adapter use ssl if or not
     */
    public function setSslVerification($ssl)
    {
        $this->sslVerification = $ssl;
    }

    public function addHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function prepare($ch)
    {
        foreach ($this->curlOpts as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
    }

    public function get($url, $params = [], $headers = [])
    {
        return $this->request('GET', $url, [], $params, $headers);
    }

    public function post($url, $data = [], $headers = [])
    {
        return $this->request('POST', $url, $data, [], $headers);
    }

    public function request($method, $url, $data, $params = [], $headers = [])
    {
        $request = new Request($method, $url, $data, $params, $headers);
        $request->prepare();
        $response = $this->send($request);

        return $response;
    }

    public function send($request)
    {
        $ch = curl_init();
        $this->prepare($ch);

        $url    = $request->url;
        $method = $request->method;

        $headers = array_merge($this->headers, $request->headers);
        $body    = $request->body;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->buildHeaders($headers));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->socketTimeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeout);

        // Verification of the SSL cert
        if ($this->sslVerification && $this->isSslVerification($url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // chmod the file as 0755
        if ($this->cacertLocation === true) {
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
        } elseif (is_string($this->cacertLocation)) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->cacertLocation);
        }

        $content = curl_exec($ch);
        $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code === 0) {
            throw new RequestException(curl_error($ch));
        }

        curl_close($ch);
        return $this->processResponse($content, $code);
    }

    /**
     * Process response before return
     * @param $content
     * @param $code
     * @return Response
     */
    public function processResponse($content, $code)
    {
        if (is_string($content)) {
            $body = $content;
        } else {
            $body = json_decode($content, true);
        }
        return new Response(null, $body, $code);
    }

    private function buildHeaders($headers)
    {
        $result = [];
        foreach ($headers as $k => $v) {
            $result[] = sprintf('%s: %s', $k, $v);
        }
        return $result;
    }

    private function isSslVerification($url)
    {
        return substr($url, 0, 8) == "https://";
    }
}
