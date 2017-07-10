<?php
namespace Backstage\Model;

use Think\Model;

class AdminModel extends Model
{

    protected $tableName = 'backstage_users';

    /**
     * 获取指定后台用户的信息
     * 
     * @param number $admin_id            
     */
    public function getAdminInfo($admin_id = 0)
    {
        $id = (int) $admin_id;
        $pre = C('DB_PREFIX');
        return $this->field('a.*,r.title as role_name')
            ->alias('a')
            ->join($pre . 'auth_group as r on a.role_id=r.id', 'left')
            ->where("a.id={$id}")
            ->find();
    }

    /**
     * 获取后台用户列表
     * 
     * @param array $map            
     * @param string $field            
     * @return array
     */
    public function getAdminList($map = [], $field = '*')
    {
        $row = [];
        $pre = C('DB_PREFIX');
        $row = $this->field($field)
            ->alias('a')
            ->join($pre . 'auth_group as r on a.role_id=r.id', 'left')
            ->where($map)
            ->select();
        return $row;
    }

    public function getRoleList($map = array(), $field = '*')
    {
        $row = [];
        $row = M('auth_group')->field($field)
            ->where($map)
            ->select();
        foreach ($row as $k => $v) {
            $row[$k]['auth_nums'] = $this->where(['id' => ['in',$v['rules']]])->count();
        }
        return $row;
    }

    /**
     * 生成树状的 权限
     * 
     * @param number $role_id            
     */
    public function getAuthList($role_id = 0)
    {
        $model = M('auth_rule');
        $temp_auth_list = $model->where([
            'status' => 1
        ])->order('id asc')->select();
        $self_auth = M('auth_group')->where([
            'id' => $role_id
        ])->getField('rules');
        $self_auth = explode(',', $self_auth);
        $auth_list = [];
        foreach ($temp_auth_list as $v) {
            $auth_list[$v['id']] = $v;
        }
        foreach ($auth_list as $k => $v) {
            if (in_array($v['id'], $self_auth)) {
                $auth_list[$k]['check'] = 1;
            } else {
                $auth_list[$k]['check'] = 0;
            }
            $auth_list[$v['pid']]['node'][$v['id']] = &$auth_list[$v['id']];
        }
        
        return $auth_list[0]['node'];
    }

    /**
     * 绑定角色
     * 
     * @param number $admin_id            
     * @param number $role_id            
     * @return boolean
     */
    public function bingRole($admin_id = 0, $role_id = 0)
    {
        if ($admin_id == 0 || $role_id == 0) {
            return false;
        } else {
            $model = M('auth_group_access');
            //
            if ($model->where([
                'uid' => $admin_id
            ])->count()) {
                if ($model->where([
                    'uid' => $admin_id
                ])->setField('group_id', $role_id)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                $data = [
                    'uid' => $admin_id,
                    'group_id' => $role_id
                ];
                if ($model->add($data)) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * 检测邮箱
     * 
     * @param number $admin_id            
     * @param string $email            
     * @return boolean
     */
    public function checkMobile($admin_id = 0, $email = '')
    {
        if ($this->where([
            'mobile' => $email,
            'id' => [
                'neq',
                $admin_id
            ]
        ])->count()) {
            return false;
        } else {
            return true;
        }
    }
}