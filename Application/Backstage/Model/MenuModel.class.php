<?php
namespace Backstage\Model;

use Think\Model;
use Think\Page;

class MenuModel extends Model
{

    protected $tableName = 'backstage_menu';
    
    public static $_model = null;

    public static function init()
    {
        if (empty(self::$_model)) {
            
            self::$_model = new self();
        }
        return self::$_model->getMenu();
    }

    public function getMenu()
    {
        
        $category_level_first = array();
        // 获取一级菜单
        $category_level_first = self::$_model->where([
            'parent_id' => 0,
            'status' => 1
        ])->order('id asc')->cache(true,C('CACHE_SIZE'))->select();
        // 根据一级菜单获取获取二级级菜单
        $category_level = array();
        foreach ($category_level_first as $k => $v) { 
            $category_level[$v['id']] = self::$_model->where([
                'parent_id' => $v['id'],
                'status' => 1
            ])->cache(true,C('CACHE_SIZE'))->select();
        }
        // 菜单整合
        foreach ($category_level as $k => $v) {
            // $category_level[$v['id']]=$model->where(['pid'=>$v['id'],'status'=>1])->select();
            foreach ($v as $key => $val) {
                $category_level[$k][$key]['children'] = self::$_model->where([
                    'parent_id' => $val['id'],
                    'status' => 1
                ])->cache(true,C('CACHE_SIZE'))->select();
            }
        }
        return array(
            'category_level_first' => $category_level_first,
            'category_level' => $category_level
        );
    }
    
    public  static function getMenuId($url = '')
    {
        if (empty(self::$_model)) {
        
            self::$_model = new self();
        }
        return  self::$_model->where(['url'=>$url])->cache(true)->getField('id');
    }
    
    public function navigation($where = array(), $field = '*', $page_now = 1, $page_size = 0)
    {
        $where['is_delete'] = 0;
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $model = $this->table($this->tablePrefix . 'navigation');
        $rows = $model->field($field)
            ->where($where)
            ->page($page_now, $page_size)
            ->order('id desc')
            ->select();
        $count = $model->where($where)->count();
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        return array(
            'rows' => $rows,
            'page' => $page_show,
            'search' => ''
        );
    }
}