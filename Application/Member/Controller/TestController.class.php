<?php
namespace Member\Controller;

use Think\Controller;



class TestController extends Controller
{

    public  function index()
    {
        $model = M('members');
        M('members')->startTrans();
            M('members')->startTrans();
                M('members')->startTrans();
                    M('members')->startTrans();
                    M('members')->rollback();
                 M('members')->rollback();
             M('members')->commit();
        M('members')->commit();
        
    }
    
    
}