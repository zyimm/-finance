<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------
namespace Think;

class Page{
    public $firstRow; // 起始行数
    public $listRows; // 列表每页显示行数
    public $parameter; // 分页跳转时要带的参数
    public $totalRows; // 总行数
    public $totalPages; // 分页总页面数
    public $rollPage   = 8;// 分页栏每页显示的页数
	public $lastSuffix = false; // 最后一页是否显示总页数

    private $p       = 'p'; //分页参数名
    private $url     = ''; //当前链接URL
    private $nowPage = 1;

	// 分页显示定制
    private $config  = array(
        'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>&nbsp;,',
        'now'    => '<span class="now">当前第<b>%NOW_PAGE%</b>页记录</span>&nbsp;',
        'prev'   => '上一页',
        'next'   => '下一页',
        'first'  => '1...',
        'last'   => '最后一页',
        'theme'  => '%HEADER%%NOW_PAGE%%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
    );

    /**
     * 架构函数
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     */
    public function __construct($totalRows, $listRows=20, $parameter = array()) {
        C('VAR_PAGE') && $this->p = C('VAR_PAGE'); //设置分页参数名称
        /* 基础设置 */
        $this->totalRows  = $totalRows; //设置总记录数
        $this->listRows   = $listRows;  //设置每页显示行数
        $this->parameter  = empty($parameter) ? $this->_filter(I('get.')): $parameter;
        $this->nowPage    = empty($_REQUEST[$this->p]) ? 1 : intval($_REQUEST[$this->p]);
        $this->nowPage    = $this->nowPage>0 ? $this->nowPage : 1;
        $this->firstRow   = $this->listRows * ($this->nowPage - 1);
        
    }
    private function _filter($param)
    {
        $filter = array('m','c','a');
        $_param = array();
        $_param = $param;
        foreach ($filter as $v){
            if(isset($_param[$v])){
                unset($_param[$v]);
            }
        }
        return $_param;
    }
    /**
     * 定制分页链接设置
     * @param string $name  设置名称
     * @param string $value 设置值
     */
    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 生成链接URL
     * @param  integer $page 页码
     * @return string
     */
    private function url($page){
        return str_replace(urlencode('[PAGE]'), $page, $this->url);
    }
    
   
    private function buildUrl($url = '',$param = array())
    {
        if(!empty($url)){
            return $url.'?'.http_build_query($param);
        }else{
            return false;
        }
        
    }
    /**
     * 组装分页链接
     * @return string
     */
    public function show() {
        if(0 == $this->totalRows){
            return '';
        }
        /* 生成URL */
        $this->parameter[$this->p] = '[PAGE]';
        $this->url = $this->buildUrl(U(__ACTION__), $this->parameter);
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        /* 计算分页临时变量 */
        $now_cool_page      = $this->rollPage/2;
		$now_cool_page_ceil = ceil($now_cool_page);
		$this->lastSuffix && $this->config['last'] = $this->totalPages;

        //上一页
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<a class="prev" href="' . $this->url($up_row) . '">' . $this->config['prev'] . '</a>' : '';

        //下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<a class="next" href="' . $this->url($down_row) . '">' . $this->config['next'] . '</a>' : '';

        //第一页
        $the_first = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1){
            $the_first = '<a class="first" href="' . $this->url(1) . '">' . $this->config['first'] . '</a>';
        }

        //最后一页
        $the_end = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages){
            $the_end = '<a class="end" href="' . $this->url($this->totalPages) . '">' . $this->config['last'] . '</a>';
        }
        //数字连接
        $link_page = "";
        for($i = 1; $i <= $this->rollPage; $i++){
			if(($this->nowPage - $now_cool_page) <= 0 ){
				$page = $i;
			}elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
				$page = $this->totalPages - $this->rollPage + $i;
			}else{
				$page = $this->nowPage - $now_cool_page_ceil + $i;
			}
            if($page > 0 && $page != $this->nowPage){

                if($page <= $this->totalPages){
                    $link_page .= '<a class="num" href="' . $this->url($page) . '">' . $page . '</a>';
                }else{
                    break;
                }
            }else{
                if($page > 0 && $this->totalPages != 1){
                    $link_page .= '<span class="current">' . $page . '</span>';
                }
            }
        }
        //替换分页内容
        return  $this->outPage($up_page,$down_page,$the_first, $link_page, $the_end);
    }
    /**
     * ajax 分页
     * @return string
     * @author 周阳阳 2017年3月14日 下午5:06:09
     */
    public function ajaxShow()
    {
        if(0 == $this->totalRows) return '';
        $this->parameter['url'] = __ACTION__;
        /* 生成URL */
        $this->parameter = json_encode($this->parameter);
        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }
        
        /* 计算分页临时变量 */
        $now_cool_page      = $this->rollPage/2;
        $now_cool_page_ceil = ceil($now_cool_page);
        $this->lastSuffix && $this->config['last'] = $this->totalPages;
        
        //上一页
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? "<a class='prev' href='javascript:ZYY.ajaxPage({$up_row},{$this->parameter})'>{$this->config['prev']}</a>" : '';
        
        //下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? "<a class='next' href='javascript:ZYY.ajaxPage({$down_row},{$this->parameter})'>{$this->config['next'] }</a>": '';
        
        //第一页
        $the_first = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1){
            $the_first = "<a class='first' href='javascript:ZYY.ajaxPage(1,{$this->parameter}) '>{$this->config['first']}</a>";
        }
        
        //最后一页
        $the_end = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages){
            $the_end = "<a class='end' href='javascript:ZYY.ajaxPage({$this->totalPages},{$this->parameter}) '>{$this->config['last']} </a>";
        }
        
        //数字连接
        $link_page = "";
        for($i = 1; $i <= $this->rollPage; $i++){
            if(($this->nowPage - $now_cool_page) <= 0 ){
                $page = $i;
            }elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
                $page = $this->totalPages - $this->rollPage + $i;
            }else{
                $page = $this->nowPage - $now_cool_page_ceil + $i;
            }
            if($page > 0 && $page != $this->nowPage){
        
                if($page <= $this->totalPages){
                    $link_page .= "<a class='num' href='javascript:ZYY.ajaxPage({$page},{$this->parameter}) '>{$page}</a>";
                }else{
                    break;
                }
            }else{
                if($page > 0 && $this->totalPages != 1){
                    $link_page .= '<span class="current">' . $page . '</span>';
                }
            }
        }
        //替换分页内容
        return  $this->outPage($up_page,$down_page,$the_first, $link_page, $the_end);
       
    }

    private function outPage($up_page, $down_page, $the_first, $link_page, $the_end)
    {
        $this->config['now'] = str_replace('%NOW_PAGE%',$this->nowPage,$this->config['now']);
        $page_str = str_replace(array(
            '%HEADER%',
            '%NOW_PAGE%',
            '%UP_PAGE%',
            '%DOWN_PAGE%',
            '%FIRST%',
            '%LINK_PAGE%',
            '%END%',
            '%TOTAL_ROW%',
            '%TOTAL_PAGE%'
        ), array(
            $this->config['header'],
            $this->config['now'],
            $up_page,
            $down_page,
            $the_first,
            $link_page,
            $the_end,
            $this->totalRows,
            $this->totalPages
        ), $this->config['theme']);
        return "<div class='page'>{$page_str}</div>";
    }
}
