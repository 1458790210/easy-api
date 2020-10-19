<?php

namespace EasyApi\Request;

class WechatTest
{
    private $bizContent;
    private $apiParas = array();

    public function getService()
    {
        return '/cgi-bin/token?grant_type=client_credential';
    }

    public function setBizContent($bizContent)
    {
        $this->bizContent = $bizContent;
        $this->apiParas['biz_content'] = $bizContent;
    }

    public function getBizContent()
    {
        return $this->bizContent;
    }

    public function getApiParas()
    {
        return $this->apiParas;
    }

}