<?php

namespace EasyApi\Http;

class Request
{
    /**
     * The URL being requested.
     */
    public $url;

    /**
     * The URL query string
     */
    public $params;

    /**
     * The request raw body
     */
    public $body;

    /**
     * The headers being sent in the request.
     */
    public $headers;

    /**
     * The body being sent in the request.
     */
    public $data;

    /**
     * The method by which the request is being made.
     */
    public $method;

    /**
     * The Content Type
     */
    public $ctype;

    /**
     * Default useragent string to use.
     */
    public $useragent = 'api/request';

    public function __construct($method, $url, $data, $params = [], $headers = [])
    {
        $this->url     = $url;
        $this->params  = $params;
        $this->method  = strtoupper($method);
        $this->headers = $headers;
        $this->data    = $data;
        $this->ctype   = $headers['Content-Type']??null;
    }

    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function removeHeader($key)
    {
        if (isset($this->headers[$key])) {
            unset($this->headers[$key]);
        }
        return $this;
    }

    public function setMethod($method)
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function setUserAgent($ua)
    {
        $this->useragent = $ua;
        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function setContentType($ctype)
    {
        $this->ctype = $ctype;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    private function buildUrl()
    {
        if ($this->params){
            $params = [];
            foreach ($this->params as $param) {
                if ($param[0]) {
                    $params[$param[0]] = $param[1];
                }
            }
            if (!empty($params)) {
                $str       = http_build_query($params);
                $url       = $this->url;
                $this->url = $url . (strpos($url, '?') === false ? '?' : '&') . $str;
            }
        }
    }

    private function buildBody()
    {
        if ($this->data){
            $data = [];
            foreach ($this->data->forms as $v) {
                if ($v[0]) {
                    $data[$v[0]] = $v[1];
                }
            }
            if (is_array($data)) {
                switch ($this->ctype) {
                    case ContentTypes::JSON:
                        $body = json_encode($data);
                        break;
                    case ContentTypes::FORM:
                        $body = http_build_query($data);
                        break;
                    default:
                        $body = (string)$this->data;
                        break;
                }
            } else {
                $body = (string)$this->data;
            }
            $this->body = $body;
        }
    }

    private function buildHeader()
    {
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $key           = ucwords($key);
            $headers[$key] = $value;
        }
        $headers['User-Agent'] = $this->useragent;
        if (!empty($this->ctype)) {
            $headers['Content-Type'] = $this->ctype;
        }
        $this->headers = $headers;
    }

    public function prepare()
    {
        $this->buildUrl();
        $this->buildHeader();
        $this->buildBody();
    }
}

