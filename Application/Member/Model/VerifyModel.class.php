<?php
namespace Member\Model;

use Think\Model;
use Think\Crypt;

class VerifyModel extends Model
{
    public $code = null;
    public static $key = 'ZYIMM';
    
    /**
     * 
     * @param number $ukey
     * @return boolean
     * @author 周阳阳 2017年4月18日 下午2:20:17
     */
    public function addCode($ukey = 0)
    {
        $data = array(
            'code'=>$this->code,
            'send_time'=>time(),
            'ukey'=>$ukey    
        );
        return $this->add($data);
    }
     
    /**
     * 
     * @param string $code
     * @return boolean
     * @author 周阳阳 2017年4月18日 上午11:46:15
     */
    public function email($code = '')
    {
        if(!empty($code)){
            $code = null;
            $email = null;
            $email= Crypt::decrypt($code,self::$key);
            $where = array(
                'code' =>$code
            );
            $code = $this->where($where)->getField('code');
            if(($code == $email) && (!empty($code) && !empty($email))){
                $this->where($where)->delete();
                return $email;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    
    /**
     * mobile#code
     * 
     * @param string $code
     * @param number $mobile
     * @return boolean
     * @author 周阳阳 2017年5月5日 下午4:07:23
     */
    public function mobile($code = '',$mobile = 0)
    {
        if (! empty($code) && ! empty($mobile)) {
            $data = array();
           
            $data = $this->where($where)->find();
            $mobile_code = Crypt::decrypt($data['code'], self::$key);
            $mobile_code = explode('#', $mobile_code);
            if (! empty($data) && $data['send_time'] < time() &&
                array_shift($mobile_code) == $mobile && $code== array_shift($mobile_code)) {
                $this->where($where)->delete();
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * 密码重置1/2
     * 
     * @param string $code
     * @param string $mobile
     * @param string $password
     * @return boolean|boolean|string|string
     * @author 周阳阳 2017年5月5日 下午4:21:45
     */
    public function password($code = '',$mobile ='',$password = '')
    {
        if (! empty($code) && ! empty($mobile) && !empty($password)) {
            $data = array();
            $data = $this->where($where)->find();
            $mobile_code = Crypt::decrypt($data['code'], self::$key);
            $mobile_code = explode('#', $mobile_code);
            if (! empty($data) && $data['send_time'] < time() && array_shift($mobile_code) == $mobile && $code == array_shift($mobile_code)) {
                $this->where($where)->delete();
                // 依据手机号找到uid
                $uid = $this->table($this->tablePrefix . 'members')
                    ->where([
                        'mobile' => $mobile,
                        'is_deny'=>0
                    ])->getField('id');
                $result = $this->resetPassword($password,$uid);
                if($result === true){
                    return true;
                }else{
                    return $result;
                }
            } else {
                return '验证码不匹配';
            }
        } else {
            return '参数不对';
        }
    }
    /**
     * 密码重置2/2
     * 
     * @param string $password
     * @param int $uid
     * @return boolean|string
     * @author 周阳阳 2017年5月5日 下午4:22:02
     */
    private function resetPassword($password = '',$uid = 0)
    {
        if (! empty($password) && !empty($uid)) {
            //
            $result = $this->table($this->tablePrefix . 'members')
            ->where([
                'id' => $uid,
                'is_deny'=>0
            ])->setField('user_pass',(string)$password);
            if((int)$result>0){
                
                return true;
            }else{
                return '密码重置失败';
            }
        } else {
            return '修改参数不对';
        }
    }
    
    
    /**
     * 解绑邮箱
     * 
     * @param string $code
     * @param string $email
     * @param number $uid
     * @author 周阳阳 2017年5月5日 下午3:56:12
     */
    public function unBingEmail($code = '',$email = '',$uid = 0)
    {
        
    }
    /**
     * 解绑手机
     * 
     * @param string $code
     * @param string $mobile
     * @param number $uid
     * @author 周阳阳 2017年5月5日 下午3:56:27
     */
    public function unBingMobile($code = '',$mobile = '',$uid = 0)
    {
        
    }
    /**
     * 重置邮箱
     * 
     * @param string $code
     * @param string $email
     * @param number $uid
     * @author 周阳阳 2017年5月5日 下午3:55:29
     */
    public function resetEmail($code = '',$email = '',$uid = 0)
    {
        $result = $this->unBingEmail($code,$email,$uid);
        if($result === true){
            $result =  $this->email($code);   
        }   
        return $result;
    }
    
    /**
     * 重置手机
     * 
     * @param string $code
     * @param string $mobile
     * @param number $uid
     * @author 周阳阳 2017年5月5日 下午3:55:54
     */
    public function resetMobile($code = '',$mobile = '',$uid = 0)
    {
        $this->unBingMobile($code,$mobile,$uid);
    }
    /**
     * 手机邮箱是否可用
     * 
     * @param mix $needle
     * @param number $type
     * @return boolean
     * @author 周阳阳 2017年4月18日 下午4:31:53
     */
    public function available($needle = null,$type = 0)
    {
        $where = array();
        if(empty($type)){
            $where['mobile'] = $needle;
            
        }else{
            $where['email'] = $needle;
        }
        if(($this->where($where)->count())>0){
            return true;
        }else{
            return false;
        }
    }
    
    public  function buildCode($needle = null)
    {
        $this->code = Crypt::encrypt($needle, self::$key);
        return $this;
    }
    
    public static function getMemberStatus($uid = 0)
    {
        $model = new self();
        $data = $model->table($model->tablePrefix . 'member_status')
            ->where(['uid' => (int)$uid])->find();
        $html = '';
        if(!empty($data['mobile_status'])){
            $html.="<i class='iconfont text-main margin'>&#xe759;</i>";
        }else{
            $html.="<i class='iconfont text-gray margin'>&#xe759;</i>";
        }
        
        if(!empty($data['email_status'])){
            $html.="<i class='iconfont text-main margin'>&#xe6cc;</i>";
        }else{
            $html.="<i class='iconfont text-gray margin'>&#xe6cc;</i>";
        }
        
        if(!empty($data['id_status']) && $data['id_status']==2){
            $html.="<i class='iconfont text-main margin'>&#xe60f;</i>";
        }else{
            $html.="<i class='iconfont text-gray margin'>&#xe60f;</i>";
        }
        
        if(!empty($data['vip_status'])){
            $html.="<i class='iconfont text-main margin'>&#xe620;</i>";
        }else{
            $html.="<i class='iconfont text-gray margin'>&#xe620;</i>";
        }

        return $html;
    }
}