<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace addons\admininfo;

use think\Addons;
use think\Db;

/**
 * 后台首页信息显示
 */
class Admininfo extends Addons
{
    public $info = [
        'name' => 'Admininfo',
        'title' => '各部门负责人名字',
        'description' => '后台首页各部门负责人名字',
        'status' => 1,
        'author' => 'liyuzhao',
        'version'=> '0.1',
        'admin'  => '0',//是否有管理页面
    ];

    /**
     * @var string 原数据库表前缀
     * 用于在导入插件sql时，将原有的表前缀转换成系统的表前缀
     * 一般插件自带sql文件时才需要配置
     */
    public $database_prefix = '';

    /**
     * @var array 插件钩子
     */
    public $hooks = [
        // 钩子名称 => 钩子说明
        'admininfo'=>'各部门负责人名字',
    ];

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    /**
     * 实现的admininfo钩子方法
     * @return mixed
     */
    public function admininfo()
    {
        $config=$this->getConfig();
		if($config['display']){
            $bumjl = Db::name('auth_group')->select();
            //$this->assign('bumjl',$bumjl);
            $admin = Db::name('admin')->select();
            $this->assign('arr', $bumjl);
            $this->assign('admin',$admin);
            return $this->fetch("admininfo");
		}
    }
}