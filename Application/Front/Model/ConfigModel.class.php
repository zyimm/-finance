<?php
namespace Front\Model;

class ConfigModel
{
    /**
     * 读取配置文件
     * @param string $type
     * @return boolean
     */
    public static  function read($type = '')
    { 
        $config_path = ROOT_PATH."/Config/";
        if(empty($type)){ 
            return false;
        }
        if(file_exists($config_path.$type.'.php')){
            return  require ($config_path.$type.'.php');
        }else{
            return false;
        }  
    }
    
    public static  function write($file_name = '',$data = '')
    {
        if(empty($file_name)){
            
            return false;
        }
        $config_path = ROOT_PATH."/Config/";
        if(empty($data)){
            return false;
        }
        if(!file_put_contents($config_path.$file_name.'.php', $data)){
            return false;
            
        }else{
            return true;
        }
    }
}