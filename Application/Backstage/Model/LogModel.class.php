<?php
namespace Backstage\Model;

use Think\Model;
use Think\Page;
use Common\Service\WhereBuilder;
use Think\Crypt;

class LogModel extends Model
{

    protected $tableName = 'backstage_logs';


    /**
     * 后台日志
     * 
     * @param number $deal_id
     * @param number $log_status
     * @param string $deal_info
     * @param string $deal_user
     * @return bool
     * @author 周阳阳 2017年5月2日 下午2:45:01
     */
    public function logs($deal_id= 0, $log_status = 0, $deal_info = '', $deal_user = '')
    {
        $arr = array();
        $arr['log_type'] = __ACTION__;
        $arr['deal_id'] = $deal_id;
        $arr['log_status'] = $log_status;
        $arr['deal_info'] = $deal_info;
        $arr['deal_user'] = ($deal_user) ? $deal_user : Crypt::decrypt(session('admin_name'),C('CRYPT_KEY'));
        $arr['deal_ip'] = get_client_ip();
        $arr['deal_time'] = time();
        return $this->add($arr);
    }
    
    
    public function getLogList($where = array(),$field = '*',$page_now = 1,$order='id desc',$page_size = 0)
    {
        $search = array(
            'deal_user'=>array(
                'name'=>'操作者',
                'type'=>'input',
                'tip'=>'不填则不限制',
                'value'=>empty($where['deal_user'])?'':$where['deal_user']
            ),
            'star'=>array(
                'name'=>'操作时间(开始)',
                'type'=>'date',
                'tip'=>'只选开始时间则查询从开始时间往后所有',
                'value'=>empty($where['star'])?'':$where['star']
            ),
            'end'=>array(
                'name'=>'操作时间(结束)',
                'type'=>'date',
                'tip'=>'只选开始时间则查询从开始时间往后所有',
                'value'=>empty($where['end'])?'':$where['end']
            ),
        
        );
        $_where = array();
       
        if($where){
            $_where['deal_user'] = array(
                'condition'=>'eq',
                'value'=>$where['deal_user']
            );
            $_where['deal_time'] = array(
                'condition'=>'EGT',
                'value'=>empty($where['star'])?'':strtotime($where['star'])
            );
            if($where['end']){
                $_where['deal_time'] = array(
                    'condition'=>'ELT',
                    'value'=>strtotime($where['end'])
                );
            }
            if($where['star']){
                $_where['deal_time'] = array(
                    'condition'=>'BETWEEN',
                    'value'=>array(strtotime($where['star']),strtotime($where['end']))
                );
            }
            
            $where = WhereBuilder::build($_where);
        }
        
        $page_size = empty($page_size)?C('PAGE_SIZE'):$page_size;
        $page_now  = empty($page_now)?1:$page_now;
        $rows = $this->field($field)
        ->where($where)->page($page_now,$page_size)->order('id desc')->select();
        $count = $this->field($field) 
                ->where($where)->count();
        $page  = new Page($count,$page_size,I('post.'));
        $page_show =  $page->show();
     
        $row = array(
            'rows'=>$rows,
            'page'=>$page_show,
            'search'=>WhereBuilder::buidFrom($search)
        );
        return $row;
    }
}