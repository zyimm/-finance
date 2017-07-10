<?php
/**
 *
* @Author ZYIMM <799783009@qq.com>
* @version 1.0
* Create By 2017年4月17日 下午8:15:48  版权所有@copyright 2013~2017 www.zyimm.com
*/
namespace Member\Controller;

use Member\Model\MoneyModel;

class QuotaController extends BaseController
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
    
    public function apply()
    {
        $result = (new MoneyModel())->apply(I('post.'),$this->uid);
        if($result === true){
            $this->success('申请成功!');
        }else{
            $this->error($result);
        }
    }
}