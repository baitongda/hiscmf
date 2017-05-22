<?php
/**
 * Created by PhpStorm.
 * User: liyuzhao
 * Date: 2017/3/22
 * Time: 16:34
 */
namespace app\admin\controller;
use EasyWeChat\Staff\Session;
use think\Db;
use think\Cache;

class Issuetj extends Base
{
    //显示图形统计页面
    public function issue_list()
    {
        //计算出发布的任务总个数
        $count = Db::name('dl_buglist')->count();
        $this->assign('count',$count);
        //计算出未解决任务
        $sum = Db::name('dl_buglist')->where('rw_state',0)->count();
        $this->assign('sum',$sum);
        //已解决任务
        $a = $count - $sum;
        $this->assign('a',$a);
        //取出一月、二月...的审核通过的任务数量,全部设计为31号，无关紧要,写死了，后面改，现在懒得改
        $arr = [
            '1'  =>  array(array('>','2017-01-01'),array('<','2017-01-31'),'AND'),
            '2'  =>  array(array('>','2017-02-01'),array('<','2017-02-31'),'AND'),
            '3'  =>  array(array('>','2017-03-01'),array('<','2017-03-31'),'AND'),
            '4'  =>  array(array('>','2017-04-01'),array('<','2017-04-31'),'AND'),
            '5'  =>  array(array('>','2017-05-01'),array('<','2017-05-31'),'AND'),
            '6'  =>  array(array('>','2017-06-01'),array('<','2017-06-31'),'AND'),
            '7'  =>  array(array('>','2017-07-01'),array('<','2017-07-31'),'AND'),
            '8'  =>  array(array('>','2017-08-01'),array('<','2017-08-31'),'AND'),
            '9'  =>  array(array('>','2017-09-01'),array('<','2017-09-31'),'AND'),
            '10' =>  array(array('>','2017-10-01'),array('<','2017-10-31'),'AND'),
            '11' =>  array(array('>','2017-11-01'),array('<','2017-11-31'),'AND'),
            '12' =>  array(array('>','2017-12-01'),array('<','2017-12-31'),'AND'),
        ];
        foreach($arr as $k=>$v){
            $map['fabsj'] = $v;
            unset($map['rw_state']);
            //计算出每个月提交的任务数量
            $all_count[$k] = Db::name('dl_buglist')->where($map)->count();
            //计算出每个月已完成的任务
            $map['rw_state'] = 0;
            $sum_count[$k] = Db::name('dl_buglist')->where($map)->count();
            //计算出每个月未完成的任务
            $one_count[$k] = $all_count[$k] - $sum_count[$k];
        }
        //print_r($all_count);exit;
        //把数组变成字符串，格式为1,2,3...
        $all_count = implode(',',$all_count);
        $this->assign('all_count',$all_count);

        //把数组变成字符串，格式为1,2,3...
        $sum_count = implode(',',$sum_count);
        $this->assign('sum_count',$sum_count);

        //把数组变成字符串，格式为1,2,3...
        $one_count = implode(',',$one_count);
        $this->assign('one_count',$one_count);

        return $this->fetch();
    }

    //统计页面查询操作
    public function issue_runlist()
    {
        $map = array();
        $cp = input('cp','');  //接收产品
        $xm = input('xm','');  //接收项目

        if ($cp != '') {
            $map['chanp_id'] = $cp;
        }
        if ($xm != '') {
            $map['xiangm_id'] = $xm;
        }
        //计算出发布的任务总个数
        $count = Db::name('dl_buglist')->where($map)->count();
        $this->assign('count',$count);
        //计算出未解决任务
        $sum = Db::name('dl_buglist')->where($map)->where('rw_state',0)->count();
        $this->assign('sum',$sum);
        //已解决任务
        $a = $count - $sum;
        $this->assign('a',$a);
        //取出一月、二月...的审核通过的任务数量,全部设计为31号，无关紧要
        $arr = [
            '1'  =>  array(array('>','2017-01-01'),array('<','2017-01-31'),'AND'),
            '2'  =>  array(array('>','2017-02-01'),array('<','2017-02-31'),'AND'),
            '3'  =>  array(array('>','2017-03-01'),array('<','2017-03-31'),'AND'),
            '4'  =>  array(array('>','2017-04-01'),array('<','2017-04-31'),'AND'),
            '5'  =>  array(array('>','2017-05-01'),array('<','2017-05-31'),'AND'),
            '6'  =>  array(array('>','2017-06-01'),array('<','2017-06-31'),'AND'),
            '7'  =>  array(array('>','2017-07-01'),array('<','2017-07-31'),'AND'),
            '8'  =>  array(array('>','2017-08-01'),array('<','2017-08-31'),'AND'),
            '9'  =>  array(array('>','2017-09-01'),array('<','2017-09-31'),'AND'),
            '10' =>  array(array('>','2017-10-01'),array('<','2017-10-31'),'AND'),
            '11' =>  array(array('>','2017-11-01'),array('<','2017-11-31'),'AND'),
            '12' =>  array(array('>','2017-12-01'),array('<','2017-12-31'),'AND'),
        ];
        //查出项目或者产品的问题id
        foreach($arr as $k=>$v){
            $map['fabsj'] = $v;
            unset($map['rw_state']);
            //计算出每个月提交的任务数量
            $all_count[$k] = Db::name('dl_buglist')->where($map)->count();
            //计算出每个月已完成的任务
            $map['rw_state'] = 0;
            $sum_count[$k] = Db::name('dl_buglist')->where($map)->count();
            //计算出每个月未完成的任务
            $one_count[$k] = $all_count[$k] - $sum_count[$k];
        }
        //把数组变成字符串，格式为1,2,3...
        $all_count = implode(',',$all_count);
        $this->assign('all_count',$all_count);

        //把数组变成字符串，格式为1,2,3...
        $sum_count = implode(',',$sum_count);
        $this->assign('sum_count',$sum_count);

        //把数组变成字符串，格式为1,2,3...
        $one_count = implode(',',$one_count);
        $this->assign('one_count',$one_count);
        $a = array('count'=>$count,'sum'=>$sum,'a'=>$a,'one_count'=>$one_count,'all_count'=>$all_count,'sum_count'=>$sum_count,'sum_count'=>$sum_count);
        echo json_encode($a,true);
        //return $this->fetch();
    }
}