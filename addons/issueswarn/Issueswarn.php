<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace addons\issueswarn;

use think\Addons;
use think\Db;

/**
 * 后台首页信息显示
 */
class Issueswarn extends Addons
{
    public $info = [
        'name' => 'Issueswarn',
        'title' => '问题靠近日期提醒',
        'description' => '后台首页问题靠近日期提醒',
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
        'issueswarn'=>'问题靠近日期提醒',
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
     * 实现的userinfo钩子方法
     * @return mixed
     */
    public function issueswarn()
    {
        $config=$this->getConfig();
		if($config['display']){
            if(session('admin_auth.aid')){
                //显示任务类型
                $rwlx_list = DB::name('dl_erjidaima')->where('youxbz',1)->where('fenlbm','优先级')->select();
                $this->assign('rwlx_list',$rwlx_list);
                $date_new = date('Y-m-d H:i:s',strtotime("+3 day")); //得到未来三天的日期
                //查询出靠近完成时间的问题
                $bug_list = Db::name('dl_buglist')->where('rw_state','<>',26)->where('yaoqwcsj','<',$date_new)->where('jiedfzr',session('admin_auth.aid'))->order('youxjb asc')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
            }

            $show = $bug_list->render();
            $this->assign('page',$show);
            $this->assign('bug_list',$bug_list);
            return $this->fetch('issueswarn');
		}
    }
}