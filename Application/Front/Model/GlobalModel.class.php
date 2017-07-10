<?php
namespace Front\Model;


use Think\Model;

class GlobalModel extends Model
{
    public static  function getGlobalSetting()
    {
        $list=array();
        if(!S('global_setting')){
            $list_t = M('global')->field('code,text')->select();
            foreach($list_t as $key => $v){
                $list[$v['code']] = SafeModel::removeSlash($v['text']);
            }
            S('global_setting',$list,C('CACHE_TIME'));
        }else{
            $list = S('global_setting');
        }
        
        return $list;
    }
    
    public static function log()
    {
        
    }
}