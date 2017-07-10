<?php
namespace Backstage\Model;

use Think\Model;
use Think\Page;
use Front\Model\ConfigModel;

class BorrowModel extends Model
{

    protected $tableName = 'borrow_info';

    public function getBorrowList($where = array(), $field = '*', $page_now = 1, $page_size = 0)
    {
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        
        $rows = $this->alias('b')
            ->field($field)
            ->join($this->tablePrefix . 'members as m on m.id = b.borrow_uid', 'left')
            ->join($this->tablePrefix . "borrow_verify as v ON b.id=v.borrow_id",'left')
            ->where($where)
            ->page($page_now, $page_size)
            ->order('b.id desc')
            ->select();
        if(empty($rows)){
            $borrow_ids = '';
        }else{
            $borrow_ids = array_column($rows,'id');
        }
        $count = $this->alias('b')
            ->join($this->tablePrefix . 'members as m on m.id = b.borrow_uid', 'left')
            ->join($this->tablePrefix . "borrow_verify as v ON b.id=v.borrow_id",'left')
            ->where($where)
            ->count();
        $investor_detail = $this->table($this->tablePrefix . 'investor_detail')
            ->field('deadline,sort_order,status,borrow_id,sum(capital + interest)')
            ->where(['borrow_id'=>['in',$borrow_ids]])
            ->order("deadline ASC")
            ->select();
        $_temp = [];
        $_need = [];
        if(empty($investor_detail)){
            $investor_detail = [];
        }else{
            foreach ($investor_detail as $k => $v) {
                if(empty($v['borrow_id'])){
                    continue;
                }
                $_temp[$v['borrow_id']] = $v;
                $_need[$v['borrow_id']] = $v;
            }
        } 
        //REPAYMENT_TYPE
        $config = ConfigModel::read('borrow_config');
        $repayment_type = $config['REPAYMENT_TYPE'];
        foreach ($rows as $k => $v) {
            $rows[$k]['repayment_time'] = empty( $_temp[$k]['deadline']) ? '' : $_temp[$k]['deadline'];
            $rows[$k]['sort_order'] = empty($_temp[$k]['sort_order']) ? '' : $_temp[$k]['sort_order'];
            $rows[$k]['auto'] = "auto";  
            $rows[$k]['borrow_type'] = $config['BORROW_USE'][$v['borrow_type']];
            $rows[$k]['need_money'] = empty($_need[$k]['need']) ? '0.00' : $_need[$k]['need'];
        }
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        $row = array(
            'rows' => $rows,
            'page' => $page_show,
            'search' =>''
        );S(__ACTION__,$row);
        return $row;
    }
}