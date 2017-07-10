<?php
namespace Backstage\Controller;

use Backstage\Model\LogModel;
use Think\Cache;
use Backstage\Model\GlobalModel;

class GlobalController extends BaseController
{

    public $_model = NULL;
    
    public function parameter()
    {
        $this->_model = new GlobalModel();
        $this->assign('global_list',$this->_model->getList());
        $this->display();
    }
    
    public function saveParameter()
    {
        if(IS_POST){
            $this->_model = new GlobalModel();
            $result = $this->_model->saveGlobal(I('post.'));
            if($result === true){
                $this->success('操作成功');
            }else{
                $this->error($result);
            }
        }
    }
    
    /**
     * 缓存清除
     * 
     * @author 周阳阳 2017年4月28日 上午11:00:17
     */
    public function clearCache()
    {
        // 管理员操作日志
        (new LogModel())->logs(0,1,'执行了所有缓存清除操作！',$this->adminName);
        $dirs = array(
            APP_PATH . 'Runtime/Cache'
        );
        
        foreach ($dirs as $value) {
            rmdirr($value);
            @mkdir($value, 0777, true);
        }
        $dirs = array(
            APP_PATH . 'Runtime/Data'
        );
        foreach ($dirs as $value) {
            rmdirr($value);
            @mkdir($value, 0777, true);
        }
        $dirs = array(
            APP_PATH . 'Runtime/Temp'
        );
        foreach ($dirs as $value) {
            rmdirr($value);
            @mkdir($value, 0777, true);
        }
        // 非文件缓存
        Cache::getInstance()->clear();
        $runTimeFiles = APP_PATH . 'Runtime/~runtime.php';
        if (file_exists($runTimeFiles)) {
            if (unlink($runTimeFiles)) {
                $this->ajaxReturn(['√缓存清除ok！']);
            }
        } else {
            $this->ajaxReturn(['√缓存清除ok！']);
        }
    }
    
    public function log()
    {
        $this->_model = new LogModel();
        $page_now = I('get.p',1);
        $map = array_merge(I('post.'),I('get.'));
        $field = '*';
        $row = $this->_model->getLogList($map,$field,$page_now);
        $this->assign('rows',$row['rows']);
        $this->assign('search',$row['search']);
        $this->assign('page',$row['page']);
        $this->display();
    }
    
    
}