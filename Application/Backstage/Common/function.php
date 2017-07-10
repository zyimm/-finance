<?php
/**
 * 面包屑导航1/2
 *
 * @param number $menu_id            
 * @return string
 */
function crumbs_menu($menu_id = 0, $module = 'backstage_menu')
{
    if (empty($menu_id)){
        return false;
    }
    
    $crumbs = '';
    $menu_info = M($module)->where([
        'status' => 1,
        'id' => $menu_id
    ])->find();
    
    if (! empty($menu_info['parent_id'])) {
        $crumbs .= crumbs_menu($menu_info['parent_id'], $module);
    }
    $crumbs .= "<li><a href='#'>{$menu_info['name']}</a> </li>";
    
    return $crumbs;
}

/**
 * 面包屑导航2/2
 *
 * @param number $menu_id            
 * @param string $tag  当前操作的标识
 * @return string
 */
function crumbs($menu_id = 0, $tag = '内容', $module = 'backstage_menu')
{
    $crumbs = "<ul class='bread'><li><a href='/Backstage/index/welcome.html' class='icon-home'>首页</a> </li>" . crumbs_menu($menu_id, $module) . "<li>{$tag}</li> </ul>";
    return $crumbs;
}