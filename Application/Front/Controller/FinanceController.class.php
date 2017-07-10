<?php
/**
 * 财务处理
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月13日 下午9:37:19  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Controller;

class FinanceController extends BaseController
{

    public function index()
    {
        
        
        $data['html'] = "无相关记录";
        $this->ajaxReturn($data);
    }
}