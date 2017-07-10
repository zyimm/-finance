<?php
namespace Backstage\Controller;

use Think\Controller;
use Think\Verify;
use Think\Crypt;
use Backstage\Model\LogModel;

class SignController extends Controller
{
    public static $key = NULL;
    
    public function __construct()
    {
        parent::__construct();
        self::$key = C('CRYPT_KEY');
    }
    /**
     * 登陆界面
     *
     * @author 周阳阳 2017年3月16日 下午3:54:23
     */
    public function index()
    {
        layout(false);
      
        $this->display();
    }
    
    
    public function in()
    {
       if(IS_POST){
           $admin_name = I('post.user_name');
           $password = I('post.password');
           $key = I('post.key');
           $code = I('post.code');
           $_model = M('backstage_users');
           $where = array(
               'user_name' => $admin_name,
               'key' => (int)$key,
               'is_delete' => 0
           );
           $user_info = $_model->field('user_pass,id,user_name,key')->where($where)->find();
           if(!empty($user_info)){
               $verify = new Verify();
               if(!$verify->check($code)){
                   $this->error(L('sign_code_fail'));
               }
               if($user_info['key'] !=$key){
                   $this->error(L('sign_key_fail'));
               }
               if(($user_info['user_pass'] ==  md5($password)) && !empty($user_info['user_pass'])){
                   session('admin_id', Crypt::encrypt((int) $user_info['id'], self::$key));
                   session('admin_name', Crypt::encrypt((string) $user_info['user_name'], self::$key));
                   (new LogModel())->logs(0,0,'登录成功');
                   $this->redirect('/Backstage');
               }else{
                  $this->error(L('sign_nameorpass_fail'));   
               }
           }else{
               //
               $this->error(L('sign_fail'));
               
           }
       }
    }
    
    
    public function out()
    {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_name']);
        $this->success('退出成功!', U('/Backstage/Sign/in'));
    }
}