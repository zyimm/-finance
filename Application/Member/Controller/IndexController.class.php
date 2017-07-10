<?php
namespace Member\Controller;

class IndexController extends BaseController
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