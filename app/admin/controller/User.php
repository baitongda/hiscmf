<?php
/**
 * Created by PhpStorm.
 * User: liyuzhao
 * Date: 2017/3/27
 * Time: 10:04
 */
namespace app\admin\controller;

use think\Db;

class User extends Base
{
    //显示客户的问题记录
    public function issue_list()
    {
        $map = array();
        $bz = input('renwu', '');
        $key = input('key');  //关键字
        if ($bz == 3) { //未处理
            $map['shbz'] = 0;
            $map['rw_state'] = 0;
        } else if ($bz == 1) { //处理中
            $map['shbz'] = 1;
            $map['rw_state'] = 0;
        } else if ($bz == 2) { //已处理
            $map['rw_state'] = 26;
        }
        $fl = Db::name('auth_group_access')->where('uid',session('admin_auth.aid'))->find();

        if($fl['group_id'] == 7){ //7为客户角色
            $map['fabry'] = session('admin_auth.aid'); //如果是客户，就显示客户的问题
        }else{
            //否则显示所有客户的问题
            $user = Db::name('auth_group_access')->where('group_id',7)->column('uid');//所有客户的id
            $map['fabry'] = array('in',array_values($user));
            //print_r($map);exit;
        }
        //查询：时间格式过滤 获取格式 2015-11-12 - 2015-11-18
        $sldate=input('reservation','');
        $arr = explode(" - ",$sldate);
        if(count($arr)==2){
            $arrdateone=$arr[0]. ' 00:00:00';
            $arrdatetwo=$arr[1].' 23:55:55';
            $map['fabsj'] = array(array('egt',$arrdateone),array('elt',$arrdatetwo),'AND');
        }
        //模糊查询
        if(!empty($key)){
            $map['renwbt']= array('like',"%".$key."%");
        }

        $buglist = Db::name('dl_buglist')->where($map)->order('shsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);

        $show = $buglist->render();
        $show = preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)", "<a href='javascript:ajax_page($1);'>$2</a>", $show);
        $this->assign('page', $show);
        $this->assign('buglist', $buglist);
        //获取客户总问题个数
        $count = Db::name('dl_buglist')->where($map)->count();
        $this->assign('sum',$count);
        //获取客户已经处理的问题数目
        $count1 = Db::name('dl_buglist')->where($map)->where('rw_state',26)->count();
        $this->assign('cl_sum',$count1);
        //获取客户处理中的问题数目
        $count2 = Db::name('dl_buglist')->where($map)->where('shbz',1)->where('rw_state',0)->count();
        $this->assign('clz_sum',$count2);
        if (request()->isAjax()) {
            return $this->fetch('ajax_issue_list');
        } else {
            return $this->fetch();
        }
    }

    //显示客户问题的添加
    public function issue_add()
    {
        //获取实施部主管的id和名称
        $list = Db::name('auth_group')->where('id',5)->find();
        $this->assign('auth',$list);
        return $this->fetch("user/issue_add");
    }

    //客户问题添加操作
    public function issue_runadd()
    {
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $shijian =  date('Y-m-d H:i:s',time());
        $jied = Db::name('dl_erjidaima')->where('daimmc','审核阶段')->where('fenlbm','当前阶段')->find();
        //更新问题主表
        $data = [
            'jiedfzr'   => $_POST['jiedfzr'],
            'jied'      => $jied['daimid'],
            'fabry'     => session('admin_auth.aid'),
            'yaoqwcsj'  => $_POST['yaoqwcsj'],
            'renwbt'    => $_POST['renwbt'],
            'renwnr'    => $_POST['renwnr'],
            'fabsj'     => $shijian,
            'chanp_id'   => $_POST['chanpid'],  //产品id
            'xiangm_id'  => $_POST['xiangmid'],  //项目id
            'mk_name'   => $_POST['mk_name'],  //模块名称
            'shbz'      =>  '1', //问题不用审核，添加问题即为审核成功
            'shsj'      =>  $shijian,
            'zhuangt'   =>  11,//问题添加成功，问题则为流转
            'uptime'    =>  $shijian, //更新更新时间
        ];
        $bulist_id = DB::name('dl_buglist')->insertGetId($data);
        if($bulist_id){
            /*
             * 使用正则去获取上传文件的相对路径和名称，然后判断plug_file里面的文件路径是否匹配，如果匹配就更新plug_file里面的buglist_id.
             * */
            $file_name = $_POST['renwnr'];
            $patt = '/\/data\/[a-zA-z0-9-\/\.]+/';
            preg_match_all($patt,$file_name,$res);  //得到了上传文件的相对路径
            $file_list = DB::name('plug_files')->where('up_user',session('admin_auth.admin_realname'))->select();
            //循环判断是否和plug_file里面的路径一样
            foreach($file_list as $k=>$v){
                for($i = 0;$i < count($res[0]);$i++){
                    if($res['0'][$i] === $v['path']){
                        DB::name('plug_files')->where('id',$v['id'])->setField('buglist_id',$bulist_id);
                        break;
                    }
                }
            }

            /*问题分配，发送邮件给当前阶段负责人,也就是审核人员*/
            $buglist = Db::name('dl_buglist')->where('renwid',$bulist_id)->find(); //获取当前问题信息
            $jiedfzr = Db::name('admin')->where('admin_id',$data['jiedfzr'])->find();//获取当前阶段人员的信息
            $fabry = Db::name('admin')->where('admin_id',$data['fabry'])->find();//获取发布人员的信息
            $bt = '#'.$buglist['renwid'].' '.$buglist['renwbt'].' 问题审核提醒';
            $nr = '';
            $nr .= 'hi '.$jiedfzr['admin_realname'].',有一个新任务(问题)需要你审核 '.'<br/>';
            $nr .= '任务(问题)名称：'.$buglist['renwbt'].'<br/>';
            $nr .= '内容：'.$buglist['renwnr'].'<br/>';
            $nr .= '问题链接：'.'<a href='.ADMIN_URL.'task/verify_one/renwid/'.$buglist['renwid'].'>点我</a>'.'<br/>';
            $nr .= '当前阶段负责人：'.$jiedfzr['admin_realname'].'<br/>';
            $nr .= '问题发布人员(客户)：'.$fabry['admin_realname'].'<br/>';
            $nr .= '操作人员(客户)：'.$fabry['admin_realname'].'<br/>';
            $nr .= '温馨提示：此邮件为提醒邮件，请勿直接回复，谢谢合作！';

            sendMail($jiedfzr['admin_email'],$bt,$nr);  //发送邮件给阶段负责人

            $this->success("问题添加成功","User/issue_list");
        }else{
            $this->error("问题添加失败,请联系公司客服","User/issue_list");
        }
    }

    //客户问题详细显示
    public function issue_one()
    {
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $renwid = input('param.renwid');
        $this->assign('renwid',$renwid);
        //显示问题
        $bug_list = DB::name('dl_buglist')->where('renwid',$renwid)->find();
        $this->assign('bug_list',$bug_list);
        //显示该问题的所有附件下载
        $file_list = DB::name('plug_files')->where('buglist_id',$renwid)->select();
        $this->assign('file_list',$file_list);
        //显示该问题的所有子任务回复
        $renwnr = Db::name('dl_buglist_zirw')->where('renwid',$renwid)->select();
        $this->assign('renwnr',$renwnr);

        $list = Db::name('dl_buglist_zirw')->where('renwid',$renwid)->order('zirwid desc')->limit('2,2')->select();  //获取最新子任务的倒数第二条记录来获取上一个阶段的负责人
        $this->assign('sjdfzr','');
        if(!empty($list)){
            $this->assign('sjdfzr',$list[0]['fuzr']);
        }
        return $this->fetch("user/issue_one");
    }
}