<?php
namespace Member\Controller;

use Member\Model\InviteModel;

class InviteController extends BaseController
{
    public function index()
    {
        $this->display();
    }

    public function xxx()
    {
        $_P_fee = get_global_setting();
        $this->assign("reward", $_P_fee);
        $data['html'] = $this->fetch();
        exit(json_encode($data));
    }

    public function log()
    {
        $model = new InviteModel();
        $data = $model->log($this->uid);
        $this->assign("total",$data['total']);
        $this->assign("CR", M('members')->getFieldById($this->uid, 'reward_money'));
        $this->assign("list", $data['list']['list']);
        $this->assign("page", $data['list']['page']);
        
        $data['html'] = $this->fetch();
        exit(json_encode($data));
    }

    public function friends()
    {
        /* $model = new InviteModel();
        $data =$model->friends($this->uid);
        $this->assign("vm", $data['vm']);
        $this->assign("vi", $data['vi']);
        $data['html'] = $this->fetch();
        exit(json_encode($data)); */
        $this->outHtml($this->fetch());
    }
    
    public function rewardLog()
    {
        $this->outHtml($this->fetch());
    }
    
    public function popularize()
    {
        $this->outHtml($this->fetch());
    }
}