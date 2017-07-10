<?php
/**
 * 
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年4月20日 下午11:47:20  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Member\Controller;

class InvestController extends BaseController
{
    public function index()
    {
        $this->display();
    }
    
    public function doing()
    {
        $this->outHtml($this->fetch());
    }
    
    public function recoveryIn()
    {
        $this->outHtml($this->fetch());
    }
    public function overdue()
    {
        $this->outHtml($this->fetch());
    }
    
    public function recovery()
    {
        $this->outHtml($this->fetch());
    }
}