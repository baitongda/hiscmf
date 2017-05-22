<?php
/**
 * Created by PhpStorm.
 * Weeklog.php
 * User: liyuzhao
 * Date: 2017/5/11
 * Time: 12:09
 * 周报功能
 */
namespace app\admin\controller;
use think\Db;
use think\Cache;

class Weeklog extends Base
{
    //周报列表显示，为我的周报
    public function weeklog_list()
    {
        $map = array();
        $tijiaobz = input('tijiaobz','');
        if(!empty($tijiaobz)){
            $map['tijiaobz'] = $tijiaobz;
        }
        //如果是超级管理员或者管理员，可以查看所有的周报
        $bz = 0;  //标志，0表示不是超级管理员和管理员，1为反之
        $auth = Db::name('auth_group_access')->where('group_id','in',[1,2])->select();
        foreach ($auth as $k=>$v){
            if($v['uid'] == session('admin_auth.aid')){
                $bz = 1;
                break; //结束整个循环
            }
        }

        //查询：时间格式过滤 获取格式 2015-11-12 - 2015-11-18
        $sldate = input('reservation', '');
        $arr = explode(" - ", $sldate);
        if (count($arr) == 2) {
            $arrdateone = $arr[0] . ' 00:00:00';
            $arrdatetwo = $arr[1] . ' 23:55:55';
            $map['addtime'] = array(array('egt', strtotime($arrdateone)), array('elt', strtotime($arrdatetwo)), 'AND');
        }

        if($bz == 1){ //如果是超级管理员或者管理员，显示全部
            $weeklog = Db::name('dl_zb')->where($map)->order('addtime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        }else if($tijiaobz == '1'){ //如果点击了已提交
            $weeklog = Db::name('dl_zb')->where('admin_id',session('admin_auth.aid'))->where($map)->order('addtime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        }else if($tijiaobz == '2'){ //如果点击了未提交
            $weeklog = Db::name('dl_zb')->where('admin_id',session('admin_auth.aid'))->where($map)->order('addtime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        } else{
            $weeklog = Db::name('dl_zb')->where($map)->where('admin_id',session('admin_auth.aid'))->order('addtime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]); //显示个人全部周报
        }

        $show = $weeklog->render();
        $show = preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)", "<a href='javascript:ajax_page($1);'>$2</a>", $show);
        $this->assign('page', $show);
        $this->assign('weeklog', $weeklog);
        if (request()->isAjax()) {
            return $this->fetch('ajax_weeklog_list');
        } else {
            return $this->fetch();
        }
    }

    //添加周报操作
    public function weeklog_runadd()
    {
        $data = input('param.');
        if($data['sjld'] == 0){
            $this->error("请选择上级领导");
        }
        $files = $_FILES;

        //查询此用户是从属哪个角色
        $group_id = Db::name('auth_group_access')->where('uid',session('admin_auth.aid'))->value('group_id');
        $bum = Db::name('auth_group')->where('id',$group_id)->find();
        $zb_content = str_replace(array("\r\n", "\r", "\n"),"<br />",$data['zb_content']); //把\r\n转换为br，解决换行问题
        $zb_next_content = str_replace(array("\r\n", "\r", "\n"),"<br />",$data['zb_next_content']); //把\r\n转换为br，解决换行问题
        $zb_issue = str_replace(array("\r\n", "\r", "\n"),"<br />",$data['zb_issue']); //把\r\n转换为br，解决换行问题

        $arr = [
            'sjld'  => $data['sjld'],  //上级领导
            'admin_id'  => session('admin_auth.aid'),  //周报人员
            //'zb_type'  => $data['sjld'],  //周报类型，默认为1，待扩展
            'bum'  => $bum['title'],  //角色名称/部门名称
            'zb_content'  => $zb_content,  //本周完成内容
            'zb_next_content'  => $zb_next_content,  //下周的计划
            'zb_issue'  => $zb_issue,  //建议
            'zb_time'  => $data['zb_time'],  //周报时间，为xxxx-xx-xx
            'addtime'  => time(),  //添加时间，为时间戳
            'uptime'  => time(),  //更新时间，为时间戳
            'week'  => $data['weeks1'],  //本月第几周
        ];

        if($files['file1']['error'] != 4){  //如果上传了周报
            $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $str = str_shuffle($str);
            $str = substr($str,0,30);  //随机获取20位
            $ext = pathinfo($files['file1']['name'], PATHINFO_EXTENSION); //获取文件后缀名
            $path = ROOT_PATH . config('upload_path') . DS . date('Y-m-d') . '/' . $str .'.'. $ext;
            $save_path = config('upload_path') . '/' . date('Y-m-d') . '/' . $str .'.'. $ext;
            if (!is_dir(dirname($path))) { //如果目录不存在就创建
                if (!mkdir(dirname($path), 0755, true)) {
                    $this->error = "目录创建失败！";
                    return false;
                }
            }
            if(!move_uploaded_file ($files['file1']['tmp_name'], $path)) {//上传文件
                $this->error("上传文件失败");
            }
            $arr['up_file'] = $save_path; //保存上传文件的路径,不用存入plug_file表了
        }else{
            unset($arr['up_file']);
        }

        if(array_key_exists('baocun',$data)){  //如果提交为保存
            $arr['tijiaobz'] = '2';
            //插入周报
            $fl = Db::name('dl_zb')->insertGetId($arr);
            if($fl){
                $this->success("周报保存成功");
            }else{
                $this->error("周报保存失败");
            }
        }
        if(array_key_exists('tijiao',$data)){  //如果提交为提交
            $arr['tijiaobz'] = '1';
            //插入周报
            $fl1 = Db::name('dl_zb')->insertGetId($arr);
            if($fl1){
                $this->success("周报提交成功");
            }else{
                $this->error("周报提交失败");
            }
        }
    }

    //周报编辑显示
    public function weeklog_edit()
    {

        $id = input('id','');
        $color = input('color','');
        $zt_color = input('zt_color','');
        if(!empty($color)){
            Db::name('dl_zb')->where('id',$id)->update(['ready'=>'1','color'=>$color,'zt_color'=>$zt_color]);//更新周报的查询状态
        }
        //查询出周报记录
        $weeklog = Db::name('dl_zb')->where('id',input('id'))->find();
        $sjld = Db::name('admin')->where('admin_id',$weeklog['admin_id'])->value('admin_realname');
        $weeklog['sjld_name'] = $sjld;   //把上级领导的真名查出来，查看的时候赋值上去，修改的时候不需要，在jq那里自动判断了
        if($weeklog){
            $weeklog['code'] = 1;
        }else{
            $weeklog['code'] = 0;
        }
        return json($weeklog);
    }

    //周报编辑操作
    public function weeklog_runedit()
    {
        $data = input('param.');
        if($data['sjld'] == 0){
            $this->error("请选择上级领导");
        }

        //周报上传文件
        $files = $_FILES;
        if(!empty($files)){  //如果上传了周报
            $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $str = str_shuffle($str);
            $str = substr($str,0,30);  //随机获取20位
            $ext = pathinfo($files['file1']['name'], PATHINFO_EXTENSION); //获取文件后缀名
            $path = ROOT_PATH . config('upload_path') . DS . date('Y-m-d') . '/' . $str .'.'. $ext;
            $save_path = config('upload_path') . '/' . date('Y-m-d') . '/' . $str .'.'. $ext;
            if (!is_dir(dirname($path))) { //如果目录不存在就创建
                if (!mkdir(dirname($path), 0755, true)) {
                    $this->error = "目录创建失败！";
                    return false;
                }
            }
            if(!move_uploaded_file ($files['file1']['tmp_name'], $path)) {//上传文件
                $this->error("上传文件失败");
            }
        }else{
            $save_path = Db::name('dl_zb')->where('id',$data['id'])->value('up_file');
        }

        //查询此用户是从属哪个角色
        $group_id = Db::name('auth_group_access')->where('uid',session('admin_auth.aid'))->value('group_id');
        $bum = Db::name('auth_group')->where('id',$group_id)->find();
        $zb_content = str_replace(array("\r\n", "\r", "\n"),"<br />",$data['zb_edit_content']); //把\r\n转换为br，解决换行问题
        $zb_next_content = str_replace(array("\r\n", "\r", "\n"),"<br />",$data['zb_next_edit_content']); //把\r\n转换为br，解决换行问题
        $zb_issue = str_replace(array("\r\n", "\r", "\n"),"<br />",$data['zb_edit_issue']); //把\r\n转换为br，解决换行问题

        $arr = [
            'sjld'  => $data['sjld'],  //上级领导
            'admin_id'  => session('admin_auth.aid'),  //周报人员
            //'zb_type'  => $data['sjld'],  //周报类型，默认为1，待扩展
            'bum'  => $bum['title'],  //角色名称/部门名称
            'zb_content'  => $zb_content,  //本周完成内容
            'zb_next_content'  => $zb_next_content,  //下周的计划
            'zb_issue'  => $zb_issue,  //建议
            'zb_time'  => $data['zb_edit_time'],  //周报时间，为xxxx-xx-xx
            'addtime'  => time(),  //添加时间，为时间戳
            'uptime'  => time(),  //更新时间，为时间戳
            'week'  => $data['zb_edit_weeks'],  //本月第几周
            'up_file'  => $save_path,  //保存上传文件的路径,不用存入plug_file表了
        ];

        if(array_key_exists('baocun',$data)){  //如果提交为保存
            $arr['tijiaobz'] = '2';
        }
        if(array_key_exists('tijiao',$data)){  //如果提交为提交
            $arr['tijiaobz'] = '1';
        }
        //更新周报
        $fl = Db::name('dl_zb')->where('id',$data['id'])->update($arr);
        if($fl){
            if($arr['tijiaobz'] == '1'){
                $this->success("周报提交成功");
            }else{
                $this->success("周报更新成功");
            }
        }else{
            $this->error("周报更新失败");
        }
    }

    //删除周报
    public function weeklog_del()
    {
        $id = input('id');
        $fl = Db::name('dl_zb')->delete($id);
        if($fl){
            $this->success("周报删除成功");
        }else{
            $this->error("周报删除失败");
        }
    }

    //生成当前用户选择的日期周报记录
    public function weeklog_create()
    {
        $zb_time = input('zb_time');
        //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
        $first = 1;
        //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
        $w = date('w',strtotime($zb_time));
        //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
        $week_start = date('Y-m-d',strtotime("$zb_time -".($w ? $w - $first : 6).' days'));
        //本周结束日期
        $week_end = date('Y-m-d',strtotime("$week_start +6 days"));
        $week_start = $week_start . ' 00:00:00';
        $week_end = $week_end . ' 00:00:00';
        //查询当前用户此日期下的子任务\条件：流转时间不为空，当前用户，当前星期时间，标志不等于零
        $list = Db::query("select * from his_dl_buglist a,his_dl_buglist_zirw b where a.renwid = b.renwid and b.lzsj<>'' and b.fuzr=".session('admin_auth.aid')." and b.lzsj>='".$week_start."' and b.lzsj<='".$week_end."' and b.biaoz<>0");
        //查询此周的任务记录
        return  json($list);
    }

    //下属周报显示
    public function weeklog_xiashu()
    {
        $admin_id = input('admin_id',''); //获取提交的人查询条件
        $ready = input('ready',''); //获取是否查看的查询条件
        $sjld = input('sjld',''); //获取上级领导id
        $bum = input('bum',''); //获取部门
        $map = array();
        if(!empty($admin_id)){
            $map['admin_id'] = $admin_id;
        }
        if(!empty($sjld)){
            $map['sjld'] = $sjld;
        }
        if(!empty($bum)){
            $map['bum'] = $bum;
        }
        //如果是超级管理员或者管理员，可以查看所有的周报
        $bz = 0;  //标志，0表示不是超级管理员和管理员，1为反之
        $auth = Db::name('auth_group_access')->where('group_id','in',[1,2])->select();
        foreach ($auth as $k=>$v){
            if($v['uid'] == session('admin_auth.aid')){
                $bz = 1;
                break; //结束整个循环
            }
        }

        //查询：时间格式过滤 获取格式 2015-11-12 - 2015-11-18
        $sldate = input('reservation', '');
        $arr = explode(" - ", $sldate);
        if (count($arr) == 2) {
            $arrdateone = $arr[0] . ' 00:00:00';
            $arrdatetwo = $arr[1] . ' 23:55:55';
            $map['addtime'] = array(array('egt', strtotime($arrdateone)), array('elt', strtotime($arrdatetwo)), 'AND');
        }

        //此周报上级领导和上级领导的领导都能看到
        $list = Db::name('dl_zb_sys')->select();
        $arr = array();
        foreach($list as $k=>$v){
            if($v['pid'] == 0){
                if((string)session('admin_auth.aid') === $v['path']){
                    $arr[] = $v['admin_id'];
                }
            }else{
                $path = explode('-',$v['path']);
                if(in_array((string)session('admin_auth.aid'),$path)){
                    $arr[] = $v['admin_id'];
                }
            }
        }
        //删掉自身的id
        foreach($arr as $k=>$v){
            if($v == session('admin_auth.aid')){
                unset($arr[$k]);
            }
        }

        if($bz == 1){ //如果是超级管理员或者管理员，显示全部
            if($ready == '1'){ //如果点击了已查看
                $map['ready'] = '1';
            }else if($ready == '0'){ //如果点击了未查看
                $map['ready'] = '0';
            }
            $weeklog = Db::name('dl_zb')->where($map)->order('uptime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]);
        }else if($ready == '1'){ //如果点击了已查看
            $map['ready'] = '1';
            $weeklog = Db::name('dl_zb')->where('admin_id','in',$arr)->where('tijiaobz','=','1')->where($map)->order('uptime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]); //显示提交的
        }else if($ready == '0'){ //如果点击了未查看
            $map['ready'] = '0';
            $weeklog = Db::name('dl_zb')->where('admin_id','in',$arr)->where('tijiaobz','=','1')->where($map)->order('uptime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]); //显示提交的
        } else{
            $weeklog = Db::name('dl_zb')->where($map)->where('admin_id','in',$arr)->where('tijiaobz','=','1')->order('uptime desc')->paginate(config('paginate.list_rows'), false, ['query' => get_query()]); //显示提交的
        }

        $show = $weeklog->render();
        $show = preg_replace("(<a[^>]*page[=|/](\d+).+?>(.+?)<\/a>)", "<a href='javascript:ajax_page($1);'>$2</a>", $show);
        $this->assign('page', $show);
        $this->assign('weeklog', $weeklog);
        if (request()->isAjax()) {
            return $this->fetch('ajax_weeklog_xiashu');
        } else {
            return $this->fetch();
        }
    }

    //上级评论周报显示
    public function weeklog_comment()
    {
        $id = input('id');
        $list = Db::name('dl_zb')->where('id',$id)->find();
        return $list;
    }

    //上级评论周报显示操作
    public function weeklog_runcomment()
    {
        $id = input('id');
        $sj_comment = input('sj_comment');
        $fl = Db::name('dl_zb')->where('id',$id)->update(['sj_comment'=>$sj_comment]);
        if($fl){
            $this->success("评论成功");
        }else{
            $this->error("评论失败");
        }
    }

    //周报改变查看的状态
    public function weeklog_ready()
    {
        $id = input('id');
        $color = input('color');
        $fl = Db::name('dl_zb')->where('id',$id)->update(['ready'=>'1','color'=>$color]);
        if($fl){
            return true;
        }else{
            return false;
        }
    }

    //把周报导出为word
    public function weeklog_word()
    {
        //接收周报的id，把周报记录数据生成到word文档上面
        $id = input('id');
        $list = Db::name('dl_zb')->where('id',$id)->find();
        $name = Db::name('admin')->where('admin_id',$list['admin_id'])->value('admin_realname');
        //把内容里面的br全部换成word里面的换行符，<w:br />
        $zb_content = str_replace(array("<br />"),"<w:br />",$list['zb_content']);
        $zb_next_content = str_replace(array("<br />"),"<w:br />",$list['zb_next_content']);
        $zb_issue = str_replace(array("<br />"),"<w:br />",$list['zb_issue']);
        $sj_comment = str_replace(array("<br />"),"<w:br />",$list['sj_comment']);

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        //$section = $phpWord->addSection();
        $section = $phpWord->createSection();
        //字体的样式
        $arr = [
           '1'=>['oneStyle',array('bold'=>true,'color'=>'000000','size'=>18)],   //第一行标题
           '2'=>['twoStyle',array('bold'=>true,'color'=>'000000','size'=>14)],   //第二行
        ];

        $phpWord->addFontStyle($arr[1][0],$arr[1][1]);
        $phpWord->addParagraphStyle('pStyle', array('align'=>'center'));
        $section->addText('员工工作周报', 'oneStyle', 'pStyle'); //文本
        $section->addTextBreak(1);  //换行符
        $phpWord->addFontStyle($arr[2][0],$arr[2][1]);
        $section->addText('报告人:'.$name.'        部门:'.$list['bum'], 'twoStyle');

        // Define table style arrays
        $styleTable = array('borderSize'=>5, 'borderColor'=>'#000000', 'cellMargin'=>80);  //cellMargin为高度
        // Add table style
        $phpWord->addTableStyle('myOwnTableStyle', $styleTable);
        // Add table
        $table = $section->addTable('myOwnTableStyle');
        $fontStyle = array('bold'=>true,'align'=>'center', 'size'=>13);
        $fontStyle1 = array('bold'=>false,'align'=>'center', 'size'=>13); //内容
        $styleCell = array('bold'=>false,'valign'=>'center', 'align'=>'center');

        $table->addRow(300);
        $table->addCell(5000)->addText("上级领导",$fontStyle);   //宽度
        $table->addCell(25000)->addText($name,$fontStyle1,$styleCell);
        $table->addRow(300);
        $table->addCell(4200)->addText("周报日期",$fontStyle);   //宽度
        $table->addCell(25000)->addText($list['zb_time'],$fontStyle1,$styleCell);
        $table->addRow(300);
        $table->addCell(4200)->addText("本月第几周",$fontStyle);   //宽度
        $table->addCell(25000)->addText($list['week'],$fontStyle1,$styleCell);

        //本周工作
        $table->addRow(2700);  //表格高度
        $table->addCell(4200)->addText("<w:br /><w:br /><w:br /><w:br />本周工作",$fontStyle);
        $table->addCell(25000)->addText($zb_content,$fontStyle1);
        //下周计划
        $table->addRow(2700);  //表格高度
        $table->addCell(4200)->addText("<w:br /><w:br /><w:br /><w:br />下周计划",$fontStyle);
        $table->addCell(25000)->addText($zb_next_content,$fontStyle1);
        //存在问题改进措施建议
        $table->addRow(2700);  //表格高度
        $table->addCell(4200)->addText("<w:br /><w:br /><w:br />存在问题改进措施建议",$fontStyle);
        $table->addCell(25000)->addText($zb_issue,$fontStyle1);
        //上级评价
        $table->addRow(1400);  //表格高度
        $table->addCell(4200)->addText("<w:br /><w:br />上级评价",$fontStyle);
        $table->addCell(25000)->addText($sj_comment,$fontStyle1);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$name.$list['zb_time'].'周报.docx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save('php://output');exit;
    }

    /*------------------------------周报设置管理开始--------------------------------*/
    //显示周报设置页面
    public function weeklog_sys_list()
    {
        //
        $pid = input('pid', 0);
        $level = input('level', 0);
        $id_str = input('id', 'pid');
        $ld_list = Db::name('dl_zb_sys')->where('pid', $pid)->order('listorder')->select();
        $ld_list_all = Db::name('dl_zb_sys')->order('listorder')->select();
        $arr = menu_left($ld_list, 'id', 'pid', '─', $pid, $level, $level * 20);
        $arr_all = menu_left($ld_list_all, 'id', 'pid', '─', 0, $level, $level * 20);
        $this->assign('ld_list', $arr);
        $this->assign('ld_list_all', $arr_all);
        $this->assign('pid', $id_str);
        //显示用户名称
        $admin_list = Db::name('admin')->order('admin_id asc')->select();
        $this->assign('admin_list',$admin_list);
        if (request()->isAjax()) {
            return $this->fetch('ajax_weeklog_sys_list');
        } else {
            return $this->fetch();
        }
    }

    //添加下属显示
    public function weeklog_sys_add()
    {
        $pid = input('pid','');
        $ld_list = Db::name('dl_zb_sys')->select();
        $ld_list = menu_left($ld_list);
        $this->assign('ld_list',$ld_list);
        $this->assign('pid',$pid);
        //显示下属
        $admin_list = Db::name('admin')->select();
        $this->assign('admin_list',$admin_list);
        return $this->fetch();
    }

    //领导、下属状态修改
    public function weeklog_sys_state()
    {
        $id = input('x');
        $status = Db::name('dl_zb_sys')->where(array('id' => $id))->value('status');
        if ($status == 1) {
            $statedata = array('status' => 0);
            Db::name('dl_zb_sys')->where(array('id' => $id))->setField($statedata);
            $this->success("状态禁止");
        } else {
            $statedata = array('status' => 1);
            Db::name('dl_zb_sys')->where(array('id' => $id))->setField($statedata);
            $this->success("状态开始");
        }
    }

    //周报领导、下属添加操作
    public function weeklog_sys_runadd()
    {
        $data = input('param.');
        if($data['admin_id'] == 0){
            $this->error("请选择领导，再进行添加");
        }
        $admin_id_list = Db::name('dl_zb_sys')->column('admin_id');
        if(in_array($data['admin_id'],$admin_id_list)){
            $this->error("用户已经存在对应的关系，只能修改，不能增加");
        }
        $time = time();
        //查出用户真实名字
        $name = Db::name('admin')->where('admin_id',$data['admin_id'])->value('admin_realname');
        if(empty($data['status'])){
            $data['status'] = 0;
        }
        $arr = [
           'admin_id'   =>  $data['admin_id'],
           'admin_realname'   =>  $name,
           'listorder'   =>  $data['sort'],
           'status'   =>  $data['status'],
           'level'   =>  1,
           'addtime'   =>  $time,
           'uptime'   =>  $time,
        ];
        if(!empty($data['pid'])){
            $list = Db::name('dl_zb_sys')->where('id',$data['pid'])->find();
            $arr['level'] = $list['level'] + 1;
            $arr['pid'] = $data['pid'];
            $a = path1($data['pid']);

            if($list['pid'] == 0){ //如果为顶级的，返回的只有一个admin_id的值
                $path = $a;
            }else{
                $path = implode('-',$a);
            }
            $arr['path'] = $path.'-'.$data['admin_id'];

            $fl = Db::name('dl_zb_sys')->insertGetId($arr);
            if($fl){
                $this->success("添加成功","Weeklog/weeklog_sys_list");
            }else{
                $this->error("添加失败");
            }
        }else{
            $fl = Db::name('dl_zb_sys')->insertGetId($arr);
            //更新路径
            Db::name('dl_zb_sys')->update(['id'=>$fl,'path'=>$data['admin_id']]);
            if($fl){
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }
    }

    //周报领导、下属修改显示
    public function weeklog_sys_edit()
    {
        $id = input('id','');
        $ld_list = Db::name('dl_zb_sys')->select();
        $ld_list = menu_left($ld_list);
        $this->assign('ld_list',$ld_list);
        //显示下属
        $admin_list = Db::name('admin')->select();
        $this->assign('admin_list',$admin_list);
        //获取要修改的id信息
        $list = Db::name('dl_zb_sys')->where('id',$id)->find();
        $this->assign('list',$list);
        return $this->fetch();
    }

    //周报领导、下属修改操作
    public function weeklog_sys_runedit()
    {
        $data = input('param.');
        $arr = [
           'id'  => $data['id'],
           'admin_id'  => $data['admin_id'], //下属id
           'pid'  => $data['pid'], //上级领导id
           'listorder'  => $data['sort'], //排序
           'uptime'  => time(),
        ];

        if(!empty($data['admin_id'])){
            $name = Db::name('admin')->where('admin_id',$data['admin_id'])->value('admin_realname');
            $arr['admin_realname'] = $name;
        }
        if(!empty($data['status'])){
            $arr['status'] = $data['status'];
        }
        $path = Db::name('dl_zb_sys')->where('id',$data['pid'])->value('path'); //查询出选择领导的路径
        $path = $path . '-' . $data['admin_id'];
        $arr['path'] = $path;

        $fl = Db::name('dl_zb_sys')->update($arr);
        if($fl){
            $this->success("更新成功","Weeklog/weeklog_sys_list");
        }else{
            $this->error("更新失败");
        }
    }

    //周报领导、下属删除
    public function weeklog_sys_del()
    {
        $pid = input('id');
        $arr = Db::name('dl_zb_sys')->select();
        $tree = new \Tree();
        $tree->init($arr, ['parentid' => 'pid']);
        $arrTree = $tree->get_childs($arr, $pid, true, true);
        if ($arrTree) {
            $rst = Db::name('dl_zb_sys')->where('id', 'in', $arrTree)->delete();
            if ($rst !== false) {
                Cache::clear();
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('删除失败');
        }
    }

    /*------------------------------周报设置管理结束--------------------------------*/
}