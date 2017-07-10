<?php
namespace Member\Controller;

class WithDrawController extends BaseController
{
    public function index()
    {
        $this->display();
    }
    
    public function applyLog()
    {
        $this->outHtml($this->fetch());
    }
}