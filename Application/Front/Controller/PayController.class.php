<?php
namespace Front\Controller;

use Think\Controller;
use Think\Exception;
use Front\Model\PayModel;
use Think\Crypt;

class PayController extends Controller
{
   public static $payModel;
   public static $uid = 0;
   public function __construct()
   {
       if(session('user_id')){
           self::$uid = session('user_id');
           self::$uid = Crypt::decrypt(self::$uid,C('CRYPT_KEY'));
       }
       parent::__construct();
       self::$payModel = new PayModel($data ,self::$uid);
       
   }
   public function isEnable($type= '')
   {
       $config = array();
       $config = self::$payModel->getPayConfig($type);
       if(!empty($config)){      
           if (($type != 'offline') && $config['enable'] == 0 ) {
               $this->error("对不起，该支付方式被关闭，暂时不能使用!");
           }  
       }else{
           $this->error("支付方式异常!");
       }
   }
   
   public function __call($method = '', $args = array())
   {  
       $method = strtolower($method);
       if(method_exists(self::$payModel,strtolower($method))){
           $this->isEnable($method);
           try {
               call_user_func_array([self::$payModel,$method],I('post.'));
           } catch (Exception $e) {
               $this->error($e->getMessage());
           }
       }else{
           $this->error('支付方式不存在');
       }
   }
   
   public function payReturn()
   {
       self::$payModel->payReturn(I('request.'));
   }
   
   public function payNotice()
   {
       self::$payModel->payNotice(I('request.'));
   }
  
}