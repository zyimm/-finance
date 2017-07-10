<?php
/**
 * 工具
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月13日 下午9:11:28  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Controller;

use Think\Controller;
use Front\Model\ToolModel;

class ToolController extends Controller
{

    public function getArea()
    {
        
    }
    
    
    
    public function borrowCalc()
    {
        if(IS_AJAX){
            $data = [];
            $data = ToolModel::borrowCalc(I('post.'));
            $this->ajaxReturn($data);
        }else{
            $this->display();
        }
        
    }
    
    public function investCalc()
    {
        if(IS_AJAX){
            $data = [];
            $data = ToolModel::investCalc(I('post.'));
            $this->ajaxReturn($data);
        }else{
            $this->display();
        }
        
    }
}