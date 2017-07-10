<?php
namespace Member\Controller;

use Think\Controller;
use Think\Verify;
use Think\Crypt;
use Member\Model\VerifyModel;
use Front\Model\MessageModel;
use Front\Model\ToolModel;

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
        $this->assign('url', __CONTROLLER__);
        $this->display();
    }

    public function in()
    {
        if (IS_AJAX) {
            $user_name = I('post.user_name');
            $password = I('post.password');
            $code = I('post.code');
            $_model = M('members');
            $where = array(
                'user_name' => $user_name,
                'is_deny' => 0
            );
            $user_info = $_model->field('user_pass,id,user_name')
                ->where($where)
                ->find();
            if (! empty($user_info)) {
                $verify = new Verify();
                if (! $verify->check($code, null)) {
                    $this->error(L('sign_code_fail'));
                }
                if (($user_info['user_pass'] == md5($password)) && ! empty($user_info['user_pass'])) {
                    session('user_id', Crypt::encrypt((int) $user_info['id'], self::$key));
                    session('user_name', Crypt::encrypt((string) $user_info['user_name'], self::$key));
                    $this->success('ok');
                } else {
                    $this->error(L('sign_nameorpass_fail'));
                }
            }else{
                $this->error(L('sign_fail').$_model->getLastSql());
            }
        }
    }
    
    public function reg()
    {
        
        if(IS_AJAX){
           
            
            
        }else{
            $this->display();
            
        }
        
    }
    
    
    /**
     * 获取验证码
     * 
     * @author 周阳阳 2017年5月5日 下午4:33:37
     */
    public function verify()
    {
        $config = array(
            'fontSize' => 14, // 验证码字体大小
            'length' => 4, // 验证码位数
            'useNoise' => false, // 关闭验证码杂点
            'imageW' => 100,
            'useCurve'=>false,
        );
        $Verify = new \Think\Verify($config);
        $Verify->entry();
    }
    /**
     * 获取手机短信
     * 
     * @author 周阳阳 2017年5月5日 下午4:31:36
     */
    public function getSms()
    {
        $mobile = I('post.mobile');
        $mobile = '15358461826';
        if(ToolModel::isMobile($mobile)){
            if(!session('user_id')){
                //未登录要检查验证码
                $verify = new Verify();
                if (! $verify->check($verif_code, null)) {
                    $this->error(L('sign_code_fail'));
                }
            }
            dump(MessageModel::smsTip('verify_phone',$mobile));
        }else{
            $this->error('短信获取失败!');
        }     
    }
    public function getEmail()
    {
        $email = I('post.email');
        if(ToolModel::isEmail($email)){
            
            dump(MessageModel::smsTip('verify_phone',$mobile));
        }else{
            $this->error('短信获取失败!');
        }
    }
    /**
     * 验证邮箱
     * 
     * @author 周阳阳 2017年5月5日 下午4:33:13
     */
    public function email()
    {
        $code = (string)I('get.code','');
        $model = new VerifyModel();
        $email = $model->email($code);
        if(!is_bool($email)){
            if($model->bingEmail($email)){
                
            }else{
                
            }
        }else{
            
        }
    }
    /**
     * 找回密码
     * 
     * @author 周阳阳 2017年5月5日 下午4:32:54
     */
    public function findPassword()
    {
        
        if(IS_POST){
            $model = new VerifyModel();
            $reslut = $model->password($code,$mobile,$password);
        }
        $this->display();
    }
    
    
    
    
}