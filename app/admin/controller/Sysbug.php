<?php
/**
 * Created by PhpStorm.
 * User: liyuzhao
 * Date: 2017/4/12
 * Time: 10:02
 */
namespace app\admin\controller;
use think\Db;

class Sysbug extends Base
{
    //显示bug的列表
    public function bug_list()
    {
        $map = array();
        $key=input('key');  //关键字
        $admin_id = input('admin_id',''); //接收用户id

        if ($admin_id != '') {
            $map['admin_id'] = $admin_id;
        }

        //查询：时间格式过滤 获取格式 2015-11-12 - 2015-11-18
        $sldate=input('reservation','');
        $arr = explode(" - ",$sldate);
        if(count($arr)==2){
            $arrdateone=$arr[0]. ' 00:00:00';
            $arrdatetwo=$arr[1].' 23:55:55';
            $map['subtime'] = array(array('egt',strtotime($arrdateone)),array('elt',strtotime($arrdatetwo)),'AND');
        }
        //模糊查询
        if(!empty($key)){
            $map['content']= array('like',"%".$key."%");
        }
        $bug = Db::name('dl_sysbug')->where($map)->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
        $show = $bug->render();
        $show=preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)","<a href='javascript:ajax_page($1);'>$2</a>",$show);
        $this->assign('page',$show);
        $this->assign('bug',$bug);
        if(request()->isAjax()){
            return $this->fetch('ajax_bug_list');
        }else{
            return $this->fetch();
        }
    }

    /*
     * 提交系统bug、问题反馈操作显示
    * */
    public function put_bug()
    {
        return $this->fetch();
    }

    /*
     *提交系统bug、问题操作
     * */
    public function put_runbug()
    {
        $data = [
            'subtime'   =>  time(),
            'content'   =>  $_POST['content'],
            'admin_id'  =>  $_POST['admin_id']
        ];
        //print_r($data);exit;
        //更新bug表
        $fl = Db::name('dl_sysbug')->insertGetId($data);
        if($fl){
            $this->success("感觉你的提交，我会尽快确认修改O(∩_∩)O","Index/index");
        }else{
            $this->success("出问题啦，请联系管理员处理","Index/index");
        }
    }
}