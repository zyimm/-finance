<?php
/**
 *
* @Author ZYIMM <799783009@qq.com>
* @version 1.0
* Create By 2017年4月17日 下午8:15:48  版权所有@copyright 2013~2017 www.zyimm.com
*/
namespace Member\Controller;

class BorrowController extends BaseController
{
    public function index()
    {
        $this->display();
    }
    
    
    public function doIng()
    {
        $this->outHtml($this->fetch());
    }
    
    public function repayIn()
    {
        $this->outHtml($this->fetch());
    }
    
    public function overdue()
    {
        $this->outHtml($this->fetch());
    }
    
    public  function fail()
    {
        $this->outHtml($this->fetch());
    }
    public function payOff()
    {
        $this->outHtml($this->fetch());
    }
    
    public function statistics()
    {
        $this->outHtml($this->fetch());
    }
}