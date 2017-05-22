<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace addons\userinfo;

use think\Addons;
use think\Db;

/**
 * 后台首页信息显示
 */
class Userinfo extends Addons
{
    public $info = [
        'name' => 'Userinfo',
        'title' => '用户登录信息',
        'description' => '后台用户登录信息',
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
        'userinfo'=>'用户登录信息',
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
     * 实现的callinfo钩子方法
     * @return mixed
     */
    public function userinfo()
    {
        $config=$this->getConfig();
		if($config['display']){
            //获取登录用户信息
            $admin_info = Db::name('admin')->where('admin_id',session('admin_auth.aid'))->find();
            $this->assign('admin_info',$admin_info);
            //获取用户属于哪个部门
            $id = Db::name('auth_group_access')->where('uid',session('admin_auth.aid'))->value('group_id');
            $bm = Db::name('auth_group')->where('id',$id)->value('title');
            $this->assign('bm',$bm);
            return $this->fetch('userinfo');
		}
    }
}