<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// [ 应用入口文件 ]
//定义全局的访问路径
define('HTTP_URL','http://localhost/hiscmf');
define('ADMIN_URL',HTTP_URL.'/index.php/admin/');
//定义一个绝对路径
//define('ABS_PUBLIC_URL','/var/www/html/hiscmf/public/DataTables/');
define('ABS_PUBLIC_URL','G:\\wamp64\\www\\HisCMF\\public\\DataTables\\');

//绑定admin模块
//define('BIND_MODULE','admin');
// 定义应用目录
define('APP_PATH', __DIR__ . '/app/');
define("RUNTIME_PATH", __DIR__ .'/data/runtime/');

// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';
