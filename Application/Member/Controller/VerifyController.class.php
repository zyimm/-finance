<?php

/**
 * 验证
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年4月20日 下午11:40:16  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Member\Controller;



class VerifyController extends BaseController
{
    public function index()
    {
        $this->display();
    }
    
    public function mobile()
    {
        $this->outHtml($this->fetch());
    }
    
    public function email()
    {
        $this->outHtml($this->fetch());
    }
    
    public function id()
    {
        $this->outHtml($this->fetch());
    }
    
    public function info()
    {
        $this->display();
    }
}