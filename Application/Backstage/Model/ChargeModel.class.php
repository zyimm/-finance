<?php
namespace Backstage\Model;

use Think\Model;
use Front\Model\ConfigModel;
use Common\Service\WhereBuilder;
use Think\Page;
use Front\Model\PayModel;

class ChargeModel extends Model
{
    protected $tableName = 'member_payonline';
    
    public static $config = [];
    
    public static $payStatus = [];
    
    public function _initialize(){
        self::$config= array_merge([
            'off' => ['key'=>'off','name'=>'线下支付']
        ], ConfigModel::read("pay_config"));
        self::$payStatus = PayModel::$payStatus;
    }

    public function getChargeList($where = [], $field = '*', $page_now = 1, $page_size = 0)
    {
        $search = array(
            'user_name'=>array(
                'name'=>'会员名',
                'type'=>'input',
                'tip'=>'不填则不限制',
                'value'=>empty($where['user_name'])?'':$where['user_name']
            ),
            'deal_user'=>array(
                'name'=>'处理人',
                'type'=>'input',
                'tip'=>'不填则不限制',
                'value'=>empty($where['deal_user'])?'':$where['deal_user']
            ),
  
            'star'=>array(
                'name'=>'充值时间(开始)',
                'type'=>'date',
                'tip'=>'只选开始时间则查询从开始时间往后所有',
                'value'=>empty($where['star'])?'':$where['star']
            ),
            'end'=>array(
                'name'=>'充值时间(结束)',
                'type'=>'date',
                'tip'=>'只选开始时间则查询从开始时间往后所有',
                'value'=>empty($where['end'])?'':$where['end']
            ),
        
        );
        
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $page_now = empty($page_now) ? 1 : $page_now;
        $rows = $this->alias('p')
            ->field($field)
            ->join($this->tablePrefix . 'members as m on p.uid = m.id')
            ->where($where)
            ->order('p.id desc')
            ->page($page_now, $page_size)
            ->select();
        $count = $this->alias('p')
            ->field($field)
            ->join($this->tablePrefix . 'members as m on p.uid = m.id')
            ->where($where)
            ->count();
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        $row = array(
            'rows' => $rows,
            'page' => $page_show,
            'search' => WhereBuilder::buidFrom($search)
        );
        return $row;
        
       
    }
    
    public function getChargeListForChart()
    {
        $week_times = array(
            strtotime('-1 weeks'),
            strtotime(date('Y-m-d', time()))
          
        );
        $week_times_serialize = array(
            date('m月-d日',strtotime('-6 day')),
            date('m月-d日',strtotime('-5 day')),
            date('m月-d日',strtotime('-4 day')),
            date('m月-d日',strtotime('-3 day')),
            date('m月-d日',strtotime('-2 day')),
            date('m月-d日',strtotime('-1 day')),
            date('m月-d日',time())
           
        );
        $where = array(
            'status'=>1,
            'add_time'=>['between',$week_times]
        );
        $rows = $this->field('money')->where($where)->select();
        $rows = array_column($rows,'money');
        foreach ($rows as $k=>$v){
            if(empty($v)){
                $rows[$k] = 0.00;
            }
        }
        $json = array(
            'title'=>array(
                'text'=>'最近一周充值记录统计 ',
                'x'=>-20,
        
            ),
            'subtitle'=>array(
                'text'=>'数据来源:统计中心',
                'x'=>-20,
        
            ),
            'xAxis'=>[
                'categories'=>$week_times_serialize
        
            ],
            'yAxis' => array(
                'title' => '金额（元）',
                'plotLines' => [
                    [
                        'value' => 0,
                        'width' => 1,
                        'color' => '#808080'
                    ]
                ]
            ),
            'tooltip'=>[
                'valueSuffix'=> '元'
            ],
            'legend'=>[
                'layout'=> 'vertical',
                'align'=> 'right',
                'verticalAlign'=> 'middle',
                'borderWidth'=> 0
            ],
            'series'=>[
                [
                    'name'=>'充值记录 ',
                    'data'=>$rows
        
                ]
            ]
        );
        return json_encode($json);
    }
    
    public function deal($id,$status = 3)
    {
        if((int)$id>0){
            $where = [
                'id'=>$id,
                'status'=>0,           
            ];
            $data = [];
            $data = $this->where($where)->find();
            $status = ($status == 1)?$status:3;
            if(!empty($data)){
                if($this->where($where)->setField('status',$status)){
                    return true;
                }else{
                    return '处理失败不存在';
                }
            }else{
                return '数据不存在';
            }
            
        }else{
            
            return '处理id不存在!';
        }
    }
}