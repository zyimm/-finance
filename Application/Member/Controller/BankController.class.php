<?php
namespace Member\Controller;

class BankController extends BaseController
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
}