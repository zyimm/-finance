<?php
/**
 * 文章模型
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月14日 下午8:07:29  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Model;

use Think\Model;
use Think\Page;
use Common\Model\TreeModel;

class ArticleModel extends Model
{

    public function article($where = array(), $field = '*', $page_now = 1, $page_size = 0)
    {
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $page_now = empty($page_now) ? 1 : $page_now;
        $rows = $this->alias('a')
            ->field($field)
            ->join("{$this->tablePrefix}article_category as c on c.id=a.type_id", 'left')
            ->where($where)
            ->order(' a.id DESC ')
            ->page($page_now, $page_size)
            ->select();
        $count = $this->alias('a')
            ->field($field)
            ->join("{$this->tablePrefix}article_category as c on c.id=a.type_id", 'left')
            ->where($where)
            ->order(' a.id DESC ')
            ->count();
        
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        return array(
            'rows' => $rows,
            'page' => $page_show,
            'search' => ''
        );
    }

    public function category($where = array(), $field = '*', $page_now = 1, $page_size = 0)
    {
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $page_now = empty($page_now) ? 1 : $page_now;
        $rows = $this->table($this->tablePrefix . 'article_category')
            ->field($field)
            ->where($where)
            ->order('id DESC ')
            ->page($page_now, $page_size)
            ->select();
        $_rows = $this->table($this->tablePrefix . 'article_category')
            ->field($field)
            ->where([
            'is_delete' => 0
        ])
            ->select();
        $tree = (new TreeModel($_rows))->getTrees();
        $count = $this->table($this->tablePrefix . 'article_category')
            ->field($field)
            ->where($where)
            ->count();
        
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        return array(
            'rows' => $rows,
            'tree' => $tree,
            'page' => $page_show,
            'search' => ''
        );
    }

    /**
     * 获取特定栏目下文章列表
     *
     * @param array $parm            
     * @return array
     */
    public function getArticleList($parm = array(), $field = '*', $order = 'sort_order desc,id DESC')
    {
        if (empty($parm['type_id'])) {
            return false;
        }
        $page = I('get.p', 1, 'intval');
        if (S($_SERVER['PATH_INFO'] . $parm['type_id'] . $page)) {
            return S($_SERVER['PATH_INFO'] . $parm['type_id'] . $page);
        }
        $type_id = intval($parm['type_id']);
        $map['type_id'] = $type_id;
        // 查询条件
        $parm['pagesize'] = empty($parm['pagesize']) ? 10 : $parm['pagesize'];
        // 分页处理
        $count = $this->where($map)->count('id');
        $p = new Page($count, $parm['pagesize']);
        $page_show = $p->show();
        $data = $this->field($field)
            ->where($map)
            ->order($order)
            ->limit($page, $parm['pagesize'])
            ->select();
        
        foreach ($data as $key => $v) {
            // 跳转
            if ($v['art_set'] == 1 && ! empty($v['art_url'])) {
                $data[$key]['arturl'] = (stripos($v['art_url'], "http://") === false) ? "http://" . $_SERVER['HTTP_HOST'] . '/' . $v['art_url'] : $v['art_url'];
            } else {
                $data[$key]['arturl'] = U('/article/view/' . $v['id']);
            }
        }
        $row = array();
        $row['list'] = $data;
        $row['page'] = $page_show;
        S($_SERVER['PATH_INFO'] . $parm['type_id'] . $page, $row, C('CACHE_TIME'));
        return $row;
    }

    /**
     * 前后文章
     *
     * @param number $id            
     * @param number $pid            
     * @return string
     */
    private function prevNext($id = 0, $pid = 0)
    {
        if (empty($id)) {
            return false;
        }
        
        if (S('prevNext' . $id . '-' . $pid)) {
            return S('prevNext' . $id . '-' . $pid,C("CACHE_SIZE"));
        }
        $data = array();
        $content = '';
        if ($id == 1) {
            $data = $this->field('id,art_url,title')
                ->where("id<{$id} and type_id={$pid}")
                ->order('id desc')
                ->find();
            $content = "<div class='prev-article'>上一篇:<a href='javasctipt:void()' >上一篇没有</a></div>'";
            $content .= "<div class='next-article'>下一篇:<a href='{$data['art_url']}' title='{$data['title']}'>{$data['title']}</a></div>'";
        } else {
            // prev
            $data = $this->field('id,art_url,title')
                ->where(" id<{$id} and type_id={$pid}")
                ->order('id desc')
                ->find();
            if ($data) {
                if (empty($data['art_url'])) {
                    $data['art_url'] = U('article/view/' . $data['id']);
                }
                
                $content = "<div class='prev-article'>上一篇:<a href='{$data['art_url']}' title='{$data['title']}'>{$data['title']}</a></div>'";
            } else {
                $content = "<div class='prev-article'>上一篇:<a href='javascript:void()' >上一篇没有</a></div>'";
            }
            // next
            $data = $this->field('id,art_url,title')
                ->where("id>{$id} and type_id={$pid}")
                ->order('id desc')
                ->find();
            if ($data) {
                if (empty($data['art_url'])) {
                    $data['art_url'] = U('article/view/' . $data['id']);
                }
                $content .= "<div class='next-article'>下一篇:<a href='{$data['art_url']}' title='{$data['title']}'>{$data['title']}</a></div>'";
            } else {
                
                $content .= "<div class='prev-article'>下一篇:<a href='javascript:void()' >下一篇没有</a></div>'";
            }
        }
        S('prevNext' . $id . '-' . $pid, $content,C("CACHE_SIZE"));
        return $content;
    }

    public function getCategoryList($map = array(), $field = '*', $order = 'sort_order desc,id desc', $page_now = 1, $page_size = 10)
    {
        $_model = M('article_category');
        
        if (empty($page_size)) {
            $page_size = C('PAGE_SIZE');
        }
        $data = array(
            'list' => array(),
            'page' => ''
        );
        $count = $_model->field($field)
            ->where($map)
            ->count('id');
        $list = $_model->field($field)
            ->where($map)
            ->order($order)
            ->page($page_now, $page_size)
            ->select();
        $page = new Page($count, $page_size);
        $page_show = $page->show();
        $data['list'] = $list;
        $data['page'] = $page_show;
        return $data;
    }

    public function getCategoryLeft($id = 0)
    {
        if (S('getLeft-data-' . $id)) {
            return S('getLeft-data-' . $id,C("CACHE_SIZE"));
        }
        $_model = M('article_category');
        $row = [];
        $pid = $_model->where("id={$id}")->getField('parent_id');
        $row = $_model->field('id,type_name,type_url')
            ->where("parent_id={$pid} and is_hidden=0")
            ->order('sort_order desc')
            ->select();
        foreach ($row as $k => $v) {
            $row[$k]['type_url'] = U('/article/lists/' . $v['id']);
        }
        S('getLeft-data-' . $id, $row,C("CACHE_SIZE"));
        return $row;
    }

    public function ad($where = [], $field = '*', $page_now = 1, $page_size = 0)
    {
        $_model = M('ad');
        $where['is_delete'] = 0;
        $page_size = empty($page_size) ? C('PAGE_SIZE') : $page_size;
        $rows = $_model->field($field)
            ->where($where)
            ->page($page_now, $page_size)
            ->select();
        $count = $_model->where($where)->count();
        $page = new Page($count, $page_size, I('post.'));
        $page_show = $page->show();
        return array(
            'rows' => $rows,
            'page' => $page_show,
            'search' => ''
        );
    }

    public function view($article_id = 0)
    {
        if (! empty($article_id)) {
            
            $where = [
                'is_delete' => 0,
                'id' => $article_id
            
            ];
            $content = $this->where($where)->find();
            if(empty($content)){
                return false;
            }
            $leftList = $this->getCategoryLeft($content['type_id']);
            $parent_id = $this->table($this->tablePrefix . 'article_category')
                ->where([
                'id' => $content['type_id'],
                'is_delete' => 0,
            ])->getField('id');
            if(empty($parent_id)){
                return false;
            }
            return [
                'left_list' => $left_list,
                'content' => $content,
                'prev_next' => $this->prevNext($id,$parent_id)
            ];
        } else {
            
            return false;
        }
        
    }
}