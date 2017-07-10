<?php
/**
 * 文章管理系统 控制器
 * @Author ZYIMM <799783009@qq.com>
 * @version 1.0
 * Create By 2017年3月14日 下午8:04:13  版权所有@copyright 2013~2017 www.zyimm.com
 */
namespace Front\Controller;

use Front\Model\ArticleModel;

class ArticleController extends BaseController
{

    /**
     * 详情页
     * 
     * @author 周阳阳 2017年5月22日 下午5:37:52
     */
    public function view()
    {
        $article_id = I('get.id',0,'intval');
        $article_model = new ArticleModel();
        $data = [];
        $data = $article_model->view($article_id);
        $this->assign('left_list', $data['left_list']);
        $this->assign('content',$data['content']);
        $this->display();
        
    }

    /**
     * 文章列表页
     * 
     * @author 周阳阳 2017年5月22日 下午5:37:46
     */
    public function lists()
    {
        $id = I('get.id', 0, 'intval');
        $where = array(
            'id' => $id,
            'is_hidden' => 0
        );
        $article_model = new ArticleModel(); 
        $field = 'type_name as title,type_keyword as keyword,type_info as describle,type_set';
        $data = $article_model->getCategoryList($where, $field);
      
        if (empty($data['list'])) {
            $this->error('数据id查询不到！');  
        }
        
        $data = current($data['list']);
        $type_set = $data['type_set'];
        
        $left_list = $article_model->getCategoryLeft($id);
        $this->assign('left_list', $left_list);
        if ($type_set == 0) { // 单网页
            $field = 'id,type_name as title,type_content as art_content,add_time as art_time';
            $data = $article_model->getCategoryList($where, $field);
            $this->assign('content', current($data['list']));
            $this->display('view');
        } else { //
            $map = array(
                'type_id' => $id,
                'pagesize' => 8
            );
            $field = "id,title,art_set,art_time,art_url,art_img,art_info";
            $data = $article_model->getArticleList($map, $field);
            $this->assign('article', $data);
            $this->assign('list', $data['list']);
            $this->assign('page', $data['page']);echo($data['page']);
            $this->display('lists');
        }
    }
}