<?php
namespace Backstage\Model;

use Think\Model;
use Think\Page;

class LinkModel extends Model
{

    protected $tableName = 'link';

    public function getLinkList($where = array(), $field = '*', $page_now = 1, $page_size = 0)
    {
        $where['is_delete'] = 0;
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $rows = $this->field($field)
            ->where($where)
            ->page($page_now, $page_size)
            ->order('id desc')
            ->select();
        $count = $this->where($where)->count();
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        return array(
            'rows' => $rows,
            'page' => $page_show,
            'search' => ''
        );
    }

    public function getAdById($ad_id = 0)
    {
        $where['is_delete'] = 0;
        return $this->where($where)->find();
    }
}

