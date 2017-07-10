<?php
namespace Backstage\Controller;

use Backstage\Model\ExtendModel;
use Front\Model\ConfigModel;

class ExtendController extends BaseController
{
    public static $model = null;
    public function __construct()
    {
        parent::__construct();
        
        self::$model = new ExtendModel();
    }
    
    public function parameter()
    {
        if(IS_POST){
           $result = self::$model->parameter(I('post.'));
           if($result === true){
              $this->success('操作成功'); 
               
           }else{
               $this->error($result);
           }
        }else{
            $data = [];
            $borrow_config = ConfigModel::read('borrow_config');
            //借款用途
            $data['BORROW_USE'] = $borrow_config['BORROW_USE'];
            //最小金额
            $data['BORROW_MIN'] = $borrow_config['BORROW_MIN'];
            //最大金额
            $data['BORROW_MAX'] = $borrow_config['BORROW_MAX'];
            //募资时间
            $data['BORROW_TIME'] = $borrow_config['BORROW_TIME'];
            //查询金额
            $data['MONEY_SEARCH'] = $borrow_config['MONEY_SEARCH'];
            //提现银行
            $bank = ConfigModel::read('bank');
            $data['BANK_NAME'] = $bank['BANK_NAME'];
            $this->assign('data',$data); 
            $this->display();
        }
    }
    
    
}