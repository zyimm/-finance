<?php
namespace Common\Service;

class WhereBuilder
{
    
   
    public static function build($field = array())
    {
        $where = array();
        if(!empty($field) && is_array($field)){
            foreach ($field as $k=>$v){
                if(empty($v['value'])){
                    continue;
                }
                if (! empty($v['alias'])) {
                    if(stripos('|', $k)){
                        $_temp = explode('|',$k);
                        $_k =  array();
                        foreach ($_temp as $_v){
                            $_k[]= $v['alias'].array_shift($_temp);
                        }
                        $k = join('|', $_k[]);
                    }else{
                        $k = $v['alias'] . '.' . $k;
                    }   
                }
                $where[$k] = array(
                    strtolower($v['condition']),$v['value']
                );
                
                
            }
            return $where;
        }else{      
            return false;
            
        }
    } 
    /**
     * @example
      ```
        $field = [
                'id'=>[
                    'name'=>'订单号',
                    'type'=>'input',
                    'tip'=>'',
                    'value'=>'121'
                ],
                
            ];
     ```
     * @param array $field
     * @return string
     */
    public static  function buidFrom($field = array())
    {
        
        $html = '';
        if (! empty($field) && is_array($field)) {
            foreach ($field as $k => $v) {
                switch ($v['type']) {
                    case 'input':
                        $html .= "<tr><td>{$v['name']}:</td><td><input type='text' name='{$k}' class='input' placeholder='{$v['tip']}' value='{$v['value']}' /></td></tr>";
                        break;
                    case 'date':
                        $html .= "<tr><td>{$v['name']}:</td><td><input type='text' name='{$k}' class='input'  value='{$v['value']}' placeholder='{$v['tip']}' onclick=\" layui.laydate({elem:this,istime: true, format: 'YYYY-MM-DD'}) \"/></td></tr>";
                        break;
                }
            }
        }
        if(!empty($html)){
            $html.= "<tr><td colspan = 2> <input value='搜索' class=' button bg-blue' type='submit'></td></tr>";
            
        }
        return $html;
    }
}
