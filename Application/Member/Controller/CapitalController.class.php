<?php
namespace Member\Controller;

class CapitalController extends BaseController
{
    public function index()
    {
        if(IS_AJAX){
            layout(false);
            $this->outHtml($this->fetch());
        
        }else{
            $this->display();
        }
    }
    
    public function log()
    {
        $this->outHtml($this->fetch());
    }
}