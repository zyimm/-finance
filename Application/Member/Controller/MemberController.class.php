<?php
namespace Member\Controller;

class MemberController extends BaseController
{
    public function index()
    {
        $this->display();
    }
    
    public function header()
    {
       /*  $member = new MemberModel();
        $member->getMinfo($this->uid,''); */
        $this->outHtml($this->fetch());
    }
    
    public function info()
    {
        $this->outHtml($this->fetch());
    }
    
    public function contact()
    {
        $this->outHtml($this->fetch());
    }
    
    public function career()
    {
        $this->outHtml($this->fetch());
    }
    
}