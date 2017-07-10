<?php
namespace Member\Controller;

class PasswordController extends BaseController
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
    
    
    public function Pay()
    {
        $this->outHtml($this->fetch());
    }
    
}