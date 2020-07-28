<?php
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
// 定义应用目录
define('APP_PATH','./Application/');
define('ROOT_PATH',dirname(__FILE__));
// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',1);
require './ThinkPHP/ThinkPHP.php';
