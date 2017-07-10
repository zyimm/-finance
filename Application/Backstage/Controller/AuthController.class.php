<?php
namespace Backstage\Controller;

use Backstage\Model\AdminModel;

class AuthController extends BaseController
{
    public $_model = NULL;
    
    public function role()
    {
        $model= new AdminModel();
        $role_list=$model->getRoleList(['status'=>1]);
        $this->assign('role_list',$role_list);
        $this->display();
    }
    
    /**
     * 添加角色
     */
    public function roleAdd()
    {
        $model=M('auth_group');
        if(IS_POST){
            if($model->create()){
                self::$logModel->logs($this->adminId,0,'添加角色'.$model->user_name);
                if($model->add()){
    
                    $this->success('数据修改成功!');
                }else{
                    $this->error('数据修改失败!');
    
                }
            }else{
    
                $this->error('数据创建成功!');
            }
        }else{
    
            $this->display('roleEdit');
        }
    }
    
    public function roleEdit()
    {
        $role_id=(int)$_REQUEST['role_id'];
        if(empty($role_id)) $this->error('id不存在!');
        $model=M('auth_group');
        self::$logModel->logs($this->adminId,0,'修改角色'.$model->user_name); 
        if(IS_POST){
            if($model->create()){
    
                if($model->save()){
                    $this->success('数据修改成功!');
                }else{
                    $this->error('数据修改失败!');
                }
            }else{
                $this->error('数据创建成功!');
            }
        }else{
            $role_info=$model->where(['id'=>$role_id])->find();//dump($role_info);
            $this->assign('role_info',$role_info);
            $this->display('roleEdit');
        }
    
    
    }
    
    public function checkRole()
    {
        $model=M('auth_group');
        $role_name=I('param');
        if($model->where(['title'=>$role_name])->count()){
            $data=[
                'info'=>'角色名称被占用!',
                'status'=>'n'
            ];
        }else{
            $data=[
                'info'=>'可以使用!',
                'status'=>'y'
            ];
        }
         
        //echo $model->getLastSql();
        echo json_encode($data);
    }
    
    public function user()
    {
        $this->_model = new AdminModel();
        $map=[
            'a.is_delete'=>0,
            'r.status'=>1
        ];
        $field="a.*,r.title as role_name,r.status";
        $admin_list=$this->_model->getAdminList($map,$field);
        $this->assign('admin_list',$admin_list);
       
        $this->display();
    }
    
    public function userAdd()
    {
        $model = new AdminModel();
        if(IS_POST){
            self::$logModel->logs($this->adminId,0,'添加管理员:'.$model->user_name);
            
            if($model->create()){
                if(!empty($model->user_pass)){
                    $model->user_pass = md5($model->user_pass);
                }else{
                    $this->error('密码不能为空!');
                }
                $model->login_log_time = time();
                if($model->add()){
                    $this->success('数据修改成功!');
                }else{
                    //$this->error($model->getLastSql());
                    $this->error('数据修改失败！');
                }
            }else{
                $this->error('数据创建成功!');
            }
        
        
        }else{
            $role_list=[];
            $role_list_temp = $model->getRoleList(['id'=>['neq',1],'status'=>1]);
            foreach ($role_list_temp as $k=>$v){
                $role_list[$v['id']]=$v['title'];
            }
            $this->assign('role_list',$role_list);
            $this->assign('is_add',1);
            $this->display('userEdit');
        }
    }
    
    public function userEdit()
    {
        $admin_id=(int)$_REQUEST['id'];
        if(empty($admin_id) || $admin_id==1 ){
            $this->error('id不存在!');
        }
        $this->_model = new AdminModel();
        if(IS_POST){
            self::$logModel->logs($this->adminId,0,'修改管理员:'.$model->user_name);
            
            if($this->_model->create()){
                if(!$this->_model->checkMobile($admin_id,$this->_model->mobile)){
                    $this->error('电话已经被占用!');
                }
                $role_id = $this->_model->role_id;
                if(!empty($this->_model->user_pass)){
                    $this->_model->user_pass = md5($this->_model->user_pass);
                }else{
                    unset($this->_model->user_pass);
                }
                $this->_model->login_time = time();
                if($this->_model->save()){
                    $this->_model->bingRole($admin_id,$role_id);
                    $this->success('数据修改成功!');
                }else{
                    $this->error('数据修改失败！');
                }
            }else{
                $this->error('数据创建成功!');
            }
            
        }else{
            
            $admin_info=$this->_model->where(['id'=>$admin_id])->find();//dump($role_info);
            $role_list=[];
            $role_list_temp = $this->_model->getRoleList(['id'=>['neq',1],'status'=>1]);
            foreach ($role_list_temp as $k=>$v){
                $role_list[$v['id']]=$v['title'];
            }
            $this->assign('role_list',$role_list);
            $this->assign('admin_data',$admin_info);//dump($admin_info);
            $this->display();
        }
    }
    
    
    /**
     * 更新角色的权限
     * @param number $role_id
     */
    public function roleRule()
    {
        $role_id=(int)$_REQUEST['role_id'];
        if($role_id <=1){
            $this->error('role_id 错误!');
        }
        if(IS_POST){
            self::$logModel->logs($this->adminId,0,'更改角色权限');
            
            $auth_ids = join(',',$_POST['auth']);
            $model = M('auth_group');
             
            if($model->where(['id'=>$role_id])->setField('rules',$auth_ids)){
                $this->success('更新数据成功!');
    
            }else{
                $this->error('更新数据失败或没有更新!');
            }
            $this->success($auth_ids);
        }else{
            $model=D('Admin');
            $auth = $model->getAuthList($role_id);
            $this->assign('role_id',$role_id);
            $this->assign('auth_list',$auth);
            $this->display();
        }
    }
    
    
   
}