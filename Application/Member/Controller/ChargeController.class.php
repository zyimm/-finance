<?php
namespace Member\Controller;

class ChargeController extends BaseController
{
    public function index()
    {
        $this->display();
    }
    
    public function onLine()
    {
        $this->outHtml($this->fetch());
    }
    
    public function offLine()
    {
        $this->outHtml($this->fetch());
    }
    
    public function  log()
    {
        $this->outHtml($this->fetch());
    }
}