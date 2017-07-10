<?php
namespace Member\Controller;

use Member\Model\MessageModel;

class MessageController extends BaseController
{
    protected static $model = null;
    public function __construct(){
        parent::__construct();
        self::$model = new MessageModel();
        
    }
    public function index()
    {
       $this->display();
    }
    public function lists()
    {
        $page_now = I('post.p',1,'intval');
        $where = array(
            'status'=>array('gt',0),
            'uid'=>$this->uid
        );
        C('PAGE_SIZE',12);
        $data = self::$model->getMessageList($where,'id,title,send_time,msg,status',$page_now,'id desc');
        $this->assign('lists',$data['list']);
        $this->assign('page',$data['page']);
        $this->outHtml($this->fetch());
    }
    
    public function read()
    {
        $where = array(
            'id'=>(int)I('post.id'),
            'uid'=>$this->uid
        );
        if(self::$model->where($where)->setField('status',2)){
            $this->success();
        }else{
            $this->error();
        }
       
    }
}