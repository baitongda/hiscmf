<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Common;
use app\admin\model\AuthRule;
use think\Db;

class Base extends Common
{
    public function _initialize()
    {
        parent::_initialize();
        if(!$this->check_admin_login()) $this->redirect('admin/Login/login');//未登录
        $auth=new AuthRule;
        $id_curr=$auth->get_url_id();
        if(!$auth->check_auth($id_curr)) $this->error('没有权限',url('admin/Index/index'));
        //获取有权限的菜单tree
        $menus=$auth->get_admin_menus();
        $this->assign('menus',$menus);
        //当前方法倒推到顶级菜单ids数组
        $menus_curr=$auth->get_admin_parents($id_curr);
        $this->assign('menus_curr',$menus_curr);
        //取当前操作菜单父节点下菜单 当前菜单id(仅显示状态)
        $menus_child=$auth->get_admin_parent_menus($id_curr);
        $this->assign('menus_child',$menus_child);
        $this->assign('id_curr',$id_curr);
        $this->assign('admin_avatar',session('admin_auth.admin_avatar'));
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样

        /*
         * 代码重用优化，把代码重复的放到这里来
         * */
        //显示状态码
        $ztlist = Db::name('dl_erjidaima')->where('youxbz',1)->select();
        $this->assign('ztlist',$ztlist);
        //查询所有的用户
        $admin_list = Db::name('admin')->order('admin_id asc')->select();
        $this->assign('admin_list',$admin_list);
        //显示所有的产品
        $cp_list = Db::name('dl_chanpin')->select();
        $this->assign('cp_list',$cp_list);
        //显示所有的项目
        $cp_list = Db::name('dl_xiangmu')->select();
        $this->assign('xm_list',$cp_list);

        //显示任务类型
        $rwlx_list = DB::name('dl_erjidaima')->where('youxbz',1)->where('fenlbm','任务类型')->select();
        $this->assign('rwlx_list',$rwlx_list);
        //显示状态
        $yxj_list = DB::name('dl_erjidaima')->where('youxbz',1)->where('fenlbm','优先级')->select();
        $this->assign('yxj_list',$yxj_list);
        //显示状态标志
        $ztbz_list = DB::name('dl_erjidaima')->where('youxbz',1)->where('fenlbm','状态标志')->where('youxbz',1)->select();
        $this->assign('ztbz_list',$ztbz_list);
        //子任务类型
        $zirwlx = DB::name('dl_erjidaima')->where('youxbz',1)->where('fenlbm','子任务类型')->where('youxbz',1)->select();
        $this->assign('zirwlx',$zirwlx);
        //子任务标志
        $zirwbz = DB::name('dl_erjidaima')->where('youxbz',1)->where('fenlbm','子任务标志')->where('youxbz',1)->select();
        $this->assign('zirwbz',$zirwbz);
        //显示角色、也就是要部门查询而已，下属周报那个查询要用到
        $bum = Db::name('auth_group')->select();
        $this->assign('bum',$bum);
        //保存当前的session aid
        $this->assign('admin_id',session('admin_auth.aid'));
    }
}