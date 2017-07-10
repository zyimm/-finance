<?php
namespace Backstage\Model;


use Front\Model\ConfigModel;

class ExtendModel
{
    public function  parameter($data = [])
    {
        foreach ($data as $v){
            if(empty($v)){
                break;
                return '存在空值';
            }
            
        }
        $bank = [];
        
        $bank['BANK_NAME'] = $data['BANK_NAME'];
        
        array_pop($data);
        $data = array_merge(ConfigModel::read('borrow_config'),$data);
        $data = "<?php return ".var_export($data,true).";";
   
        if(!ConfigModel::write('borrow_config',$data)){
            return '文件写入失败-1';
            
        }
        
        $bank = "<?php return ".var_export($bank,true).";";
        
        if(!ConfigModel::write('bank',$bank)){
            return '文件写入失败-2';
        
        }

        return true;
    }
}