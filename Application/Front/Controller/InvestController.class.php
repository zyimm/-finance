<?php
/**
 * 投标处理
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月13日 下午9:37:19  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Controller;

use Think\Crypt;
use Front\Model\InvestModel;

class InvestController extends BaseController
{
    public function index()
    {
        
        
    }

    public function view()
    {
        $id = explode('/',$_SERVER['PATH_INFO']);
        $id = array_pop($id);
        $id = Crypt::decrypt($id,C('CRYPT_KEY'));
   
        
    }

    public function investMoney()
    {
        if (!$this->uid) { 
            $this->error('请先登录');
        }
        $invest_model = new InvestModel();
        $_POST['pay_pass'] =  '';
        $result = $invest_model->investMoney(1304,'choubaguai',2881,100);
        if($result === true){
            echo ('投标成功');
        }else{
            $result = is_bool($result)?'error':$result;
            dump($result);
        }
    }
}