<?php
namespace Member\Model;
use Think\Model;

class SignModel extends Model
{
    protected $tableName = 'members';
    
    public function reg($reg_data = [])
    {
        if(!empty($reg_data)){
            $result = $this->checkReg($reg_data);
            if($result === true){
                $result = $this->addMember($reg_data);
                
            }else{
                return $result;
            }
            
        }else{
            return false;
        }
    }
    
    public function checkReg($reg_data = [])
    {
        if(!empty($reg_data)){
            
            
        }else{
            return false;
        }
    }
    
    public function addMember($data = [])
    {
        if(!empty($data)){
            $this->startTrans();
            
            $result = $this->initializationMember($id);
            if($result === true){
                $this->commit();
                return true;
            }else{
                $this->rollback();
                return $result;
            }
        }else{
            return false;
            
        }
    }
    
    
    private function initializationMember($id = 0)
    {
        $data = array('uid'=>$id);
        $result = [];
        $result[]= M('member_banks')->add($data);
        $result[]= M('member_info')->add($data);
        $result[]= M('member_status')->add($data);
        foreach ($result as $v){
            if(empty($v)){
                return false;
                break;
            }
            
        }
        
        return true;
    }
}