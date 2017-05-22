<?php
// +----------------------------------------------------------------------
// | YFCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2016 http://www.rainfer.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: rainfer <81818832@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use think\Db;
use think\Cache;
use think\helper\Time;
use app\admin\model\News as NewsModel;
use app\admin\model\MemberList;

class Index extends Base
{
    /**
     * 后台首页
     */
    public function index()
    {
        //日期xxxx-xx-xx xx:xx:xx
        $start_t = date('Y-m-d 00:00:00',time()); //今天开始日期
        $end_t = date('Y-m-d 23:59:59',time()); //今天结束日期
        $start_y = date('Y-m-d 00:00:00',strtotime('-1 day')); //昨天结束日期
        $end_y = date('Y-m-d 23:59:59',strtotime('-1 day')); //昨天结束日期
        //显示总问题个数
        $issue_count = Db::name('dl_buglist')->where('shbz','1')->count();
        $this->assign('issue_count',$issue_count);
        //今日新增的问题个数
        $new_issue_count = Db::name('dl_buglist')->whereTime('fabsj', 'between', [$start_t, $end_t])->count();
        $this->assign('new_issue_count',$new_issue_count);
        //昨日新增的问题个数
        $y_issue_count = Db::name('dl_buglist')->whereTime('fabsj', 'between', [$start_y, $end_y])->count();
        $difday_i=($y_issue_count>0)?($new_issue_count-$y_issue_count)/$y_issue_count*100:0;
        $this->assign('difday_i',$difday_i);

        //显示总人数
        $admin_count = Db::name('admin')->count();
        $this->assign('admin_count',$admin_count);
        //今日新增人员
        $new_admin_count = Db::name('admin')->whereTime('admin_addtime', 'between', [strtotime($start_t), strtotime($end_t)])->count();
        $this->assign('new_admin_count',$new_admin_count);
        //昨日新增人员
        $y_admin_count = Db::name('admin')->whereTime('admin_addtime', 'between', [strtotime($start_y), strtotime($end_y)])->count();
        $difday_a=($y_admin_count>0)?($new_admin_count-$y_admin_count)/$y_admin_count*100:0;
        $this->assign('difday_a',$difday_a);
        //显示总解决的问题个数
        $solve_count = 0; //统计总解决问题的个数
        $a = Db::name('dl_buglist')->where('rw_state',26)->column('renwid'); //获取所有关闭问题的id
        foreach($a as $k=>$v){
            $b = Db::name('dl_buglist_zirw')->where('renwid',$v)->select();
            if($b){
                $solve_count += 1;
            }
        }
        $this->assign('solve_count',$solve_count);
        //今日解决问题个数
        $new_solve_count = 0;
        foreach($a as $k=>$v){
            $c = Db::name('dl_buglist_zirw')->where('jiedsjwcsj','between',[$start_t,$end_t])->where('renwid',$v)->select();
            if($c){
                $new_solve_count += 1;
            }
        }
        $this->assign('new_solve_count',$new_solve_count);
        //昨日解决问题个数
        $y_solve_count = 0;
        foreach($a as $k=>$v){
            $d = Db::name('dl_buglist_zirw')->where('jiedsjwcsj','between',[$start_y,$end_y])->where('renwid',$v)->select();
            if($d){
                $y_solve_count += 1;
            }
        }
        $difday_s=($y_solve_count>0)?($new_solve_count-$y_solve_count)/$y_solve_count*100:0;
        $this->assign('difday_s',$difday_s);

        //所有解决的问题数据
        $zirwlist = Db::query("select * from his_dl_buglist_zirw where jiedgs <> 0 and biaoz = 19");
        $arr = array();
        foreach($zirwlist as $k=>$v){
            $count = Db::name('dl_buglist_zirw')->where('jiedgs','<>',0)->where('biaoz',19)->where('fuzr',$v['fuzr'])->count();
            $arr[$v['fuzr']]['count'] = $count;
        }
        $keys = array_keys($arr); //获取数组里面的所以键值
        array_multisort(array_column($arr, 'count'),SORT_DESC,SORT_NUMERIC,$arr,$keys); //数组按照count的大小来进行降序排列array_column是取出数组中的某一列的值
        $arr = array_combine($keys, $arr); //合并两个数组来创建一个新数组，其中的一个数组元素为键名，另一个数组元素为键值：
        //只取前面十个
        $data = array();
        $i = 0;//标志
        foreach($arr as $k=>$v){
            if($i<10){
                $data[$k] = $v;
            }else{
                break;
            }
            $i++;
        }

        $this->assign('arr',$arr);
        //查询部门
        $group = Db::query("select a.admin_id,c.title from his_admin a left join his_auth_group_access b on b.uid = a.admin_id left join his_auth_group c on c.id = b.group_id");
//print_r($group);exit;
        //$bm = Db::name('dl_bumen')->whereIn('bumid',$group)->select();
        $this->assign('bm',$group);
        //查询当前人员是否是客户
        $group_id = Db::name('auth_group_access')->where('uid',session('admin_auth.aid'))->value('group_id');
        if($group_id == 7){ //7为客户管理
            $this->assign('xx',1); //xx为一个标志
        }else{
            $this->assign('xx',2);
        }

        //弹出靠近问题日期的数据条数，用于index页面的右下角数据记录
        $date_new = date('Y-m-d H:i:s',strtotime("+3 day")); //得到未来三天的日期
        //查询出靠近完成时间的问题个数
        $bug_list_count = Db::name('dl_buglist')->where('rw_state','<>',26)->where('yaoqwcsj','<',$date_new)->where('jiedfzr',session('admin_auth.aid'))->count();
        $this->assign('bug_list_count',$bug_list_count);

        //弹出本周未交周报提醒
        $first = 1;
        //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $zb_time = date('Y-m-d',time());
        $w = date('w',strtotime($zb_time));
        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $week_start = strtotime("$zb_time -".($w ? $w - $first : 6).' days');
        //本周结束日期
        $a = date('Y-m-d',$week_start);
        $week_end = strtotime("$a +6 days");
        //查询当前用户此日期下的子任务\条件：流转时间不为空，当前用户，当前星期时间，标志不等于零
        $fl = Db::name('dl_zb')->where('addtime','between',[$week_start,$week_end])->where('admin_id',session('admin_auth.aid'))->find();
        if($fl){
            $this->assign('zbts','1');  //如果查询到此周交了周报，就为1
        }else{
            $this->assign('zbts','0');
        }

        //渲染模板
        return $this->fetch();
    }
    /**
     * 后台多语言切换
     */
    public function lang()
    {
        if (!request()->isAjax()){
            $this->error('提交方式不正确');
        }else{
            $lang=input('lang_s');
            session('login_http_referer',$_SERVER["HTTP_REFERER"]);
            switch ($lang) {
                case 'cn':
                    cookie('think_var', 'zh-cn');
                    break;
                case 'en':
                    cookie('think_var', 'en-us');
                    break;
                //其它语言
                default:
                    cookie('think_var', 'zh-cn');
            }
            Cache::clear();
            $this->success('切换成功',session('login_http_referer'));
        }
    }
}