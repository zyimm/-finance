<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Template\TagLib;

use Think\Template\TagLib;

/**
 * Html标签库驱动
 */
class Html extends TagLib
{
    // 标签定义
    protected $tags = array(
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'input' => array(
            'attr' => 'id,name,class,value,placeholder,style',
            'close' => 0
        ),
        'editor' => array(
            'attr' => 'id,name,style,width,height,type',
            'close' => 1
        ),
        'select' => array(
            'attr' => 'name,options,values,output,multiple,id,size,first,change,selected,dblclick',
            'close' => 0
        ),
        'grid' => array(
            'attr' => 'id,pk,style,action,actionlist,show,datasource',
            'close' => 0
        ),
        'list' => array(
            'attr' => 'id,pk,style,action,actionlist,show,datasource,checkbox',
            'close' => 0
        ),
        'imagebtn' => array(
            'attr' => 'id,name,value,type,style,click',
            'close' => 0
        ),
        'checkbox' => array(
            'attr' => 'name,checkboxes,checked,separator',
            'close' => 0
        ),
        'radio' => array(
            'attr' => 'name,radios,checked,separator',
            'close' => 0
        ),
        'date' => array(
            'attr' => 'id,name,class,value,format',
            'close' => 0
        ),
        'uploadImage' => array(
            'attr' => 'u_id,img_preview,img_upload',
            'close' => 0
        )
    );

    /**
     * input
     * 
     * @param array $attr            
     * @return string
     */
    public function _input($tag)
    {
        $id = ! empty($tag['id']) ? "id ={$tag['id']}" : ''; // name 和 id
        $name = $tag['name'];
        $value = !empty($tag['value']) ? $tag['value'] : ''; // 文本框值
        $placeholder = !empty($tag['placeholder']) ? $tag['placeholder'] : '请认真填写此项!'; // placeholder
        $tip = ! empty($tag['tip']) ? $tag['tip'] : ''; // span tip提示内容
        $class = $tag['class']; // class
        $style = $tag['style']; // 附加样式 style="widht:100"
        $type = ! empty($tag['type']) ? $tag['type'] : 'text';
        $readonly = ! empty($tag['readonly']) ? $tag['readonly'] : '';
        $required = ($tag['required'] == 'no') ? '' : "required='required'";
        $min = ! empty($tag['min']) ? $tag['min'] : 0;
        $max = ! empty($tag['max']) ? $tag['max'] : 9999999999999;
        $datatype=!empty($tag['datatype']) ? 'datatype='."'{$tag['datatype']}'" : ''; 
        $errormsg=!empty($tag['errormsg']) ? 'errormsg='.$tag['errormsg'] : '';
        $ajaxurl=!empty($tag['ajaxurl']) ? 'ajaxurl='.$tag['ajaxurl'] : '';
        $disabled = !empty($tag['disabled']) ? 'disabled=disabled': '';
        $parseStr = "";
        if ($style)
            $style = 'style="' . $style . '"';
            $parseStr = "<input name='{$name}' id='{$id}' {$datatype}  {$disabled} {$errormsg} {$ajaxurl}  $style class='{$class}' type='{$type}' value='{$value}'  {$required}   placeholder= '{$placeholder}'>";
        if ($type == 'number') {
            $parseStr = "<input name='{$name}' {$datatype} {$disabled} {$errormsg} {$ajaxurl} {$id} min={$min} max={$max}  $style class='{$class}' type='{$type}' value='{$value}'  {$required}   placeholder= '{$placeholder}'>";
        }
        if (! empty($readonly)) {
            $parseStr = "<input name='{$name}'  {$datatype} {$disabled} {$errormsg} {$ajaxurl} {$id}  $style class='{$class}' type='{$type}' value='{$value}' {$required}  readonly='{$readonly}'   required='required' placeholder= '{$placeholder}'>";
        }
        if ($tip) {
            $parseStr = $parseStr . "<span id='tip_{$id}' class='button bg-red float-left margin-left' >{$tip}</span>";
        }
        return $parseStr;
    }

    /**
     * editor标签解析 插入可视化编辑器 格式： <html:editor id="editor" name="remark" type="FCKeditor" style="" >{$vo.remark}</html:editor>
     *
     * @access public
     * @param array $tag
     *            标签属性
     * @return string|void
     */
    public function _editor($tag, $content)
    {
        $id = ! empty($tag['id']) ? $tag['id'] : '_editor';
        $name = $tag['name'];
        $style = ! empty($tag['style']) ? $tag['style'] : '';
        $width = ! empty($tag['width']) ? $tag['width'] : '100%';
        $height = ! empty($tag['height']) ? $tag['height'] : '320';
        $content = $tag['content'];
        $parseStr = "<!-- 加载编辑器的容器 -->
   					<script id='{$id}' name='{$name}' type='text/plain'>{$content}</script>
				    <!-- 配置文件 -->
				    <script type='text/javascript' src='__STATIC__Lib/UE/ueditor.config.js'></script>
				    <!-- 编辑器源码文件 -->
				    <script type='text/javascript' src='__STATIC__Lib/UE/ueditor.all.js'></script>
				    <!-- 实例化编辑器 -->
				    <script type='text/javascript'>
				        var ue = UE.getEditor('{$id}',{
				        	initialFrameWidth:'{$width}',
				        	initialFrameHeight:'{$height}',
    						serverUrl:'_STATIC__Lib/UE/php/controller.php'
    					});
				    </script>
        		<!-- 编辑器调用结束 -->";
        return $parseStr;
    }

    /**
     * imageBtn标签解析 格式： <html:imageBtn type="" value="" />
     * @access public
     * @param array $tag  标签属性
     * @return string|void
     */
    public function _imageBtn($tag)
    {
        $name = $tag['name']; // 名称
        $value = $tag['value']; // 文字
        $id = isset($tag['id']) ? $tag['id'] : ''; // ID
        $style = isset($tag['style']) ? $tag['style'] : ''; // 样式名
        $click = isset($tag['click']) ? $tag['click'] : ''; // 点击
        $type = empty($tag['type']) ? 'button' : $tag['type']; // 按钮类型
        
        if (! empty($name)) {
            $parseStr = '<div class="' . $style . '" ><input type="' . $type . '" id="' . $id . '" name="' . $name . '" value="' . $value . '" onclick="' . $click . '" class="' . $name . ' imgButton"></div>';
        } else {
            $parseStr = '<div class="' . $style . '" ><input type="' . $type . '" id="' . $id . '"  name="' . $name . '" value="' . $value . '" onclick="' . $click . '" class="button"></div>';
        }
        
        return $parseStr;
    }

    /**
     * imageLink标签解析 格式： <html:imageLink type="" value="" />
     *
     * @access public
     * @param array $tag     标签属性
     * @return string|void
     */
    public function _imgLink($tag)
    {
        $name = $tag['name']; // 名称
        $alt = $tag['alt']; // 文字
        $id = $tag['id']; // ID
        $style = $tag['style']; // 样式名
        $click = $tag['click']; // 点击
        $type = $tag['type']; // 点击
        if (empty($type)) {
            $type = 'button';
        }
        $parseStr = '<span class="' . $style . '" ><input title="' . $alt . '" type="' . $type . '" id="' . $id . '"  name="' . $name . '" onmouseover="this.style.filter=\'alpha(opacity=100)\'" onmouseout="this.style.filter=\'alpha(opacity=80)\'" onclick="' . $click . '" align="absmiddle" class="' . $name . ' imgLink"></span>';
        
        return $parseStr;
    }

    /**
     * select标签解析 格式： <html:select options="name" selected="value" />
     * @access public
     * @param array $tag    标签属性
     * @return string|void
     */
    public function _select($tag)
    {
        $name = $tag['name'];
        $options = ! empty($tag['options']) ? $tag['options'] : '';
        $values = ! empty($tag['values']) ? $tag['values'] : '';
        $output = ! empty($tag['output']) ? $tag['output'] : '';
        $multiple = ! empty($tag['multiple']) ? $tag['multiple'] : '';
        $id = ! empty($tag['id']) ? $tag['id'] : '';
        $size = ! empty($tag['size']) ? $tag['size'] : '';
        $first = ! empty($tag['first']) ? $tag['first'] : '';
        $selected = ! empty($tag['selected']) ? $tag['selected'] : '';
        $style = ! empty($tag['style']) ? $tag['style'] : '';
        $onchange = ! empty($tag['change']) ? $tag['change'] : '';
      
        if (!empty($multiple)) {
            $parseStr = "<select class='input display-inline-block' id='{$id}' name='{$name}'  onchange='{$onchange}' multiple='multiple' style='{$style}' size={ $size} >";
        } else {
            $parseStr = "<select  class='input display-inline-block' id='{$id}' name='{$name}'  onchange='{$onchange}'   style='{$style}'  >";
        }
        if (! empty($first)) {
            $parseStr .= "<option value='' >{$first}</option>";
        }
        if (! empty($options)) {
            $parseStr .= '<?php  foreach($' . $options . ' as $key=>$val) { ?>';
            if (! empty($selected)) {
                $parseStr .= '<?php if(!empty($' . $selected . ') && ($' . $selected . ' == $key || in_array($key,$' . $selected . '))) { ?>';
                $parseStr .= '<option selected="selected" value="<?php echo $key; ?>"><?php echo $val; ?></option>';
                $parseStr .= '<?php }else { ?><option value="<?php echo $key; ?>"><?php echo $val; ?></option>';
                $parseStr .= '<?php } ?>';
            } else {
                $parseStr .= '<option value="<?php echo $key ?>"><?php echo $val ?></option>';
            }
            $parseStr .= '<?php } ?>';
        }
        $parseStr .= '</select>';
        
        return $parseStr;
    }

    /**
     * checkbox标签解析 格式： <html:checkbox checkboxes="" checked="" />
     *
     * @access public
     * @param array $tag
     *            标签属性
     * @return string|void
     */
    public function _checkbox($tag)
    {
        $name = $tag['name'];
        $checkboxes = $tag['checkboxes'];
        $checked = $tag['checked'];
        $separator = $tag['separator'];
        $checkboxes = $this->tpl->get($checkboxes);
        $checked = $this->tpl->get($checked) ? $this->tpl->get($checked) : $checked;
        $parseStr = '';
        foreach ($checkboxes as $key => $val) {
            if ($checked == $key || in_array($key, $checked)) {
                $parseStr .= '<input type="checkbox" checked="checked" name="' . $name . '[]" value="' . $key . '">' . $val . $separator;
            } else {
                $parseStr .= '<input type="checkbox" name="' . $name . '[]" value="' . $key . '">' . $val . $separator;
            }
        }
        return $parseStr;
    }

    /**
     * radio标签解析 格式： <html:radio radios="name" checked="value" />
     *
     * @access public
     * @param array $tag
     *            标签属性
     * @return string|void
     */
    public function _radio($tag)
    {
        $name = $tag['name'];
        $radios = $tag['radios'];
        $checked = $tag['checked'];
        $separator = $tag['separator'];
        $radios = $this->tpl->get($radios);
        $checked = $this->tpl->get($checked) ? $this->tpl->get($checked) : $checked;
        $parseStr = '';
        foreach ($radios as $key => $val) {
            if ($checked == $key) {
                $parseStr .= '<input type="radio" checked="checked" name="' . $name . '[]" value="' . $key . '">' . $val . $separator;
            } else {
                $parseStr .= '<input type="radio" name="' . $name . '[]" value="' . $key . '">' . $val . $separator;
            }
        }
        return $parseStr;
    }

    /**
     * list标签解析 格式： <html:grid datasource="" show="vo" />
     * @access public
     * @param array $tag       标签属性
     * @return string
     */
    public function _grid($tag)
    {
        $id = $tag['id']; // 表格ID
        $datasource = $tag['datasource']; // 列表显示的数据源VoList名称
        $pk = empty($tag['pk']) ? 'id' : $tag['pk']; // 主键名，默认为id
        $style = $tag['style']; // 样式名
        $name = ! empty($tag['name']) ? $tag['name'] : 'vo'; // Vo对象名
        $action = ! empty($tag['action']) ? $tag['action'] : false; // 是否显示功能操作
        $key = ! empty($tag['key']) ? true : false;
        if (isset($tag['actionlist'])) {
            $actionlist = explode(',', trim($tag['actionlist'])); // 指定功能列表
        }
        
        if (substr($tag['show'], 0, 1) == '$') {
            $show = $this->tpl->get(substr($tag['show'], 1));
        } else {
            $show = $tag['show'];
        }
        $show = explode(',', $show); // 列表显示字段列表
                                    
        // 计算表格的列数
        $colNum = count($show);
        if (! empty($action))
            $colNum ++;
        if (! empty($key))
            $colNum ++;
            
            // 显示开始
        $parseStr = "<!-- Think 系统列表组件开始 -->\n";
        $parseStr .= '<table id="' . $id . '" class="' . $style . '" cellpadding=0 cellspacing=0 >';
        $parseStr .= '<tr><td height="5" colspan="' . $colNum . '" class="topTd" ></td></tr>';
        $parseStr .= '<tr class="row" >';
        // 列表需要显示的字段
        $fields = array();
        foreach ($show as $val) {
            $fields[] = explode(':', $val);
        }
        
        if (! empty($key)) {
            $parseStr .= '<th width="12">No</th>';
        }
        foreach ($fields as $field) { // 显示指定的字段
            $property = explode('|', $field[0]);
            $showname = explode('|', $field[1]);
            if (isset($showname[1])) {
                $parseStr .= '<th width="' . $showname[1] . '">';
            } else {
                $parseStr .= '<th>';
            }
            $parseStr .= $showname[0] . '</th>';
        }
        if (! empty($action)) { // 如果指定显示操作功能列
            $parseStr .= '<th >操作</th>';
        }
        $parseStr .= '</tr>';
        $parseStr .= '<volist name="' . $datasource . '" id="' . $name . '" ><tr class="row" >'; // 支持鼠标移动单元行颜色变化 具体方法在js中定义
        
        if (! empty($key)) {
            $parseStr .= '<td>{$i}</td>';
        }
        foreach ($fields as $field) {
            // 显示定义的列表字段
            $parseStr .= '<td>';
            if (! empty($field[2])) {
                // 支持列表字段链接功能 具体方法由JS函数实现
                $href = explode('|', $field[2]);
                if (count($href) > 1) {
                    // 指定链接传的字段值
                    // 支持多个字段传递
                    $array = explode('^', $href[1]);
                    if (count($array) > 1) {
                        foreach ($array as $a) {
                            $temp[] = '\'{$' . $name . '.' . $a . '|addslashes}\'';
                        }
                        $parseStr .= '<a href="javascript:' . $href[0] . '(' . implode(',', $temp) . ')">';
                    } else {
                        $parseStr .= '<a href="javascript:' . $href[0] . '(\'{$' . $name . '.' . $href[1] . '|addslashes}\')">';
                    }
                } else {
                    // 如果没有指定默认传编号值
                    $parseStr .= '<a href="javascript:' . $field[2] . '(\'{$' . $name . '.' . $pk . '|addslashes}\')">';
                }
            }
            if (strpos($field[0], '^')) {
                $property = explode('^', $field[0]);
                foreach ($property as $p) {
                    $unit = explode('|', $p);
                    if (count($unit) > 1) {
                        $parseStr .= '{$' . $name . '.' . $unit[0] . '|' . $unit[1] . '} ';
                    } else {
                        $parseStr .= '{$' . $name . '.' . $p . '} ';
                    }
                }
            } else {
                $property = explode('|', $field[0]);
                if (count($property) > 1) {
                    $parseStr .= '{$' . $name . '.' . $property[0] . '|' . $property[1] . '}';
                } else {
                    $parseStr .= '{$' . $name . '.' . $field[0] . '}';
                }
            }
            if (! empty($field[2])) {
                $parseStr .= '</a>';
            }
            $parseStr .= '</td>';
        }
        if (! empty($action)) { // 显示功能操作
            if (! empty($actionlist[0])) { // 显示指定的功能项
                $parseStr .= '<td>';
                foreach ($actionlist as $val) {
                    if (strpos($val, ':')) {
                        $a = explode(':', $val);
                        if (count($a) > 2) {
                            $parseStr .= '<a href="javascript:' . $a[0] . '(\'{$' . $name . '.' . $a[2] . '}\')">' . $a[1] . '</a>&nbsp;';
                        } else {
                            $parseStr .= '<a href="javascript:' . $a[0] . '(\'{$' . $name . '.' . $pk . '}\')">' . $a[1] . '</a>&nbsp;';
                        }
                    } else {
                        $array = explode('|', $val);
                        if (count($array) > 2) {
                            $parseStr .= ' <a href="javascript:' . $array[1] . '(\'{$' . $name . '.' . $array[0] . '}\')">' . $array[2] . '</a>&nbsp;';
                        } else {
                            $parseStr .= ' {$' . $name . '.' . $val . '}&nbsp;';
                        }
                    }
                }
                $parseStr .= '</td>';
            }
        }
        $parseStr .= '</tr></volist><tr><td height="5" colspan="' . $colNum . '" class="bottomTd"></td></tr></table>';
        $parseStr .= "\n<!-- Think 系统列表组件结束 -->\n";
        return $parseStr;
    }

    /**
     * list标签解析 格式： <html:list datasource="" show="" />
     *
     * @access public
     * @param array $tag
     *            标签属性
     * @return string
     */
    public function _list($tag)
    {
        $id = $tag['id']; // 表格ID
        $datasource = $tag['datasource']; // 列表显示的数据源VoList名称
        $pk = empty($tag['pk']) ? 'id' : $tag['pk']; // 主键名，默认为id
        $style = $tag['style']; // 样式名
        $name = ! empty($tag['name']) ? $tag['name'] : 'vo'; // Vo对象名
        $action = $tag['action'] == 'true' ? true : false; // 是否显示功能操作
        $key = ! empty($tag['key']) ? true : false;
        $sort = $tag['sort'] == 'false' ? false : true;
        $checkbox = $tag['checkbox']; // 是否显示Checkbox
        if (isset($tag['actionlist'])) {
            if (substr($tag['actionlist'], 0, 1) == '$') {
                $actionlist = $this->tpl->get(substr($tag['actionlist'], 1));
            } else {
                $actionlist = $tag['actionlist'];
            }
            $actionlist = explode(',', trim($actionlist)); // 指定功能列表
        }
        
        if (substr($tag['show'], 0, 1) == '$') {
            $show = $this->tpl->get(substr($tag['show'], 1));
        } else {
            $show = $tag['show'];
        }
        $show = explode(',', $show); // 列表显示字段列表
                                    
        // 计算表格的列数
        $colNum = count($show);
        if (! empty($checkbox))
            $colNum ++;
        if (! empty($action))
            $colNum ++;
        if (! empty($key))
            $colNum ++;
            
            // 显示开始
        $parseStr = "<!-- Think 系统列表组件开始 -->\n";
        $parseStr .= '<table id="' . $id . '" class="' . $style . '" cellpadding=0 cellspacing=0 >';
        $parseStr .= '<tr><td height="5" colspan="' . $colNum . '" class="topTd" ></td></tr>';
        $parseStr .= '<tr class="row" >';
        // 列表需要显示的字段
        $fields = array();
        foreach ($show as $val) {
            $fields[] = explode(':', $val);
        }
        if (! empty($checkbox) && 'true' == strtolower($checkbox)) { // 如果指定需要显示checkbox列
            $parseStr .= '<th width="8"><input type="checkbox" id="check" onclick="CheckAll(\'' . $id . '\')"></th>';
        }
        if (! empty($key)) {
            $parseStr .= '<th width="12">No</th>';
        }
        foreach ($fields as $field) { // 显示指定的字段
            $property = explode('|', $field[0]);
            $showname = explode('|', $field[1]);
            if (isset($showname[1])) {
                $parseStr .= '<th width="' . $showname[1] . '">';
            } else {
                $parseStr .= '<th>';
            }
            $showname[2] = isset($showname[2]) ? $showname[2] : $showname[0];
            if ($sort) {
                $parseStr .= '<a href="javascript:sortBy(\'' . $property[0] . '\',\'{$sort}\',\'' . ACTION_NAME . '\')" title="按照' . $showname[2] . '{$sortType} ">' . $showname[0] . '<eq name="order" value="' . $property[0] . '" ><img src="__PUBLIC__/images/{$sortImg}.gif" width="12" height="17" border="0" align="absmiddle"></eq></a></th>';
            } else {
                $parseStr .= $showname[0] . '</th>';
            }
        }
        if (! empty($action)) { // 如果指定显示操作功能列
            $parseStr .= '<th >操作</th>';
        }
        
        $parseStr .= '</tr>';
        $parseStr .= '<volist name="' . $datasource . '" id="' . $name . '" ><tr class="row" '; // 支持鼠标移动单元行颜色变化 具体方法在js中定义
        if (! empty($checkbox)) {
            // $parseStr .= 'onmouseover="over(event)" onmouseout="out(event)" onclick="change(event)" ';
        }
        $parseStr .= '>';
        if (! empty($checkbox)) { // 如果需要显示checkbox 则在每行开头显示checkbox
            $parseStr .= '<td><input type="checkbox" name="key"	value="{$' . $name . '.' . $pk . '}"></td>';
        }
        if (! empty($key)) {
            $parseStr .= '<td>{$i}</td>';
        }
        foreach ($fields as $field) {
            // 显示定义的列表字段
            $parseStr .= '<td>';
            if (! empty($field[2])) {
                // 支持列表字段链接功能 具体方法由JS函数实现
                $href = explode('|', $field[2]);
                if (count($href) > 1) {
                    // 指定链接传的字段值
                    // 支持多个字段传递
                    $array = explode('^', $href[1]);
                    if (count($array) > 1) {
                        foreach ($array as $a) {
                            $temp[] = '\'{$' . $name . '.' . $a . '|addslashes}\'';
                        }
                        $parseStr .= '<a href="javascript:' . $href[0] . '(' . implode(',', $temp) . ')">';
                    } else {
                        $parseStr .= '<a href="javascript:' . $href[0] . '(\'{$' . $name . '.' . $href[1] . '|addslashes}\')">';
                    }
                } else {
                    // 如果没有指定默认传编号值
                    $parseStr .= '<a href="javascript:' . $field[2] . '(\'{$' . $name . '.' . $pk . '|addslashes}\')">';
                }
            }
            if (strpos($field[0], '^')) {
                $property = explode('^', $field[0]);
                foreach ($property as $p) {
                    $unit = explode('|', $p);
                    if (count($unit) > 1) {
                        $parseStr .= '{$' . $name . '.' . $unit[0] . '|' . $unit[1] . '} ';
                    } else {
                        $parseStr .= '{$' . $name . '.' . $p . '} ';
                    }
                }
            } else {
                $property = explode('|', $field[0]);
                if (count($property) > 1) {
                    $parseStr .= '{$' . $name . '.' . $property[0] . '|' . $property[1] . '}';
                } else {
                    $parseStr .= '{$' . $name . '.' . $field[0] . '}';
                }
            }
            if (! empty($field[2])) {
                $parseStr .= '</a>';
            }
            $parseStr .= '</td>';
        }
        if (! empty($action)) { // 显示功能操作
            if (! empty($actionlist[0])) { // 显示指定的功能项
                $parseStr .= '<td>';
                foreach ($actionlist as $val) {
                    if (strpos($val, ':')) {
                        $a = explode(':', $val);
                        if (count($a) > 2) {
                            $parseStr .= '<a href="javascript:' . $a[0] . '(\'{$' . $name . '.' . $a[2] . '}\')">' . $a[1] . '</a>&nbsp;';
                        } else {
                            $parseStr .= '<a href="javascript:' . $a[0] . '(\'{$' . $name . '.' . $pk . '}\')">' . $a[1] . '</a>&nbsp;';
                        }
                    } else {
                        $array = explode('|', $val);
                        if (count($array) > 2) {
                            $parseStr .= ' <a href="javascript:' . $array[1] . '(\'{$' . $name . '.' . $array[0] . '}\')">' . $array[2] . '</a>&nbsp;';
                        } else {
                            $parseStr .= ' {$' . $name . '.' . $val . '}&nbsp;';
                        }
                    }
                }
                $parseStr .= '</td>';
            }
        }
        $parseStr .= '</tr></volist><tr><td height="5" colspan="' . $colNum . '" class="bottomTd"></td></tr></table>';
        $parseStr .= "\n<!-- Think 系统列表组件结束 -->\n";
        return $parseStr;
    }

    /**
     *
     * @param array $tag            
     */
    public function _date($tag)
    {
        $id = ! empty($tag['id']) ? $tag['id'] : '_date';
        $name = $tag['name'];
        $value = $tag['value'] ? $tag['value'] : ''; // 文本框值
        $class = $tag['class'] ? " " . $tag['class'] : '';
        $format = ! empty($tag['format']) ? $tag['format'] : 'YYYY-MM-DD';
        $max = ! empty($tag['max']) ? "laydate.now({$tag['max']})" : 'laydate.now()';
        $style = ! empty($tag['style']) ? "style='{$tag['style']}'" : '';
        $required = ($tag['required'] == 'no') ? '' : "required='required'";
        $placeholder = ! empty($tag['placeholder']) ? $tag['placeholder'] : ''; // placeholder
        $parseStr = "<input name='{$name}' id='{$id}'  {$style}  class='{$class} laydate-icon'  onclick=\"laydate({istime: true, max:{$max},format: '{$format}'})\"  type='text' value='{$value}' {$required} placeholder= '{$placeholder}'>";
        return $parseStr;
    }

    /**
     *
     * @param array $tag            
     * @return string
     */
    public function _uploadImage($tag)
    {
        $ue_id = ! empty($tag['ue_id']) ? $tag['ue_id'] : '_editor';
        $img_preview = ! empty($tag['img_preview']) ? $tag['img_preview'] : '';
        $img_upload = ! empty($tag['img_upload']) ? $tag['img_upload'] : '';
        $img_nums = ! empty($tag['img_nums']) ? (int)$tag['img_nums'] : 0;
        $parseStr = <<<EOC
    			<script id="{$ue_id}"></script>
    			<script> 
    			    var imageLength =  {$img_nums};
	    			$(function() {
					   //重新实例化一个编辑器，防止在上面的editor编辑器中显示上传的图片或者文件
					    _e = UE.getEditor('{$ue_id}', {
					        //serverUrl :'/Home/Index/ueditor.html',
					     });
					    _e.ready(function() {
					        //设置编辑器不可用
					        _e.setDisabled('insertimage');
					        //隐藏编辑器，因为不会用到这个编辑器实例，所以要隐藏
					        _e.hide();
					        //侦听图片上传
					        _e.addListener('beforeInsertImage', function(t,arg) {
					            if(imageLength){
					               var n = imageLength;
                                }else{
                                   var n=arg.length;
                                }
					            var n=arg.length;
					            var str='';
					            for(i=0;i<n;i++){
					              str+="<div style='width:120px;float:left;margin:8px;position:relative;'><i class='icon-times-circle text-dot' onclick='delImage(this)' style='cursor:pointer;position:absolute;top:-6px;right:28px;'></i><input type='hidden' value="+arg[i].src+" name='img_url[]' /><img src="+arg[i].src+" alt=''  class='radius-big' width='88' height='88' /></div>";
					            }
					           
					            $('#{$img_preview}').append(str);
					        })
					    });
	    				$('#{$img_upload}').click(function(){
					      _e.getDialog("insertimage").open();
					    });
					});
    			</script>
EOC;
        return $parseStr;
    }
}