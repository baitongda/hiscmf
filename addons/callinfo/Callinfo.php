<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace addons\callinfo;

use think\Addons;
use think\Db;

/**
 * 后台首页信息显示
 */
class Callinfo extends Addons
{
    public $info = [
        'name' => 'Callinfo',
        'title' => '任务提示',
        'description' => '后台首页任务提示',
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
        'callinfo'=>'任务提示',
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
    public function callinfo()
    {
        $config=$this->getConfig();
		if($config['display']){
            //获取我的任务个数
            $map = [
                'shbz'      =>  '1',
                'rw_state'  =>  '0',
                'jiedfzr'   =>  session('admin_auth.aid'),
            ];
            $task_sum = Db::name('dl_buglist')->where($map)->count();
            $this->assign('task_sum',$task_sum);
            //获取已接收任务个数
            $map['zhuangt'] = 12; //已接收代码
            $js_task = Db::name('dl_buglist')->where($map)->count();
            $this->assign('js_task',$js_task);
            $map['zhuangt'] = 11; //未接收代码
            $wjs_task = Db::name('dl_buglist')->where($map)->count();
            $this->assign('wjs_task',$wjs_task);
            //获取当前用户发布的任务个数（不包含未关闭的）
            $wfb_task = Db::name('dl_buglist')->where('fabry',session('admin_auth.aid'))->where('rw_state','<>','26')->count();
            $this->assign('wfb_task',$wfb_task);
            //获取当前用户发布的总任务个数
            $wfb_task_all = Db::name('dl_buglist')->where('fabry',session('admin_auth.aid'))->count();
            $this->assign('wfb_task_all',$wfb_task_all);
            //获取我未审核的任务个数
            $wwsh = Db::name('dl_buglist')->where('jiedfzr',session('admin_auth.aid'))->where('shbz',0)->count();
            $this->assign('wwsh_task',$wwsh);
			return $this->fetch('callinfo');
		}
    }
}