<?php
/**
 * Created by PhpStorm.
 * User: liyuzhao
 * Date: 2017/3/3
 * Time: 14:46
 */
namespace app\admin\controller;
use think\Db;
use think\Cache;

class Basedata extends Base
{
    /*-------------------部门功能开始-----------------------*/
    /*
     * 显示部门数据
     * */
    public function bm_list()
    {
        $admin_list = DB::name('admin')->select(); //显示出经理要用
        $this->assign('admin_list',$admin_list);
        //查询出部门的数据
        if(empty($_POST)){
            $bmlist=Db::name('dl_bumen')->order('bumid')->paginate(config('paginate.list_rows'),false);
            $page = $bmlist->render();
            $this->assign('bmlist',$bmlist);
            $this->assign('page',$page);
        }else{
            $search_name=input('search_name');
            $this->assign('search_name',$search_name);
            $map=array();
            if($search_name){
                $map['bummc']= array('like',"%".$search_name."%");
            }
            $bmlist=Db::name('dl_bumen')->where($map)->order('bumid')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
            $page = $bmlist->render();
            $this->assign('bmlist',$bmlist);
            $this->assign('page',$page);
        }

        return $this->fetch();
    }

    /*
     * 修改部门的有效状态/开启、禁用
     * */
    public function bm_state()
    {
        $id=input('x');
        if (empty($id)){
            $this->error('部门ID不存在',url('admin/Basedata/bm_list'));
        }
        $status=Db::name('dl_bumen')->where('bumid',$id)->value('youxbz');//判断当前状态情况
        if($status==0){
            $statedata = array('youxbz'=>1);
            Db::name('dl_bumen')->where('bumid',$id)->setField($statedata);
            $this->success('状态禁止');
        }else{
            $statedata = array('youxbz'=>0);
            Db::name('dl_bumen')->where('bumid',$id)->setField($statedata);
            $this->success('状态开启');
        }
    }

    /*
     * 添加部门功能
     * */
    public function bm_add()
    {
        $admin_list = DB::name('admin')->select();
        $this->assign('admin_list',$admin_list);
        if(!empty($_POST)){
            $bummc = $_POST['bummc'];
            $pinym = $_POST['pinym'];
            //$bumbm = $_POST['bumbm'];  //部门编码，暂时不用
            $bumjl = $_POST['bumjl'];
            if(empty($_POST['youxbz'])){
                $youxbz = '0';
            }else{
                $youxbz = $_POST['youxbz'];
            }

            $data = [
                'bummc'     => $bummc,
                'pinym'     => $pinym,
                'bumjl'     => $bumjl,
                'youxbz'    => $youxbz,
            ];
            //添加部门
            $fl = Db::name('dl_bumen')->insert($data);

            if($fl){
                $this->success("部门添加成功","Basedata/bm_list");
            }else{
                $this->error("部门添加失败","Basedata/bm_list");
            }
        }
        return $this->fetch("Basedata/bm_add");
    }

    /**
     * 部门修改
     */
    public function bm_edit()
    {
        $admin_list = DB::name('admin')->select(); //显示出经理要用
        $this->assign('admin_list',$admin_list);
        $bumid = DB::name('dl_bumen')->where('bumid',input('bumid'))->find();
        $this->assign('bumbm',$bumid['bumbm']);  //部门编码暂时用不到
        $this->assign('bumid',$bumid['bumid']);
        $this->assign('bummc',$bumid['bummc']);
        $this->assign('pinym',$bumid['pinym']);
        $this->assign('bumjl',$bumid['bumjl']);

        return $this->fetch('Basedata/bm_edit');
    }

    /**
     * 部门修改操作
     */
    public function bm_runedit()
    {
        $data=input('post.');
        $rst=DB::name('dl_bumen')->update($data);
        if($rst!==false){
            $this->success('部门修改成功',url('admin/Basedata/bm_list'));
        }else{
            $this->error('部门修改失败',url('admin/Basedata/bm_list'));
        }
    }

    /**
     * 部门删除
     **/
    public function bm_del()
    {
        $bumid=input('bumid');
        if (empty($bumid)){
            $this->error('部门ID不存在',url('admin/Basedata/bm_list'));
        }else{
            //删除对应的部门
            $rst = Db::name('dl_bumen')->delete($bumid);
            if($rst!==false){
                $this->success('部门删除成功',url('admin/Basedata/bm_list'));
            }else{
                $this->error('部门删除失败',url('admin/Basedata/bm_list'));
            }
        }
    }

    /*-------------------部门功能结束-----------------------*/



    /*-------------------项目功能开始-----------------------*/
    /*
     * 项目列表
     * */
    public function xm_list()
    {
        $admin_list = DB::name('admin')->select();
        $this->assign('admin_list',$admin_list);
        if(empty($_POST)){
            $xmlist = DB::name('dl_xiangmu')->paginate(config('paginate.list_rows'),false);
            $page = $xmlist->render();
            $this->assign('page',$page);
            $this->assign('xmlist',$xmlist);
        }else{
            $search_name = input('search_name');
            $this->assign('search_name',$search_name);
            $map = array();  //用来装查询条件
            if($search_name){
                $map['xiangmmc'] = array('like','%'.$search_name.'%');
            }
            $xmlist = DB::name('dl_xiangmu')->where($map)->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
            $page = $xmlist->render();
            $this->assign('page',$page);
            $this->assign('xmlist',$xmlist);
        }
        return $this->fetch('Basedata/xm_list');

    }

    /*
     * 项目有效状态、开启/禁用
     * */
    public function xm_state()
    {
        $id=input('x');
        if (empty($id)){
            $this->error('项目ID不存在',url('admin/Basedata/xm_list'));
        }
        $status=Db::name('dl_xiangmu')->where('xiangmid',$id)->value('youxbz');//判断当前状态情况
        if($status==0){
            $statedata = array('youxbz'=>1);
            Db::name('dl_xiangmu')->where('xiangmid',$id)->setField($statedata);
            $this->success('状态禁止');
        }else{
            $statedata = array('youxbz'=>0);
            Db::name('dl_xiangmu')->where('xiangmid',$id)->setField($statedata);
            $this->success('状态开启');
        }
    }

    /*
     * 项目添加
     * */
    public function xm_add()
    {
        $admin_list = DB::name('admin')->select();
        $this->assign('admin_list',$admin_list);
        if(!empty($_POST)){
            $data = input('post.');
            $fl = DB::name('dl_xiangmu')->insert($data);
            if($fl){
                $this->success("项目添加成功","Basedata/xm_list");
            }else{
                $this->error("项目添加失败","Basedata/xm_list");
            }
        }
        return $this->fetch('Basedata/xm_add');
    }

    /*
     * 项目修改
     * */
    public function xm_edit()
    {
        $admin_list = DB::name('admin')->select();
        $this->assign('admin_list',$admin_list);
        $xmlist = DB::name('dl_xiangmu')->where('xiangmid',input('xiangmid'))->find();
        $this->assign('xmlist',$xmlist);
        return $this->fetch('Basedata/xm_edit');
    }

    /*
     * 项目修改操作
     * */
    public function xm_runedit()
    {
        $data=input('post.');
        $rst=DB::name('dl_xiangmu')->update($data);
        if($rst!==false){
            $this->success('项目修改成功',url('admin/Basedata/xm_list'));
        }else{
            $this->error('项目修改失败',url('admin/Basedata/xm_list'));
        }
    }

    /*
     * 项目删除
     * */
    public function xm_del()
    {
        $xiangmid=input('xiangmid');
        if (empty($xiangmid)){
            $this->error('项目ID不存在',url('admin/Basedata/xm_list'));
        }else{
            //删除对应的项目
            $rst = Db::name('dl_xiangmu')->delete($xiangmid);
            if($rst!==false){
                $this->success('项目删除成功',url('admin/Basedata/xm_list'));
            }else{
                $this->error('项目删除失败',url('admin/Basedata/xm_list'));
            }
        }
    }

    /*-------------------项目功能结束-----------------------*/



    /*-------------------产品功能开始-----------------------*/
    /*
     * 产品列表
     * */
    public function cp_list()
    {
        $admin_list = DB::name('admin')->select();
        $this->assign('admin_list',$admin_list);
        if(empty($_POST)){
            $cplist = DB::name('dl_chanpin')->paginate(config('paginate.list_rows'),false);
            $page = $cplist->render();
            $this->assign('page',$page);
            $this->assign('cplist',$cplist);
        }else{
            $search_name = input('search_name');
            $this->assign('search_name',$search_name);
            $map = array();  //用来装查询条件
            if($search_name){
                $map['chanpmc'] = array('like','%'.$search_name.'%');
            }
            $cplist = DB::name('dl_chanpin')->where($map)->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
            $page = $cplist->render();
            $this->assign('page',$page);
            $this->assign('cplist',$cplist);
        }
        return $this->fetch('Basedata/cp_list');

    }

    /*
    * 产品有效状态、开启/禁用
    * */
    public function cp_state()
    {
        $id=input('x');
        if (empty($id)){
            $this->error('产品ID不存在',url('admin/Basedata/cp_list'));
        }
        $status=Db::name('dl_chanpin')->where('chanpid',$id)->value('youxbz');//判断当前状态情况
        if($status==0){
            $statedata = array('youxbz'=>1);
            Db::name('dl_chanpin')->where('chanpid',$id)->setField($statedata);
            $this->success('状态禁止');
        }else{
            $statedata = array('youxbz'=>0);
            Db::name('dl_chanpin')->where('chanpid',$id)->setField($statedata);
            $this->success('状态开启');
        }
    }

    /*
     * 产品添加
     * */
    public function cp_add()
    {
        $admin_list = DB::name('admin')->select();
        $this->assign('admin_list',$admin_list);
        if(!empty($_POST)){
            $data = input('post.');
            $fl = DB::name('dl_chanpin')->insert($data);
            if($fl){
                $this->success("产品添加成功","Basedata/cp_list");
            }else{
                $this->error("产品添加失败","Basedata/cp_list");
            }
        }
        return $this->fetch('Basedata/cp_add');
    }

    /*
     * 产品修改
     * */
    public function cp_edit()
    {
        $admin_list = DB::name('admin')->select();
        $this->assign('admin_list',$admin_list);
        $cplist = DB::name('dl_chanpin')->where('chanpid',input('chanpid'))->find();
        $this->assign('cplist',$cplist);
        return $this->fetch('Basedata/cp_edit');
    }

    /*
     * 产品修改操作
     * */
    public function cp_runedit()
    {
        $data=input('post.');
        $rst=DB::name('dl_chanpin')->update($data);
        if($rst!==false){
            $this->success('产品修改成功',url('admin/Basedata/cp_list'));
        }else{
            $this->error('产品修改失败',url('admin/Basedata/cp_list'));
        }
    }

    /*
     * 产品删除
     * */
    public function cp_del()
    {
        $chanpid=input('chanpid');
        if (empty($chanpid)){
            $this->error('项目ID不存在',url('admin/Basedata/cp_list'));
        }else{
            //删除对应的产品
            $rst = Db::name('dl_chanpin')->delete($chanpid);
            if($rst!==false){
                $this->success('产品删除成功',url('admin/Basedata/cp_list'));
            }else{
                $this->error('产品删除失败',url('admin/Basedata/cp_list'));
            }
        }
    }

    /*-------------------产品功能结束-----------------------*/



    /*-------------------二级代码状态功能开始-----------------------*/
    /*
     * 状态列表
     * */
    public function zt_list()
    {
        if(empty($_POST)){
            $ztlist = DB::name('dl_erjidaima')->paginate(config('paginate.list_rows'),false);
            $page = $ztlist->render();
            $this->assign('ztlist',$ztlist);
            $this->assign('page',$page);
        }else{
            $search_name = input('search_name');
            $this->assign('search_name',$search_name);
            $map = array();  //用来装查询条件
            if($search_name){
                $map['fenlbm'] = array('like','%'.$search_name.'%');
            }
            $ztlist = DB::name('dl_erjidaima')->where($map)->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
            $page = $ztlist->render();
            $this->assign('page',$page);
            $this->assign('ztlist',$ztlist);
        }

        return $this->fetch('Basedata/zt_list');
    }

    /*
     * 状态有效状态、开启/禁用
     * */
    public function zt_state()
    {
        $id=input('x');
        if (empty($id)){
            $this->error('状态ID不存在',url('admin/Basedata/zt_list'));
        }
        $status=Db::name('dl_erjidaima')->where('daimid',$id)->value('youxbz');//判断当前状态情况
        if($status==0){
            $statedata = array('youxbz'=>1);
            Db::name('dl_erjidaima')->where('daimid',$id)->setField($statedata);
            $this->success('状态禁止');
        }else{
            $statedata = array('youxbz'=>0);
            Db::name('dl_erjidaima')->where('daimid',$id)->setField($statedata);
            $this->success('状态开启');
        }
    }

    /*
     * 状态添加
     * */
    public function zt_add()
    {
        if(!empty($_POST)){
            $data = input('post.');
            $fl = DB::name('dl_erjidaima')->insert($data);
            if($fl){
                $this->success("状态添加成功","Basedata/zt_list");
            }else{
                $this->error("状态添加失败","Basedata/zt_list");
            }
        }
        return $this->fetch('Basedata/zt_add');
    }

    /*
     * 状态修改显示
     * */
    public function zt_edit()
    {
        $ztlist = DB::name('dl_erjidaima')->where('daimid',input('daimid'))->find();
        $this->assign('ztlist',$ztlist);
        return $this->fetch('Basedata/zt_edit');
    }

    /*
     * 状态修改操作
     * */
    public function zt_runedit()
    {
        $data=input('post.');
        $rst=DB::name('dl_erjidaima')->update($data);
        if($rst!==false){
            $this->success('状态修改成功',url('admin/Basedata/zt_list'));
        }else{
            $this->error('状态修改失败',url('admin/Basedata/zt_list'));
        }
    }

    /*
     * 状态删除
     * */
    public function zt_del()
    {
        $daimid=input('daimid');
        if (empty($daimid)){
            $this->error('状态ID不存在',url('admin/Basedata/zt_list'));
        }else{
            //删除对应的状态
            $rst = Db::name('dl_erjidaima')->delete($daimid);
            if($rst!==false){
                $this->success('状态删除成功',url('admin/Basedata/zt_list'));
            }else{
                $this->error('状态删除失败',url('admin/Basedata/zt_list'));
            }
        }
    }

    /*-------------------二级代码状态功能结束-----------------------*/
}