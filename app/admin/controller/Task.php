<?php
/**
 * Created by PhpStorm.
 * User: liyuzhao
 * Date: 2017/3/7
 * Time: 14:45
 */
namespace app\admin\controller;

use EasyWeChat\Staff\Session;
use think\Db;
use think\Cache;

class Task extends Base
{
    /*----------------------------任务功能页面开始-----------------------*/
    //任务列表显示
    public function issue_list()
    {
        $map = array();
        $key = input('key');  //关键字
        $youxjb = input('youxjb', ''); //接收任务级别
        $renwlx = input('renwlx', ''); //接收任务类型
        $fabry = input('fabry', ''); //接收发布人员
        $jiedfzr = input('jiedfzr', ''); //接收阶段负责人
        $jiedfzr1 = input('jiedfzr1', ''); //接收阶段负责人-排序
        $zhuangt = input('zhuangt', ''); //当前状态
        $biaoz = input('biaoz', ''); //状态标志
        $jied = input('jied', ''); //当前阶段
        $renwu = input('renwu', '');  //接收任务选择选项
        $update = input('update', '');  //更新时间排序选择选项
        $yxjb = input('yxjb', '');  //接收优先级别排序选择选项
        $fabsj = input('fabsj', '');  //发布时间排序选择选项
        $jiedyjwcsj = input('jiedyjwcsj', '');  //阶段预计完成时间排序选择选项
        $yaoqwcsj = input('yaoqwcsj', '');  //要求完成时间排序选择选项
        $cp = input('cp', '');  //接收产品
        $xm = input('xm', '');  //接收项目
        $cpmc = input('cpmc', '');  //接收项目
        $xmmc = input('xmmc', '');  //接收项目

        if ($youxjb != '') {
            $map['youxjb'] = $youxjb;
        }
        if ($renwlx != '') {
            $map['renwlx'] = $renwlx;
        }
        if ($fabry != '') {
            $map['fabry'] = $fabry;
        }
        if ($jiedfzr != '') {
            $map['jiedfzr'] = $jiedfzr;
        }
        if ($zhuangt != '') {
            $map['zhuangt'] = $zhuangt;
        }
        if ($biaoz != '') {
            $map['biaoz'] = $biaoz;
        }
        if ($jied != '') {
            $map['jied'] = $jied;
        }
        if ($cp != '') {
            $map['chanp_id'] = $cp;
        }
        if ($xm != '') {
            $map['xiangm_id'] = $xm;
        }

        //查询：时间格式过滤 获取格式 2015-11-12 - 2015-11-18
        $sldate = input('reservation', '');
        $arr = explode(" - ", $sldate);
        if (count($arr) == 2) {
            $arrdateone = $arr[0] . ' 00:00:00';
            $arrdatetwo = $arr[1] . ' 23:55:55';
            $map['fabsj'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
        }
        //模糊查询
        if (!empty($key)) {
            $map['renwbt'] = array('like', "%" . $key . "%");
        }
        //显示已经审核过的
        $map['shbz'] = 1;
        //不显示已经关闭的任务
        $map['rw_state'] = array('<>', 26);
        if ($fabry == 0) {
            unset($map['fabry']);
        }
        if ($jiedfzr == 0) {
            unset($map['jiedfzr']);
        }
        if ($renwu == '0') {  //我的所有任务
            $map['shbz'] = '1';
            $map['jiedfzr'] = session('admin_auth.aid');
            $buglist = Db::name('dl_buglist')->where($map)->order('fabsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        } elseif ($renwu == '1') {  //我未接收的任务
            $zhuangt = Db::name('dl_erjidaima')->where('fenlbm', '当前状态')->where('daimmc', '未接收')->value('daimid'); //更新任务主表的当前状态，为未接收
            $map['shbz'] = '1';
            $map['jiedfzr'] = session('admin_auth.aid');
            $map['zhuangt'] = $zhuangt;
            $buglist = Db::name('dl_buglist')->where($map)->order('fabsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        } elseif ($renwu == '2') {  //我已接收的任务
            $zhuangt = Db::name('dl_erjidaima')->where('fenlbm', '当前状态')->where('daimmc', '接收')->value('daimid');
            $map['shbz'] = '1';
            $map['jiedfzr'] = session('admin_auth.aid');
            $map['zhuangt'] = $zhuangt;
            $buglist = Db::name('dl_buglist')->where($map)->order('fabsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        } elseif ($renwu == '3') {  //我参与过的任务
            /*$zhuangt = Db::name('dl_erjidaima')->where('fenlbm', '当前状态')->where('daimmc', '未接收')->value('daimid');
            $map['shbz'] = '1';
            $map['lzry'] = session('admin_auth.aid');
            $map['zhuangt'] = $zhuangt;
            $buglist = Db::name('dl_buglist')->where($map)->order('fabsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);*/
            //查询出当前用户的所有参与任务
            $renwid = Db::name('dl_buglist_zirw')->where('fuzr',session('admin_auth.aid'))->column('distinct renwid');
            $buglist = Db::name('dl_buglist')->where('renwid','in',$renwid)->order('fabsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        } elseif ($update == '6') {  //更新时间排序
            //如果选择了关闭任务，就显示关闭任务的记录
            if ($biaoz == 26) {
                unset($map['rw_state']);
                $buglist = Db::name('dl_buglist')->where($map)->where('rw_state', 26)->order('rw_state asc')->order('uptime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
            } else {
                $buglist = Db::name('dl_buglist')->where($map)->order('uptime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
            }
        } elseif ($yxjb == '1') {
            $buglist = Db::name('dl_buglist')->where($map)->order('youxjb desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        } elseif ($fabsj == '1') {
            $buglist = Db::name('dl_buglist')->where($map)->order('fabsj asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        } elseif ($jiedyjwcsj == '1') {
            $buglist = Db::name('dl_buglist')->where($map)->order('jiedyjwcsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        }elseif ($yaoqwcsj == '1') {
            $buglist = Db::name('dl_buglist')->where($map)->order('yaoqwcsj asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        }elseif ($cpmc == '1') {
            $buglist = Db::name('dl_buglist')->where($map)->order('chanp_id asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        }elseif ($xmmc == '1') {
            $buglist = Db::name('dl_buglist')->where($map)->order('xiangm_id desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        }elseif ($jiedfzr1 == '1') {
            $buglist = Db::name('dl_buglist')->where($map)->order('jiedfzr asc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        }else {
            //如果选择了关闭任务，就显示关闭任务的记录
            if ($biaoz == 26) {
                unset($map['rw_state']);
                $buglist = Db::name('dl_buglist')->where($map)->where('rw_state', 26)->order('rw_state asc')->order('uptime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
            }
            if ($biaoz == '0') {
                $buglist = Db::name('dl_buglist')->order('uptime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
            } else {
                $buglist = Db::name('dl_buglist')->where($map)->order('rw_state asc')->order('fabsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
            }
        }
        /*if($renwu == '0'){  //我的所有任务
            $map['shbz'] = '1';
            $map['jiedfzr'] = session('admin_auth.aid');
            $buglist = Db::name('dl_buglist')->where($map)->order('fabsj desc')->select();
        }elseif($renwu == '1'){  //我未接收的任务
            $zhuangt= Db::name('dl_erjidaima')->where('fenlbm','当前状态')->where('daimmc','未接收')->value('daimid'); //更新任务主表的当前状态，为未接收
            $map['shbz'] = '1';
            $map['jiedfzr'] = session('admin_auth.aid');
            $map['zhuangt'] = $zhuangt;
            $buglist = Db::name('dl_buglist')->where($map)->order('fabsj desc')->select();

            //排序
            if($renwu == '1' || $px == '4' || $px == '5' || $px == '6' || $px == '7' || $px == '8'){
                common_px($px,$map,$biaoz);
            }
        }elseif($renwu == '2'){  //我已接收的任务
            $zhuangt= Db::name('dl_erjidaima')->where('fenlbm','当前状态')->where('daimmc','接收')->value('daimid');
            $map['shbz'] = '1';
            $map['jiedfzr'] = session('admin_auth.aid');
            $map['zhuangt'] = $zhuangt;
            $buglist = Db::name('dl_buglist')->where($map)->order('fabsj desc')->select();
        }elseif($renwu == '3'){  //我分配的任务，即为我未接收的任务
            $zhuangt= Db::name('dl_erjidaima')->where('fenlbm','当前状态')->where('daimmc','未接收')->value('daimid');
            $map['shbz'] = '1';
            $map['lzry'] = session('admin_auth.aid');
            $map['zhuangt'] = $zhuangt;
            $buglist = Db::name('dl_buglist')->where($map)->order('fabsj desc')->select();
        }elseif($px == '6'){
            //如果选择了关闭任务，就显示关闭任务的记录
            if($biaoz == 26){
                unset($map['rw_state']);
                $buglist = Db::name('dl_buglist')->where($map)->where('rw_state',26)->order('rw_state asc')->order('uptime desc')->select();
            }else{
                $buglist = Db::name('dl_buglist')->where($map)->order('uptime desc')->select();
            }
        }else{
            //如果选择了关闭任务，就显示关闭任务的记录
            if($biaoz == 26){
                unset($map['rw_state']);
                $buglist = Db::name('dl_buglist')->where($map)->where('rw_state',26)->order('rw_state asc')->order('uptime desc')->select();
            }else{
                $buglist = Db::name('dl_buglist')->where($map)->order('rw_state asc')->order('fabsj desc')->select();
            }
        }
        // 把PHP数组转成JSON字符串
        $json_string = json_encode($buglist);
        // 写入文件
        file_put_contents(ABS_PUBLIC_URL.'test1.json', $json_string);*/
        //显示总任务数目
        unset($map['rw_state']);
        $sum = Db::name('dl_buglist')->where($map)->where('shbz', '1')->count();
        $this->assign('sum', $sum);
        //显示解决任务数目
        $jj = Db::name('dl_buglist')->where($map)->where('rw_state', 26)->count();
        $this->assign('jj', $jj);
        //显示未解决任务数目
        $wjj = Db::name('dl_buglist')->where($map)->where('rw_state', 0)->where('shbz', '1')->count();
        $this->assign('wjj', $wjj);

        $show = $buglist->render();
        $show = preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)", "<a href='javascript:ajax_page($1);'>$2</a>", $show);
        $this->assign('page', $show);
        $this->assign('buglist', $buglist);
        if (request()->isAjax()) {
            return $this->fetch('ajax_issue_list',['sum'=>$sum,'jj'=>$jj,'wjj'=>$wjj]);
        } else {
            return $this->fetch();
        }
    }

    //任务单独显示
    public function issue_one()
    {
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $renwid = input('param.renwid');
        $this->assign('renwid', $renwid);

        //处理并发任务
        $uptime = Db::name('dl_buglist')->where('renwid', $renwid)->value('uptime');
        $admin_id = session('admin_auth.aid');
        $fl = Db::name('dl_session')->where('admin_id', $admin_id)->value('admin_id');
        if ($fl) {
            Db::name('dl_session')->update(['admin_id' => $admin_id, 'uptime' => $uptime]);
        } else { //如果还没有产生缓存，则生成一条数据
            Db::name('dl_session')->insert(['admin_id' => $admin_id, 'uptime' => $uptime]);
        }

        //显示当前状态
        $zhangt_list = DB::name('dl_erjidaima')->where('youxbz', 1)->where('fenlbm', '当前状态')->select();
        $this->assign('zhangt_list', $zhangt_list);
        //显示当前阶段
        $jied_list = DB::name('dl_erjidaima')->where('youxbz', 1)->where('fenlbm', '当前阶段')->select();
        $this->assign('jied_list', $jied_list);
        //显示任务
        $bug_list = DB::name('dl_buglist')->where('renwid', $renwid)->find();
        $this->assign('bug_list', $bug_list);
        //显示该任务的所有附件下载
        $file_list = DB::name('plug_files')->where('buglist_id', $renwid)->select();
        $this->assign('file_list', $file_list);
        //显示该任务的所有子任务回复
        $renwnr = Db::name('dl_buglist_zirw')->where('renwid', $renwid)->order('lzsj desc')->select();
        $this->assign('renwnr', $renwnr);
        //显示变更记录
        /*$bg = Db::name('dl_bgjl')->where('renwid',$renwid)->order('bg_time desc')->select();
        $this->assign('bg',$bg);*/

        $list = Db::name('dl_buglist_zirw')->where('renwid', $renwid)->order('zirwid desc')->limit('2,2')->select();  //获取最新子任务的倒数第二条记录来获取上一个阶段的负责人
        $this->assign('sjdfzr', '');
        if (!empty($list)) {
            $this->assign('sjdfzr', $list[0]['fuzr']);
        }
        return $this->fetch();
    }

    //接收任务操作
    public function issue_jieshou()
    {
        $data = input('param.');
        $admin_id = session('admin_auth.aid');
        //处理并发任务
        $uptime = Db::name('dl_session')->where('admin_id', $admin_id)->value('uptime');
        $uptime_log = Db::name('dl_buglist')->where('renwid', $data['renwid'])->value('uptime');
        //var_dump($uptime==$uptime_log);exit;
        if ($uptime != $uptime_log) {
            $this->success("此任务已被操作过，请刷新后重新更新");
        }

        //[jied] => 13 [jiedyjwcsj] => 2017-04-13 [renwnr] =>
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $shijian = date('Y-m-d H:i:s', time());
        //当前状态为接收、子任务类型为任务。子任务标志为未完成，子任务状态开启
        $zhuangt = Db::name('dl_erjidaima')->where('fenlbm', '当前状态')->where('daimmc', '接收')->value('daimid');
        $zirwlx = Db::name('dl_erjidaima')->where('fenlbm', '子任务类型')->where('daimmc', '任务')->value('daimid');
        $zirwbz = Db::name('dl_erjidaima')->where('fenlbm', '子任务标志')->where('daimmc', '未完成')->value('daimid');
        //查出当前阶段负责人
        $jiedfzr = Db::name('dl_buglist')->where('renwid', $data['renwid'])->value('jiedfzr');
        $arr = [
            'renwid' => $data['renwid'],
            'jiedfzr' => $data['jiedfzr'],
            'biaoz' => 27, //一旦接收就变为任务接收
            'jiedyjwcsj' => $data['jiedyjwcsj'],   //本阶段预计完成时间
            'jied' => $data['jied'],   //当前阶段
            'zhuangt' => $zhuangt,   //当前状态
            'uptime' => $shijian,
            'lzry' => $jiedfzr, //更新阶段负责人员
            //'yaoqwcsj' => $data['yaoqwcsj'], //更新完成时间
        ];
        //如果选择了任务搁置，biaoz就变为任务搁置
        /*if($data['gezhi'] == 1){ //1为选择任务搁置
            $arr['biaoz'] = 25;
        }*/
        //查询此任务是否已经为接收状态
        $zt = Db::name('dl_buglist')->where('renwid', $data['renwid'])->value('zhuangt');
        $co = Db::name('dl_buglist')->where('renwid',$data['renwid'])->find();
        if ($zt == 12) { //12为任务已经接收
            //只更新任务，不产生子记录
            //print_r($_POST);exit;
            $list = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->order('zirwid desc')->limit(1)->find();  //此任务最新的子任务信息
            //更新子任务的预计完成时间
            Db::name('dl_buglist_zirw')->where('zirwid',$list['zirwid'])->update(['jiedyjwcsj'=>$data['jiedyjwcsj'],'fuzr'=>$data['jiedfzr']]);
            Db::name('dl_buglist')->update($arr);
            $this->success("任务更新成功");
        } else {
            if($co['rw_state'] == 26){ //如果为已经关闭的任务，修改状态为0
                //重启任务、删除关闭状态
                $arr['rw_state'] = '0';
            }
            //更新任务主表
            Db::name('dl_buglist')->update($arr);
            //判断该任务是否为第一次未接收，如果第一次未接收必须生成一条新纪录，反之不用
            $fl = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->find();
            $list = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->order('zirwid desc')->limit(1)->find();  //此任务最新的子任务信息
            $arr1 = [
                'zirwlx' => $zirwlx,
                'biaoz' => $zirwbz,  //标志为未完成
                'youxbz' => 1,
                'renwid' => $data['renwid'],
                'jiedfzr' => $data['jiedfzr'],
                'jiedyjwcsj' => $data['jiedyjwcsj'],   //本阶段预计完成时间
                'jied' => $data['jied'],   //当前阶段
                'zhuangt' => $zhuangt,   //当前状态
                'youxjb' => $data['youxjb'],   //有效级别
                'jiessj' => $shijian,
                'lzsj' => $shijian,
                'lzry' => session('admin_auth.aid'),
                'fuzr' => $data['jiedfzr'],
                'gzbz' => '0',
                'bgbz' => '0',
                'zirenwnr' => '接收任务操作记录',
            ];
            //产生子任务信息
            $p = Db::name('dl_buglist_zirw')->insertGetId($arr1);
            if($p){
                $this->success("任务接收成功");
            }else{
                $this->success("任务接收失败");
            }

            /*if ($fl) {
                //如果是第一次未接收，就更新有效状态
                unset($arr1['renwid']);  //删掉任务id
                Db::name('dl_buglist_zirw')->where('zirwid', $list['zirwid'])->update($arr1);
                $this->success("任务接收成功");
            } else {
                //已经有过未接收了，产生子任务信息
                Db::name('dl_buglist_zirw')->insert($arr1);
                $this->success("任务接收成功");
            }*/
        }
    }

    //分配任务操作
    public function issue_fenpei()
    {
        //print_r($_POST['hour'] + $_POST['fen']/60);exit;
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $shijian = date('Y-m-d H:i:s', time());
        $data = input('param.');
        //print_r($data);exit;
        $admin_id = session('admin_auth.aid');
        //处理并发任务
        $uptime = Db::name('dl_session')->where('admin_id', $admin_id)->value('uptime');
        $uptime_log = Db::name('dl_buglist')->where('renwid', $data['renwid'])->value('uptime');
        //var_dump($uptime==$uptime_log);exit;
        if ($uptime != $uptime_log) {
            $this->success("此任务已被操作过，请刷新后重新更新");
        }

        $zirwlx1 = Db::name('dl_erjidaima')->where('fenlbm', '子任务类型')->where('daimmc', '未接收')->value('daimid');
        //接收子任务标志，是否完成或者未完成
        $zirwbz = $data['zirwbz'];
        //$zirwbz = Db::name('dl_erjidaima')->where('fenlbm', '子任务标志')->where('daimmc', '未完成')->value('daimid');
        //$zirwbz1 = Db::name('dl_erjidaima')->where('fenlbm', '子任务标志')->where('daimmc', '完成')->value('daimid');
        $list = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->order('zirwid desc')->limit(1)->find();  //此任务最新的子任务信息
        //查询此任务是否已经为接收状态
        $zt = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->where('youxbz', '1')->value('zhuangt');
        $co = Db::name('dl_buglist')->where('renwid',$data['renwid'])->find();
        $arr = [
            'renwid' => $data['renwid'],
            'jiedfzr' => $data['jiedfzr'],
            'zhuangt' => 11, //一旦未接收,状态就变为未接收
            'biaoz' => 17, //标志变为任务分配
            'uptime' => $shijian,
            'lzry' => session('admin_auth.aid'), //未接收人员
            //'yaoqwcsj' => $data['yaoqwcsj'], //更新完成时间
        ];
        //做个判断，如果状态为任务进行中，则改为任务分配，其它的不变
        /*if($data['ztbz'] == 27){ //27为任务进行中
            $arr['biaoz'] = 17;
        }else{
            $arr['biaoz'] = $data['ztbz'];
        }*/

        //更新主表
        if($co['rw_state'] == 26){ //如果为已经关闭的任务，修改状态为0
            //重启任务、删除关闭状态
            $arr['rw_state'] = '0';
        }
        Db::name('dl_buglist')->update($arr);

        //使用正则去获取上传文件的相对路径和名称，然后判断plug_file里面的文件路径是否匹配，如果匹配就更新plug_file里面的buglist_id.
        $file_name = $data['renwnr'];
        $patt = '/\/data\/[a-zA-z0-9-\/\.]+/';
        preg_match_all($patt, $file_name, $res);  //得到了上传文件的相对路径
        $file_list = DB::name('plug_files')->where('up_user', session('admin_auth.admin_realname'))->select();
        //循环判断是否和plug_file里面的路径一样
        foreach ($file_list as $k => $v) {
            for ($i = 0; $i < count($res[0]); $i++) {
                if ($res['0'][$i] === $v['path']) {
                    DB::name('plug_files')->where('id', $v['id'])->setField('buglist_id', $data['renwid']);
                    break;
                }
            }
        }

        if ($zt == 12) { //12为任务已经接收
            //已接收的任务未接收
            $arr1['zirwlx'] = $zirwlx1; //子任务类型为未接收
            $arr1['youxjb'] = $data['youxjb'];//任务级别
            $arr1['lzsj'] = $shijian; //未接收时间
            $arr1['lzry'] = session('admin_auth.aid'); //此操作人员,未接收人员
            $arr1['jiedfzr'] = $data['jiedfzr'];  //当前阶段负责人
            $arr1['renwid'] = $data['renwid']; //任务id
            $arr1['biaoz'] = $zirwbz; //子任务标志
            $arr1['jiedsjwcsj'] = $shijian;
            $arr1['youxbz'] = '0';
            $arr1['zirenwnr'] = $data['renwnr']; //子任务内容
            $arr1['bgbz'] = '0'; //变更标志
            $arr1['gzbz'] = '0';

            //更新子任务完成时间和工时(分钟)
            /*$date = strtotime($shijian) - strtotime($list['jiessj']);
            $date = $date % 86400;
            $arr1['jiedgs'] = $date / 3600;*/
            $arr1['jiedgs'] = $data['hour'] + $data['fen']/60; //工时
            //更新之前的youxbz为0
            Db::name('dl_buglist_zirw')->where('zirwid', $list['zirwid'])->update(['youxbz'=>'0']);
            $d = Db::name('dl_buglist_zirw')->where('zirwid', $list['zirwid'])->find();
            unset($d['zirwid']);
            $d['youxbz'] = '0'; //分配任务有效标志一定为0
            $id = Db::name('dl_buglist_zirw')->insertGetId($d);
            Db::name('dl_buglist_zirw')->where('zirwid', $id)->update($arr1);

            //生成新的子任务记录
            unset($arr1['jiedgs']);  //删掉工时
            unset($arr1['jiedsjwcsj']);  //删掉实际完成时间
            unset($arr1['zirenwnr']);  //删掉子任务内容
            unset($arr1['youxbz']);  //删掉子任务内容
            unset($arr1['lzsj']);  //删掉未接收时间
            unset($arr1['jiedfzr']);  //删掉未接收时间
            unset($arr1['lzry']);  //删掉未接收时间
            unset($arr1['bgbz']);  //删掉未接收时间
            unset($arr1['gzbz']);  //删掉未接收时间
            Db::name('dl_buglist_zirw')->insert($arr1);

            //任务分配，发送邮件给当前阶段负责人
            $buglist = Db::name('dl_buglist')->where('renwid', $data['renwid'])->find(); //获取当前任务信息
            $jiedfzr = Db::name('admin')->where('admin_id', $data['jiedfzr'])->find();//获取当前阶段人员的信息
            $fabry = Db::name('admin')->where('admin_id', $data['fabry'])->find();//获取发布人员的信息
            $caozry = Db::name('admin')->where('admin_id', session('admin_auth.aid'))->value('admin_realname');
            $bt = '#' . $buglist['renwid'] . ' ' . $buglist['renwbt'] . ' 任务分配提醒';
            $nr = '';
            $nr .= 'hi ' . $jiedfzr['admin_realname'] . ',你有一个新任务(任务)分配提示 ' . '<br/>';
            $nr .= '任务(任务)名称：' . $buglist['renwbt'] . '<br/>';
            $nr .= '内容：' . $buglist['renwnr'] . '<br/>';
            $nr .= '分配内容：' . $data['renwnr'] . '<br/>';
            $nr .= '任务链接：' . '<a href=' . ADMIN_URL . 'task/issue_one/renwid/' . $buglist['renwid'] . '>点我</a>' . '<br/>';
            $nr .= '当前阶段负责人：' . $jiedfzr['admin_realname'] . '<br/>';
            $nr .= '分配人员：' . $caozry . '<br/>';
            $nr .= '任务发布人员：' . $fabry['admin_realname'] . '<br/>';
            $nr .= '温馨提示：此邮件为提醒邮件，请勿直接回复，谢谢合作！';

            sendMail($jiedfzr['admin_email'], $bt, $nr);  //发送邮件给阶段负责人

            $this->success("任务分配成功");
        } else {
            //print_r($data);exit;
            //任务未接收分配
            $arr1['zirwlx'] = $zirwlx1;
            $arr1['youxjb'] = $data['youxjb'];//任务级别
            $arr1['lzry'] = session('admin_auth.aid'); //此操作人员
            $arr1['jiedfzr'] = $data['jiedfzr'];  //当前阶段负责人
            $arr1['renwid'] = $data['renwid']; //任务id
            $arr1['lzsj'] = $shijian; //未接收时间
            $arr1['biaoz'] = $zirwbz;
            $arr1['jiedsjwcsj'] = $shijian; //接收时间和实际完成时间一样，即工时为0
            $arr1['youxbz'] = '0';
            $arr1['fuzr'] = $data['jiedfzr'];
            $arr1['zirenwnr'] = $data['renwnr']; //子任务内容
            $arr1['bgbz'] = '0'; //变更标志
            $arr1['gzbz'] = '0';

            //更新子任务完成时间和工时
            $gs = Db::name('dl_buglist_zirw')->where('zirwid', $list['zirwid'])->value('jiedgs');
            if (empty($gs)) {
                $arr1['jiedgs'] = 0;  //工时为0
            }
            $id = Db::name('dl_buglist_zirw')->insertGetId($arr1);
            $count = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->count();
            if ($count > 0) {
                Db::name('dl_buglist_zirw')->where('zirwid', $id)->update($arr1);
            }
            /*//查看当前是否已经有了子记录，如果有了就更新，没有就插入
            $count = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->count();
            if ($count > 0) {
                Db::name('dl_buglist_zirw')->where('zirwid', $list['zirwid'])->update($arr1);
            } else {
                Db::name('dl_buglist_zirw')->insert($arr1);
            }*/

            //生成新的子任务记录
            unset($arr1['jiedgs']);  //删掉工时
            unset($arr1['jiedsjwcsj']);  //删掉实际完成时间
            unset($arr1['zirenwnr']);  //删掉子任务内容
            unset($arr1['jiessj']);  //删掉接收时间
            unset($arr1['youxbz']);  //删掉接收时间
            unset($arr1['lzsj']);  //删掉未接收时间
            unset($arr1['lzry']);
            unset($arr1['bgbz']);
            unset($arr1['fuzr']);
            unset($arr1['gzbz']);
            Db::name('dl_buglist_zirw')->insert($arr1);

            /*任务分配，发送邮件给当前阶段负责人*/
            $buglist = Db::name('dl_buglist')->where('renwid', $data['renwid'])->find(); //获取当前任务信息
            $jiedfzr = Db::name('admin')->where('admin_id', $data['jiedfzr'])->find();//获取当前阶段人员的信息
            $fabry = Db::name('admin')->where('admin_realname', $data['fabry'])->find();//获取发布人员的信息
            $caozry = Db::name('admin')->where('admin_id', session('admin_auth.aid'))->value('admin_realname');
            $bt = '#' . $buglist['renwid'] . ' ' . $buglist['renwbt'] . ' 任务分配提醒';
            $nr = '';
            $nr .= 'hi ' . $jiedfzr['admin_realname'] . ',你有一个新任务(任务)分配提示 ' . '<br/>';
            $nr .= '任务(任务)名称：' . $buglist['renwbt'] . '<br/>';
            $nr .= '内容：' . $buglist['renwnr'] . '<br/>';
            $nr .= '分配内容：' . $data['renwnr'] . '<br/>';
            $nr .= '任务链接：' . '<a href=' . ADMIN_URL . 'task/issue_one/renwid/' . $buglist['renwid'] . '>点我</a>' . '<br/>';
            $nr .= '当前阶段负责人：' . $jiedfzr['admin_realname'] . '<br/>';
            $nr .= '分配人员：' . $caozry . '<br/>';
            $nr .= '任务发布人员：' . $fabry['admin_realname'] . '<br/>';
            $nr .= '温馨提示：此邮件为提醒邮件，请勿直接回复，谢谢合作！';

            sendMail($jiedfzr['admin_email'], $bt, $nr);  //发送邮件给阶段负责人

            $this->success("任务分配成功");
        }
    }

    //变更任务操作
    public function issue_liuzhuan()
    {
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $data = input('param.');
        $admin_id = session('admin_auth.aid');
        //处理并发任务
        $uptime = Db::name('dl_session')->where('admin_id', $admin_id)->value('uptime');
        $uptime_log = Db::name('dl_buglist')->where('renwid', $data['renwid'])->value('uptime');
        //var_dump($uptime==$uptime_log);exit;
        if ($uptime != $uptime_log) {
            $this->success("此任务已被操作过，请刷新后重新更新");
        }

        //[jied] => 13 [jiedyjwcsj] => 2017-04-13 [renwnr] =>
        $shijian = date('Y-m-d H:i:s', time());
        //当前状态为接收、子任务类型为任务。子任务标志为未完成，子任务状态开启
        $zhuangt = Db::name('dl_erjidaima')->where('fenlbm', '当前状态')->where('daimmc', '接收')->value('daimid');
        $zhuangt1 = Db::name('dl_erjidaima')->where('fenlbm', '当前状态')->where('daimmc', '未接收')->value('daimid');
        $list = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->order('zirwid desc')->limit(1)->find();  //此任务最新的子任务信息
        //查询此任务是否已经为接收状态
        $zt = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->where('youxbz', '1')->value('zhuangt');
        $co = Db::name('dl_buglist')->where('renwid',$data['renwid'])->find();
        $arr = [
            'renwid' => $data['renwid'],
            //'jiedfzr' => $data['jiedfzr'],
            'biaoz' => 42, //一旦变更就为任务变更
            //'jiedyjwcsj' => $data['jiedyjwcsj'],   //本阶段预计完成时间
            //'jied' => $data['jied'],   //当前阶段
            //'zhuangt' => $zhuangt,   //当前状态
            'uptime' => $shijian,
            'youxjb' => $data['youxjb'],//优先级别
            'renwlx' => $data['renwlx'],//任务类型
            'lzry' => session('admin_auth.aid'), //更新操作人员
            'yaoqwcsj' => $data['yaoqwcsj'], //更新完成时间
        ];

        //使用正则去获取上传文件的相对路径和名称，然后判断plug_file里面的文件路径是否匹配，如果匹配就更新plug_file里面的buglist_id.
        $file_name = $data['renwnr'];
        $patt = '/\/data\/[a-zA-z0-9-\/\.]+/';
        preg_match_all($patt, $file_name, $res);  //得到了上传文件的相对路径
        $file_list = DB::name('plug_files')->where('up_user', session('admin_auth.admin_realname'))->select();
        //循环判断是否和plug_file里面的路径一样
        foreach ($file_list as $k => $v) {
            for ($i = 0; $i < count($res[0]); $i++) {
                if ($res['0'][$i] === $v['path']) {
                    DB::name('plug_files')->where('id', $v['id'])->setField('buglist_id', $data['renwid']);
                    break;
                }
            }
        }

        if ($zt == 12) { //12为任务已经接收
            //更新子任务的有效标志为0
            $arr2['youxbz'] = '0';
            $arr2['lzsj'] = $list['lzsj'];
            Db::name('dl_buglist_zirw')->where('zirwid',$list['zirwid'])->update($arr2);
            $arr1['renwid'] = $list['renwid'];
            $arr1['zirenwnr'] = $data['renwnr'];
            $arr1['jiedfzr'] = $list['jiedfzr'];
            $arr1['zirwlx'] = 23; //子任务类型为任务
            $arr1['youxjb'] = $list['youxjb'];
            $arr1['fuzr'] = $list['fuzr'];
            $arr1['jied'] = $list['jied'];
            $arr1['jiedyjwcsj'] = $list['jiedyjwcsj'];
            $arr1['jiedsjwcsj'] = $list['jiedsjwcsj'];
            $arr1['biaoz'] = $list['biaoz'];
            $arr1['jiedgs'] = '0';
            $arr1['jiessj'] = $list['jiessj'];
            $arr1['zhuangt'] = $list['zhuangt'];
            $arr1['youxbz'] = 1;
            $arr1['lzsj'] = $shijian;
            $arr1['lzry'] = session('admin_auth.aid');
            $arr1['bgbz'] = '1'; //变更标志
            $arr1['gzbz'] = '0';
            //print_r($list);exit;
            Db::name('dl_buglist_zirw')->insert($arr1);
            //更新任务信息,接收了就变为任务接收状态
            $arr['zhuangt'] = $zhuangt;
            $fl = Db::name('dl_buglist')->update($arr);
            if($fl){
                $this->success("任务变更成功");
            }else{
                $this->success("任务变更失败");
            }
        } else { //如果没有接收随便更新
            $jiedfzr = Db::name('dl_buglist')->where('renwid',$data['renwid'])->value('jiedfzr');
            $arr1['renwid'] = $data['renwid'];
            $arr1['zirenwnr'] = $data['renwnr'];
            $arr1['lzsj'] = $shijian;
            $arr1['youxjb'] = $data['youxjb'];
            $arr1['jiedfzr'] = $jiedfzr;
            $arr1['zirwlx'] = 22;
            $arr1['fuzr'] = $data['jiedfzr'];
            $arr1['jiedgs'] = '0';
            $arr1['lzry'] = session('admin_auth.aid');
            $arr1['bgbz'] = '1'; //变更标志
            $arr1['gzbz'] = '0';
            if($co['rw_state'] == 26){ //如果为已经关闭的任务，修改状态为0
                //重启任务、删除关闭状态
                $arr['rw_state'] = '0';
                $arr['zhuangt'] = $zhuangt1;   //当前状态
            }
            Db::name('dl_buglist_zirw')->insert($arr1);
            //更新任务信息
            $fl = Db::name('dl_buglist')->update($arr);
            if($fl){
                $this->success("任务变更成功");
            }else{
                $this->success("任务变更失败");
            }
        }
    }

    //搁置任务操作
    public function issue_gezhi()
    {
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $shijian = date('Y-m-d H:i:s', time());
        $data = input('param.');
        $arr = [
            'renwid' => $data['renwid'],
            'uptime' => $shijian,
            'biaoz' => '25',
        ];
        $admin_id = session('admin_auth.aid');
        //处理并发任务
        $uptime = Db::name('dl_session')->where('admin_id', $admin_id)->value('uptime');
        $uptime_log = Db::name('dl_buglist')->where('renwid', $data['renwid'])->value('uptime');
        //var_dump($uptime==$uptime_log);exit;
        if ($uptime != $uptime_log) {
            $this->success("此任务已被操作过，请刷新后重新更新");
        }
        //$zirwlx = Db::name('dl_erjidaima')->where('fenlbm', '子任务类型')->where('daimmc', '任务')->value('daimid');
        //$zirwbz = Db::name('dl_erjidaima')->where('fenlbm', '子任务标志')->where('daimmc', '未完成')->value('daimid');
        $list = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->order('zirwid desc')->limit(1)->find();  //此任务最新的子任务信息
        //当前状态为接收、子任务类型为任务。子任务标志为未完成，子任务状态开启
        $zhuangt = Db::name('dl_erjidaima')->where('fenlbm', '当前状态')->where('daimmc', '接收')->value('daimid');
        //查询此任务是否已经为接收状态
        $zt = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->where('youxbz', '1')->value('zhuangt');
        $co = Db::name('dl_buglist')->where('renwid',$data['renwid'])->find();
        if ($zt == 12) { //12为任务已经接收
            $arr2 = array();
            $arr1 = array();
            //更新子任务的有效标志为0
            $arr2['youxbz'] = '0';
            $arr2['lzsj'] = $list['lzsj'];
            //$arr2['jiedgs'] = $data['hour'] + $data['fen']/60; //工时
            //print_r($arr2);exit;
            Db::name('dl_buglist_zirw')->where('zirwid',$list['zirwid'])->update($arr2);
            $arr1['renwid'] = $list['renwid'];
            $arr1['zirenwnr'] = $data['renwnr'];
            $arr1['jiedfzr'] = $list['jiedfzr'];
            $arr1['zirwlx'] = 23; //子任务类型为任务
            $arr1['youxjb'] = $list['youxjb'];
            $arr1['fuzr'] = $list['fuzr'];
            $arr1['jied'] = $list['jied'];
            $arr1['jiedyjwcsj'] = $list['jiedyjwcsj'];
            $arr1['jiedsjwcsj'] = $list['jiedsjwcsj'];
            $arr1['biaoz'] = $list['biaoz'];
            $arr1['jiedgs'] = $data['hour'] + $data['fen']/60; //工时
            $arr1['jiessj'] = $list['jiessj'];
            $arr1['zhuangt'] = $list['zhuangt'];
            $arr1['youxbz'] = 1;
            $arr1['bgbz'] = '0'; //变更标志
            $arr1['gzbz'] = '1';
            $arr1['lzsj'] = $shijian;
            $arr1['lzry'] = session('admin_auth.aid');
            //print_r($list);exit;
            Db::name('dl_buglist_zirw')->insert($arr1);
            //更新任务信息,接收了就变为任务接收状态
            $arr['zhuangt'] = $zhuangt;
            $fl = Db::name('dl_buglist')->update($arr);
        } else { //如果没有接收随便搁置
            $jiedfzr = Db::name('dl_buglist')->where('renwid',$data['renwid'])->value('jiedfzr');
            $arr1['renwid'] = $data['renwid'];
            $arr1['zirenwnr'] = $data['renwnr'];
            $arr1['lzsj'] = $shijian;
            $arr1['youxjb'] = $data['youxjb'];
            $arr1['jiedfzr'] = $jiedfzr;
            $arr1['zirwlx'] = 22;
            $arr1['youxbz'] = 0;
            $arr1['fuzr'] = $data['jiedfzr'];
            $arr1['lzry'] = session('admin_auth.aid');
            $arr1['jiedgs'] = $data['hour'] + $data['fen']/60; //工时
            $arr1['bgbz'] = '0'; //变更标志
            $arr1['gzbz'] = '1';
            if($co['rw_state'] == 26){ //如果为已经关闭的任务，修改状态为0
                //重启任务、删除关闭状态
                $arr['rw_state'] = '0';
            }
            Db::name('dl_buglist_zirw')->insert($arr1);
            //更新任务信息
            $fl = Db::name('dl_buglist')->update($arr);
        }

        //任务搁置，发送邮件给当发布人员
        $buglist = Db::name('dl_buglist')->where('renwid', $data['renwid'])->find(); //获取当前任务信息
        $jiedfzr = Db::name('admin')->where('admin_id', $data['jiedfzr'])->find();//获取当前阶段人员的信息
        $fabry = Db::name('admin')->where('admin_id', $data['fabry'])->find();//获取发布人员的信息
        $caozry = Db::name('admin')->where('admin_id', session('admin_auth.aid'))->value('admin_realname');
        $bt = '#' . $buglist['renwid'] . ' ' . $buglist['renwbt'] . ' 任务搁置提醒';
        $nr = '';
        $nr .= 'hi ' . $fabry['admin_realname'] . ',你发布的任务已经被搁置 ' . '<br/>';
        $nr .= '任务(任务)名称：' . $buglist['renwbt'] . '<br/>';
        $nr .= '内容：' . $buglist['renwnr'] . '<br/>';
        $nr .= '搁置内容：' . $data['renwnr'] . '<br/>';
        $nr .= '任务链接：' . '<a href=' . ADMIN_URL . 'task/issue_one/renwid/' . $buglist['renwid'] . '>点我</a>' . '<br/>';
        $nr .= '当前阶段负责人：' . $jiedfzr['admin_realname'] . '<br/>';
        $nr .= '搁置人员：' . $caozry . '<br/>';
        $nr .= '任务发布人员：' . $fabry['admin_realname'] . '<br/>';
        $nr .= '温馨提示：此邮件为提醒邮件，请勿直接回复，谢谢合作！';

        sendMail($fabry['admin_email'], $bt, $nr);  //发送邮件给阶段负责人

        if ($fl) {
            $this->success("任务搁置成功");
        } else {
            $this->success("任务搁置失败，请把bug反馈给管理员！");
        }
    }

    //任务关闭操作
    public function issue_close()
    {
        $data = input('param.');
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $shijian = date('Y-m-d H:i:s', time());
        $guanbirw = Db::name('dl_erjidaima')->where('fenlbm', '状态标志')->where('daimmc', '关闭任务')->value('daimid');
        $zirwlx = Db::name('dl_erjidaima')->where('fenlbm', '子任务类型')->where('daimmc', '任务')->value('daimid');
        $zirwlx1 = Db::name('dl_erjidaima')->where('fenlbm', '子任务类型')->where('daimmc', '未接收')->value('daimid');
        //$zirwbz = Db::name('dl_erjidaima')->where('fenlbm', '子任务标志')->where('daimmc', '未完成')->value('daimid');
        $zirwbz1 = Db::name('dl_erjidaima')->where('fenlbm', '子任务标志')->where('daimmc', '完成')->value('daimid');
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样

        $admin_id = session('admin_auth.aid');
        //处理并发任务
        $uptime = Db::name('dl_session')->where('admin_id', $admin_id)->value('uptime');
        $uptime_log = Db::name('dl_buglist')->where('renwid', $data['renwid'])->value('uptime');
        //var_dump($uptime==$uptime_log);exit;
        if ($uptime != $uptime_log) {
            $this->success("此任务已被操作过，请刷新后重新更新");
        }

        //查询此任务是否已经为接收状态
        $zt = Db::name('dl_buglist')->where('renwid', $data['renwid'])->value('zhuangt');
        $jiedfzr = Db::name('dl_buglist')->where('renwid', $data['renwid'])->value('jiedfzr'); //获取阶段负责人
        $list = Db::name('dl_buglist_zirw')->where('renwid', $data['renwid'])->order('zirwid desc')->limit(1)->find();  //此任务最新的子任务信息
        $arr = [
            'renwid' => $data['renwid'],
            'jiedfzr' => $data['jiedfzr'],
            'biaoz' => 26,  //状态为关闭任务
            'uptime' => $shijian,
        ];
        $arr['rw_state'] = $guanbirw;
        $arr['zhuangt'] = '';
        //$arr['jied'] = '';
        $arr['beizhu'] = $data['renwnr'];
        $arr['gbry_id'] = session('admin_auth.aid');  //关闭人id
        //print_r($arr);exit;
        //使用正则去获取上传文件的相对路径和名称，然后判断plug_file里面的文件路径是否匹配，如果匹配就更新plug_file里面的buglist_id.
        $file_name = $data['renwnr'];
        $patt = '/\/data\/[a-zA-z0-9-\/\.]+/';
        preg_match_all($patt, $file_name, $res);  //得到了上传文件的相对路径
        $file_list = DB::name('plug_files')->where('up_user', session('admin_auth.admin_realname'))->select();
        //循环判断是否和plug_file里面的路径一样
        foreach ($file_list as $k => $v) {
            for ($i = 0; $i < count($res[0]); $i++) {
                if ($res['0'][$i] === $v['path']) {
                    DB::name('plug_files')->where('id', $v['id'])->setField('buglist_id', $data['renwid']);
                    break;
                }
            }
        }
        //更新任务主表
        Db::name('dl_buglist')->update($arr);

        if ($zt == 12) { //12为任务已经接收
            $arr1['zirwlx'] = $zirwlx; //子任务类型为任务
            $arr1['renwid'] = $data['renwid']; //任务id
            $arr1['lzsj'] = $shijian; //未接收时间
            $arr1['biaoz'] = $zirwbz1; //子任务标志为完成
            $arr1['jiedsjwcsj'] = $shijian;
            $arr1['zirenwnr'] = $data['renwnr']; //子任务内容
            $arr1['youxbz'] = '0';
            $arr1['lzry'] = session('admin_auth.aid');  //操作人员
            $arr1['jiedfzr'] = $jiedfzr;
            $arr1['fuzr'] = $data['jiedfzr'];
            $arr1['gbbz'] = 1;  //关闭标志
            $arr1['gbryid'] = session('admin_auth.aid');  //关闭人id;
            //$arr1['zirenwnr'] = $data['renwnr']; //子任务内容
            //更新子任务完成时间和工时(分钟)
           /* $date = strtotime($shijian) - strtotime($list['jiessj']);
            $date = $date % 86400;
            $arr1['jiedgs'] = $date / 3600;*/
            $arr1['jiedgs'] = $data['hour'] + $data['fen']/60; //工时
            Db::name('dl_buglist_zirw')->where('zirwid', $list['zirwid'])->update(['youxbz'=>'0']);
            Db::name('dl_buglist_zirw')->insertGetId($arr1);
        } else {
            $arr1['zirwlx'] = $zirwlx1;//子任务类型为未接收
            $arr1['renwid'] = $data['renwid']; //任务id
            $arr1['lzsj'] = $shijian; //未接收时间
            $arr1['biaoz'] = $zirwbz1;
            $arr1['jiedsjwcsj'] = $shijian; //接收时间和实际完成时间一样，即工时为0
            $arr1['youxbz'] = '0';
            $arr1['zirenwnr'] = $data['renwnr']; //子任务内容
            $arr1['fuzr'] = $jiedfzr; //负责人
            $arr1['jiedfzr'] = $jiedfzr;
            $arr1['lzry'] = session('admin_auth.aid');  //操作人id
            $arr1['gbbz'] = 1;  //关闭标志
            $arr1['gbryid'] = session('admin_auth.aid');  //关闭人id;

            //更新子任务完成时间和工时
            /*$gs = Db::name('dl_buglist_zirw')->where('zirwid', $list['zirwid'])->value('jiedgs');
            if (empty($gs)) {
                $arr1['jiedgs'] = 0;  //工时为0
            }*/
            $arr1['jiedgs'] = $data['hour'] + $data['fen']/60; //工时
            //print_r($arr1);exit;
            Db::name('dl_buglist_zirw')->insertGetId($arr1);
        }

        //任务关闭，发送邮件给当前阶段负责人、发布人员
        $buglist = Db::name('dl_buglist')->where('renwid', $data['renwid'])->find(); //获取当前任务信息
        $jiedfzr = Db::name('admin')->where('admin_id', $data['jiedfzr'])->find();//获取当前阶段人员的信息
        $fabry = Db::name('admin')->where('admin_id', $data['fabry'])->find();//获取发布人员的信息
        $caozry = Db::name('admin')->where('admin_id', session('admin_auth.aid'))->value('admin_realname');
        $bt = '#' . $buglist['renwid'] . ' ' . $buglist['renwbt'] . ' 任务关闭提醒';
        $nr = '';
        $nr .= 'hi ' . $jiedfzr['admin_realname'] . ',你有一个新任务(任务)提示 ' . '<br/>';
        $nr .= '任务(任务)名称：' . $buglist['renwbt'] . '<br/>';
        $nr .= '内容：' . $buglist['renwnr'] . '<br/>';
        $nr .= '关闭原因：' . $buglist['beizhu'] . '<br/>';
        $nr .= '任务链接：' . '<a href=' . ADMIN_URL . 'task/issue_one/renwid/' . $buglist['renwid'] . '>点我</a>' . '<br/>';
        $nr .= '当前阶段负责人：' . $jiedfzr['admin_realname'] . '<br/>';
        $nr .= '任务发布人员：' . $fabry['admin_realname'] . '<br/>';
        $nr .= '操作人员：' . $caozry . '<br/>';
        $nr .= '温馨提示：此邮件为提醒邮件，请勿直接回复，谢谢合作！';

        $nr1 = '';
        $nr1 .= 'hi ' . $fabry['admin_realname'] . ',你发布的任务(任务)被关闭提示 ' . '<br/>';
        $nr1 .= '任务(任务)名称：' . $buglist['renwbt'] . '<br/>';
        $nr1 .= '内容：' . $buglist['renwnr'] . '<br/>';
        $nr1 .= '任务链接：' . '<a href=' . ADMIN_URL . 'task/issue_one/renwid/' . $buglist['renwid'] . '>点我</a>' . '<br/>';
        $nr1 .= '当前阶段负责人：' . $jiedfzr['admin_realname'] . '<br/>';
        $nr1 .= '任务发布人员：' . $fabry['admin_realname'] . '<br/>';
        $nr1 .= '操作人员：' . $caozry . '<br/>';
        $nr1 .= '温馨提示：此邮件为提醒邮件，请勿直接回复，谢谢合作！';

        sendMail($jiedfzr['admin_email'], $bt, $nr);  //发送邮件给阶段负责人
        sendMail($fabry['admin_email'], $bt, $nr1);  //发送邮件给发布人

        $this->success("任务关闭成功");
    }

    //添加任务显示
    public function issue_add()
    {
        return $this->fetch();
    }

    //添加任务操作
    public function issue_runadd()
    {
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $jied = Db::name('dl_erjidaima')->where('daimmc', '审核阶段')->where('fenlbm', '当前阶段')->find();
        $shijian = date('Y-m-d H:i:s', time());
        //添加任务、主表
        $data = [
            'jiedfzr' => $_POST['jiedfzr'],
            'renwlx' => $_POST['renwlx'],
            'jied' => $jied['daimid'],
            'youxjb' => $_POST['youxjb'],
            'fabry' => session('admin_auth.aid'),
            'yaoqwcsj' => $_POST['yaoqwcsj'],
            'renwbt' => $_POST['renwbt'],
            'renwnr' => $_POST['renwnr'],
            'fabsj' => $shijian,
            'biaoz' => $_POST['biaoz'],
            'chanp_id' => $_POST['chanpid'],  //产品id
            'xiangm_id' => $_POST['xiangmid'],  //项目id
            'mk_name' => $_POST['mk_name'],  //模块名称
            'shbz' => '1', //任务不用审核，添加任务即为审核成功
            'shsj' => date('Y-m-d H:i:s', time()),
            'zhuangt' => 11,//任务添加成功，任务则为未接收
            'uptime' => $shijian, //更新更新时间
        ];
        $bulist_id = DB::name('dl_buglist')->insertGetId($data);

        if ($bulist_id) {
            /*
             * 使用正则去获取上传文件的相对路径和名称，然后判断plug_file里面的文件路径是否匹配，如果匹配就更新plug_file里面的buglist_id.
             * */
            $file_name = $_POST['renwnr'];
            $patt = '/\/data\/[a-zA-z0-9-\/\.]+/';
            preg_match_all($patt, $file_name, $res);  //得到了上传文件的相对路径
            $file_list = DB::name('plug_files')->where('up_user', session('admin_auth.admin_realname'))->select();
            //循环判断是否和plug_file里面的路径一样
            foreach ($file_list as $k => $v) {
                for ($i = 0; $i < count($res[0]); $i++) {
                    if ($res['0'][$i] === $v['path']) {
                        DB::name('plug_files')->where('id', $v['id'])->setField('buglist_id', $bulist_id);
                        break;
                    }
                }
            }

            /*任务分配，发送邮件给当前阶段负责人,也就是审核人员*/
            $buglist = Db::name('dl_buglist')->where('renwid', $bulist_id)->find(); //获取当前任务信息
            $jiedfzr = Db::name('admin')->where('admin_id', $data['jiedfzr'])->find();//获取当前阶段人员的信息
            $fabry = Db::name('admin')->where('admin_id', $data['fabry'])->find();//获取发布人员的信息
            $bt = '#' . $buglist['renwid'] . ' ' . $buglist['renwbt'] . ' 新任务提醒';
            $nr = '';
            $nr .= 'hi ' . $jiedfzr['admin_realname'] . ',有一个新任务(任务) ' . '<br/>';
            $nr .= '任务(任务)名称：' . $buglist['renwbt'] . '<br/>';
            $nr .= '内容：' . $buglist['renwnr'] . '<br/>';
            $nr .= '任务链接：' . '<a href=' . ADMIN_URL . 'task/issue_one/renwid/' . $buglist['renwid'] . '>点我</a>' . '<br/>';
            $nr .= '当前阶段负责人：' . $jiedfzr['admin_realname'] . '<br/>';
            $nr .= '任务发布人员：' . $fabry['admin_realname'] . '<br/>';
            $nr .= '操作人员：' . $fabry['admin_realname'] . '<br/>';
            $nr .= '温馨提示：此邮件为提醒邮件，请勿直接回复，谢谢合作！';

            sendMail($jiedfzr['admin_email'], $bt, $nr);  //发送邮件给阶段负责人

            $this->success("任务添加成功", "task/issue_list");
        } else {
            $this->error("任务添加失败", "task/issue_list");
        }
    }

    //重启任务操作
    public function issue_restart()
    {
        date_default_timezone_set('PRC'); //必须设置时间为中国地区，否则时间会不一样
        $shijian = date('Y-m-d H:i:s', time());
        $data = input('param.');
        //$cqbeizhu = input('param.renwnr');
        //重启任务、删除关闭状态
        $arr = [
            'renwid'    => $data['renwid'],
            'cqbeizhu'  => $data['renwnr'],
            'rw_state'  => 0,
            'gbry_id'   => 0,
            'beizhu'    => '',
            'uptime'    => $shijian,
            'cqsj'      => $shijian,
            'zhuangt'      => 11, //当前状态为未接收
            'biaoz'      => 17, //状态标志为任务分配
            'jied'      => '', //当前阶段
            'jiedfzr' => $data['jiedfzr'],
            'yaoqwcsj' => $data['yaoqwcsj'],
            'cqry'      => session('admin_auth.aid'), //重启人员
            'lzry'      => session('admin_auth.aid'), //操作人员
        ];
        $arr1 = [
            'renwid'    => $data['renwid'],
            'zirenwnr'  => $data['renwnr'],
            'lzsj'    => $shijian,
            'cqsj'      => $shijian,
            'fuzr' => $data['jiedfzr'],
            'cqry'      => session('admin_auth.aid'), //重启人员
            'lzry'      => session('admin_auth.aid'), //操作人员
        ];
        $fl = Db::name('dl_buglist')->update($arr);
        //生成重启子任务信息
        Db::name('dl_buglist_zirw')->insertGetId($arr1);
        if($fl){
            $this->success('任务重启成功');
        }else{
            $this->success('任务重启失败');
        }
    }

    /*----------------------------个人任务记录开始-----------------------*/
    //个人处理任务记录显示
    public function task_log()
    {
        $buglist = Db::name('dl_buglist')->select();
        $this->assign('buglist', $buglist);
        //显示子任务标志
        $zirwbzlist = Db::name('dl_erjidaima')->where('fenlbm', '子任务标志')->select();
        $this->assign('zirwbzlist', $zirwbzlist);
        $buglist = Db::name('dl_buglist')->select();
        $this->assign('buglist', $buglist);
        //显示解决任务的个数
        $renwid_list = Db::name('dl_buglist_zirw')->where('fuzr',session('admin_auth.aid'))->column('distinct renwid');
        $sum = Db::name('dl_buglist')->where('renwid','in',$renwid_list)->where('rw_state','26')->count();
        $this->assign('sum', $sum);
        //显示解决任务的次数
        $count = Db::name('dl_buglist_zirw')->where('fuzr', session('admin_auth.aid'))->where('jiedgs', 'NEQ', 0)->count();
        //未接收任务次数
        $lz_count = Db::name('dl_buglist_zirw')->where('fuzr', session('admin_auth.aid'))->count();
        $this->assign('lz_count', $lz_count);
        //计算解决任务总共时
        $jiedgs = Db::name('dl_buglist_zirw')->where('fuzr', session('admin_auth.aid'))->sum('jiedgs');
        $this->assign('jiedgs', $jiedgs);
        $this->assign('count', $count);
        $map = array();
        $map1 = array();
        $key = input('key', '');  //关键字
        $biaoz = input('biaoz', ''); //接收任务完成或者未完成
        $key = input('key');  //关键字
        $cp = input('cp', '');  //接收产品
        $xm = input('xm', '');  //接收项目
        $youxjb = input('youxjb', '');  //接收优先级别
        $renwlx = input('renwlx', '');  //接收任务类型

        if ($cp != '') {
            $map1['chanp_id'] = $cp;
        }
        if ($xm != '') {
            $map1['xiangm_id'] = $xm;
        }
        if ($youxjb != '') {
            $map1['youxjb'] = $youxjb;
        }
        if ($renwlx != '') {
            $map1['renwlx'] = $renwlx;
        }
        $renwid = Db::name('dl_buglist')->where($map1)->column('renwid');
        if ($biaoz != '') {
            $map['biaoz'] = $biaoz;
        }

        //查询：时间格式过滤 获取格式 2015-11-12 - 2015-11-18
        $sldate = input('reservation', '');
        $arr = explode(" - ", $sldate);
        if (count($arr) == 2) {
            $arrdateone = $arr[0] . ' 00:00:00';
            $arrdatetwo = $arr[1] . ' 23:55:55';
            $map['jiessj'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
        }
        //模糊查询
        if (!empty($key)) {
            $a = Db::name('dl_buglist')->where('renwbt', 'like', "%" . $key . "%")->column('renwid');
            $map['renwid'] = array('in', $a);
        }
        //查询出此用户所有的子任务记录
        //$zrw_log = Db::name('dl_buglist_zirw')->where($map)->where('renwid','in',$renwid)->where('fuzr',session('admin_auth.aid'))->where('jiedgs','NEQ',0)->order('jiessj desc')->paginate(config('paginate.list_rows'),false,['query'=>get_query()]);
        $renwu = input('renwu','');
        if($renwu == '1'){ //我发布的任务记录
            unset($map['biaoz']);  //删掉子任务的状态
            unset($map['jiessj']); //删掉子任务的接收状态
            $map['fabry'] = session('admin_auth.aid');
            $fb_count = Db::name('dl_buglist')->where($map)->count();
            $this->assign('fb_count',$fb_count); //计算发布人的发布任务个数
            if (count($arr) == 2) {
                $arrdateone = $arr[0] . ' 00:00:00';
                $arrdatetwo = $arr[1] . ' 23:55:55';
                $map['fabsj'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
            $zrw_log = Db::name('dl_buglist')->where('fabry',session('admin_auth.aid'))->where($map1)->order('fabsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
            $show = $zrw_log->render();
            $show = preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)", "<a href='javascript:ajax_page($1);'>$2</a>", $show);
            $this->assign('page', $show);
            $this->assign('zrw_log', $zrw_log);
            $this->assign('renwu',1);
            if (request()->isAjax()) {
                return $this->fetch('ajax_log_fabu_list');
            } else {
                return $this->fetch();
            }
        }elseif($renwu == '3'){
            unset($map['biaoz']);  //删掉子任务的状态
            unset($map['jiessj']); //删掉子任务的接收状态
            $map['gbry_id'] = session('admin_auth.aid');
            $fb_count = Db::name('dl_buglist')->where($map)->count();
            $this->assign('fb_count',$fb_count); //计算发布人的发布任务个数
            if (count($arr) == 2) {
                $arrdateone = $arr[0] . ' 00:00:00';
                $arrdatetwo = $arr[1] . ' 23:55:55';
                $map['fabsj'] = array(array('egt', $arrdateone), array('elt', $arrdatetwo), 'AND');
            }
            $zrw_log = Db::name('dl_buglist')->where('gbry_id',session('admin_auth.aid'))->where($map1)->order('fabsj desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
            $show = $zrw_log->render();
            $show = preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)", "<a href='javascript:ajax_page($1);'>$2</a>", $show);
            $this->assign('page', $show);
            $this->assign('zrw_log', $zrw_log);
            $this->assign('renwu',3);
            if (request()->isAjax()) {
                return $this->fetch('ajax_log_fabu_list');
            } else {
                return $this->fetch();
            }
        } else{ //我参与的任务记录
            $zrw_log = Db::name('dl_buglist_zirw')->where($map)->where('renwid', 'in', $renwid)->where('fuzr', session('admin_auth.aid'))->order('zirwid desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);  //我参与过的任务记录
            $show = $zrw_log->render();
            $show = preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)", "<a href='javascript:ajax_page($1);'>$2</a>", $show);
            $this->assign('page', $show);
            $this->assign('zrw_log', $zrw_log);
            $this->assign('renwu','');
            if (request()->isAjax()) {
                return $this->fetch('ajax_log_list');
            } else {
                return $this->fetch();
            }
        }
    }

    //修改工时显示
    public function gs_edit()
    {
        $zirwid = input('zirwid');
        $data['code']=1;
        $data['zirwid']=$zirwid;
        return json($data);
    }

    //
    public function gs_runedit()
    {
        $data = input('param.');
        $arr = [
            'zirwid'    =>  $data['zirwid'],
            'jiedgs'    =>  $data['hour'] + $data['fen']/60,
        ];
        //print_r($arr);exit;
        $fl = Db::name('dl_buglist_zirw')->update($arr);
        if($fl){
            $this->success('工时修改成功');
        }else{
            $this->success('工时修改失败');
        }
    }

    /*----------------------------个人任务记录结束-----------------------*/

    /*
	 * 数据导出功能
	 * @author liyuzhao <562405704@qq.com>
	 */
    public function issue_excel()
    {
        excel2issue();
    }
}