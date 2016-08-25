<?php

session_start();
include 'common.php';

// 简单的权限认证
// 授权凭证
$_access_token = md5('sqgawjw');
if (!$access_token || $access_token != $_access_token) {
    exit('没有授权！');
}
// 实例接口
$api = new api();
if (method_exists($api, $act)) {
    $api->db = $db;
    call_user_func(array($api, $act));
} else {
    exit('error action!');
}

/**
 * 简单的接口操作类
 * 有许多可扩展地方，算是抛砖引玉
 * 配有接口文档
 * 
 * @date    2016-06-15
 * @author Li Zogheng
 */
class api {

    public $debug = false;
    public $db;
    public $user = array();
    public $siteid;
    public $userid;
    private $request_data;
    private $result = array('status' => 0, 'msg' => '', 'data' => '');
    public $data_type = 'json';

    function __construct() {
        $data = file_get_contents('php://input');
        $json = json_decode($data, true);
        $this->set_request_data($json);
    }

    /**
     * 设置请求值
     * @param type $data
     */
    function set_request_data($data) {
        $this->request_data = $data;
    }

    /**
     * 发布新闻
     * 
     * @param string    $title      文章标题
     * @param string    $content    文章内容
     * @param int       $cid        分类id
     * @param int       $siteid     站点id
     * @param string    $source     推送来源
     * @param string    $resume     文章简介
     * @param int       $ifcheck    审核状态，1：已审核，0：待审核
     * @param string    $picurl     图片url地址
     * @param array     $attachment 附件
     * @return mixed     返回执行结果
     */
    function publish_news() {
        $this->check_request_data();
        extract($this->request_data);
        $error_msg = '';
        if (!$guid) {
            $error_msg = '文章guid不为空!';
        } else if (!$title) {
            $error_msg = '文章标题不为空!';
        } else if (!$content) {
            $error_msg = '文章内容不为空!';
        } else if (!$cid) {
            $error_msg = '文章分类id不为空!';
        }
        if ($error_msg != '') {
            $this->output_result(0, $error_msg);
        }
        $uid = 24524;
        $siteid || $siteid = '103216';
        $resume = $resume ? $resume : '';
        $picurl = $picurl ? $picurl : '';
        $ifcheck = intval($ifcheck);
        $t_data = array(
            'title' => $title, 'cid' => '0', 'resume' => $resume, 'releasetime' => time(),
            'uid' => $uid, 'type' => '0', 'hits' => '0', 'ifshare' => '0', 'pic_url' => $picurl,
            'cat' => '0', 'replies' => '0', 'gameid' => '', 'checked' => '0', 'havepic' => '0', 'siteid' => $siteid
        );
        //
        $tid = insert_data('oho_threads_note_title', $t_data);
        if (!$tid) {
            $this->output_result(0, '发布失败!');
        }
        // 保存附件
        $list_file = array();
        if ($attachment && is_array($attachment)) {
            $list_file = $this->save_upload_file($attachment, $uid, $siteid, $tid);
        }
        $serialize_file = serialize($list_file);
        // 字段数据
        $c_data = array(
            'tid' => $tid, 'content' => $content, 'attachment' => $serialize_file, 'siteid' => $siteid,
            'private_set' => '0', 'private_con' => '', 'moban' => '', 'magic_c' => '', 'fengye' => '', 'connumber' => 0
        );
        insert_data('oho_threads_note_con', $c_data);
        // 文章主表内容
        $n_data = array(
            'id' => 0, 'subject' => $title, 'tsubject' => '',
            'resume' => $resume, 'tid' => $tid, 'ord' => 10, 'digest' => 0,
            'cid' => $cid, 'sid' => 0, 'ifcheck' => $ifcheck, 'uid' => $uid, 'username' => '999999',
            'checkuid' => 0, 'checkname' => '', 'siteid' => $siteid, 'releasetime' => time(), 'checktime' => '', 'expiretime' => '8640000',
            'createtime' => time(), 'font' => '', 'hits' => 0, 'replies' => 0, 'havepic' => $picurl ? 1 : 0,
            'pic_url' => $picurl, 'parentcid' => $parentcid, 'parentsid' => 0,
            'leader' => '', 'department' => '', 'indexno' => '', 'nogk' => '0',
            'fileno' => '', 'setlink' => 0, 'linkurl' => '', 'source' => $source, 'topped' => 0,
            'settype' => 0, 'zhuanti' => 0, 'ocid' => 0, 'tags' => '', 'linkmd' => '', 'oifcheck' => 0,
            'bs' => '', 'bscid' => 0, 'sysifcheck' => 0, 'linkurl2' => '', 'bssiteid' => '', 'bscon' => '', 'qianshou' => 0,
            'jg' => 0, 'jws' => '', 'guid' => $guid,
        );
        insert_data('oho_colonys_note', $n_data);
        // 返回信息
        $this->output_result(1, '发布成功!', array('id' => $tid));
    }

    /**
     * 保存文件
     * @param type $files
     * @param type $uid
     * @param type $siteid
     * @param type $tid
     * @return type
     */
    private function save_upload_file($files, $uid, $siteid, $tid = 0) {
        // 文件信息
        $list = array();
        $save_dir = 'files/' . $siteid . '/' . date_("ym", time()) . '/';
        check_path($save_dir);
        foreach ($files as $value) {
            $content = base64_decode($value['content']);
            $pathInfo = pathinfo($value['name']);
            $et = $pathInfo['extension'];
            $file = $save_dir . '/' . uniqid() . '.' . $et;
            $file_path = ROOT_PATH . $file;
            $write = file_put_contents($file_path, $content);
            if ($write === false) {
                continue;
            }
            // 同步
            copy_sync_file($file_path);
            $flie_size = strlen($content);
            // 保存数据
            $data = array(
                'source' => $file, 'uid' => $uid, 'cat' => 8,
                'title' => $value['name'], 'size' => $flie_size,
                'tid' => $tid, 'temp' => '', 'releasetime' => time(),
                'updatetime' => '', 'siteid' => $siteid,
                'x_y' => 0, 'small_pic' => '', 'x' => 0, 'y' => 0,
                'cid' => 0, 'sid' => 0, 'md' => '', 'hits' => 0,
                'down' => 0, 'type' => '', 'private' => 0,
                'private_con' => '', 'privatecon' => '', 'password' => ''
            );
            $file_id = insert_data('oho_t_uploadfiles', $data);
            if ($file_id) {
                $list[] = array(
                    'title' => $value['name'], 'path' => $file, 'id' => $file_id, 'releasetime' => time(),
                    'hits' => 0, 'size' => $flie_size, 'ext' => $et,
                );
            }
        }
        return $list;
    }

    /**
     * 上传文件
     */
    function upload_file() {
        $this->check_request_data();
        extract($this->request_data);
        $siteid = '103216';
        $file_data = array();
        $save_dir = 'files/' . $siteid . '/' . date_("ym", time()) . '/';
        check_path($save_dir);
        foreach ($list_files as $value) {
            $content = base64_decode($value['content']);
            $pathInfo = pathinfo($value['name']);
            $ext = $pathInfo['extension'];
            // 文件名称
            $file_name = rand(1, 500) . '_24524_' . time() . '.' . $ext;
            $file = $save_dir . '/' . $file_name;
            $file_path = ROOT_PATH . $file;
            // 保存
            file_put_contents($file_path, $content);
            // 同步
            copy_sync_file($file_path);
            $file_data[] = 'http://gawjw.sqga.gov.cn/' . $file;
        }
        $this->output_result(1, '上传成功!', $file_data);
    }

    /**
     * 提交办事
     */
    function submit_banshi() {
        global $_onlineip, $_timestamp;
        $this->check_request_data();
        extract($this->request_data);
        $error_msg = '';
        if (!$guid) {
            $error_msg = '事项guid不为空!';
        } else if (!$bsid) {
            $error_msg = '事项id不为空!';
        } else if (!$poid) {
            $error_msg = '警局不为空!';
        } else if (!$content) {
            $error_msg = '事项内容不为空!';
        }
        if ($error_msg != '') {
            $this->output_result(0, $error_msg);
        }
        $uid = 52066;
        $username = '宿迁公安云平台';
        $siteid || $siteid = '103216';
        $over_time = $this->get_over_time($bsid);
        // 获取处理部门
        $sql = "select subject,cid,wxbm from oho_colonys_bs where tid='{$bsid}' and siteid='{$siteid}'";
        $bs_info = $this->db->get_one($sql);
        $curdealid = $poid ? $poid : $bs_info['wxbm'];
        $rt = $this->db->get_one("select title from oho_wxga_jg where id='{$curdealid}'");
        $curdepartment = $rt['title'];
        /**
         * 以下代码是根据云平台办事中处理部门id，获取本站对应的警局id
         * 本站oho_wxga_sqypt_unit表，存的是云平台的处理部门，可根据名称找到本站的对应的警局
         * 匹配的度不高
         */
        /**
          if ($poid) {
          $sql = "SELECT title FROM oho_wxga_sqypt_unit where guid='{$cid}'";
          $unit = $this->db->get_one($sql);
          $unit_name = $unit['title'];
          $sql = "SELECT id,title,level,name1 FROM oho_wxga_jg where type in ('xjg','pcs') and (title like '%{$unit_name}%' or name1='{$unit_name}')";
          $jg_data = $this->db->get_one($sql);
          $curdealid = $jg_data['id'];
          $curdepartment = $jg_data['name1'] ? $jg_data['name1'] : $jg_data['title'];
          } else {
          $curdealid = $bs_info['wxbm'];
          $rt = $this->db->get_one("select title from oho_wxga_jg where id='{$curdealid}'");
          $curdepartment = $rt['title'];
          }
         */
        //
        $content = serialize($content);
        $cxhaoma = substr(md5($siteid . '_' . $_timestamp . '_' . rand(10000, 99999)), 1, 6);
        $cxhaoma = date_('ymd') . strtoupper($cxhaoma);
        $c_data = array(
            'content' => $content, 'siteid' => $siteid, 'subject' => $bs_info['subject'], 'tid' => $bsid,
            'curdealid' => $curdealid, 'curdealtype' => '', 'curdepartment' => $curdepartment, 'jg' => '', 'pcs' => '', 'bm' => '',
            'linkman' => $username, 'email' => '',
            'phone' => '', 'location' => '', 'cid' => $bs_info['cid'], 'uid' => $uid, 'releasetime' => time(),
            'cxh' => $cxhaoma, 'ip' => $_onlineip, 'source' => '微信端', 'overtime' => $over_time,
            'phone_num' => '', 'guid' => $guid
        );
        $sb_bsid = insert_data('oho_t_bstable', $c_data);
        $data = array();
        if ($sb_bsid) {
            $msg = '提交成功!';
            $data = array('id' => $sb_bsid);
            $status = 1;
        } else {
            $status = 0;
            $msg = '提交失败!';
        }
        // 返回信息
        $this->output_result($status, $msg, $data);
    }

    //获取本业务的超时时刻的时间戳。
    private function get_over_time($tid) {
        global $_timestamp;
        $cat_limit_time = array(); //获取本站办事超时设置
        if (file_exists(ROOT_PATH . 'files/zdcqprivate.conf')) {
            $con = file_get_contents(ROOT_PATH . 'files/zdcqprivate.conf');
            $cat_limit_time = unserialize($con);
        }
        $deal_time = $cat_limit_time[$tid];
        $deal_time = $deal_time ? $deal_time : 120;
        return $this->over_time($_timestamp, $deal_time);
    }

    //t1 当前时间，t2工作日(小时)
    private function over_time($t1, $t2) {
        if ($t2 == 0) {
            return $t1;
        }
        $t2_day = floor($t2 / 24);
        //echo $t2_day.' ';
        if ($t2_day >= 5) {
            $temp = $t1 + 7 * 24 * 3600;
            return over_time($temp, $t2 - 5 * 24);
        } else {
            $t1_w = (int) date_('w', $t1);  //$t1 是周几
            // echo $t1_w.'<br>';
            if (($t1_w + $t2_day) >= 5) {
                $temp = $t1 + $t2 * 3600 + 2 * 24 * 3600;
                return $temp;
            } else {
                $temp = $t1 + $t2 * 3600;
                return $temp;
            }
        }
    }

    /**
     * 更新办事状态
     * @global type $_timestamp
     */
    function update_banshi_status() {
        global $_timestamp;
        $this->check_request_data();
        extract($this->request_data);
        $error_msg = '';
        if (!$rowid) {
            $error_msg = '事项id不为空!';
            $this->output_result(0, $error_msg);
        } else if (!$check_department) {
            $error_msg = '处理部门不为空!';
        } else if (!$check_user) {
            $error_msg = '处理人不为空!';
        }
        if ($error_msg != '') {
            $this->output_result(0, $error_msg);
        }
        // 状态
        $status_arr = array(1, 2);
        $status = in_array($status, $status_arr) ? $status : 0;
        $status_map = array(0 => 0, 1 => 2, 2 => 3);
        $set_status = (int) $status_map[$status];
        $status_text = array(2 => '审核通过', 3 => '审核不通过');
        // 处理时间
        $check_time = date_("Y-m-d H:i", $_timestamp);
        $last_log = "{$check_department} {$check_user} {$check_time} {$status_text[$set_status]}";
        // 
        $newlog = $last_log;
        $reply_out && $newlog .= '<br/>对外回复：<br/><div style="border:1px solid #cccccc; padding:6px; width:80%;">' . $reply_out . '</div>';
        $reply_in && $newlog .= '<br/>内部说明：<br/><div style="border:1px solid #cccccc; padding:6px; width:80%;">' . $reply_in . '</div>';
        // 更新sql
        $sql = <<<ETO
            UPDATE oho_t_bstable SET endtime='{$_timestamp}',log=CONCAT(log,'{$newlog}'),
                msn_con='{$reply_out}',msn_con1 = '{$reply_in}',checked='0',is_send='1',
                lastlog='{$last_log}',`status`='{$set_status}'
            WHERE id='{$rowid}'
ETO;
        $this->db->update($sql);
        $this->output_result(1, '更新成功!');
    }

    /**
     * 获取警局列表
     */
    public function get_police_office() {
        if (is_array($this->request_data)) {
            extract($this->request_data);
        }
        $where = '';
        if (isset($pid) && $pid) {
            $where = "pid='{$pid}' and type='pcs'";
        } else {
            $where = "type='xjg'";
        }
        $sql = get_sql('oho_wxga_jg', 'id,title', $where);
        $list_data = get_result_array($sql);
        $this->output_result(1, 'success', $list_data);
    }

    /**
     * 获取新闻栏目
     */
    public function get_article_category() {
        if (is_array($this->request_data)) {
            extract($this->request_data);
        }
        $where = "siteid='103216' and settype=0";
        if (isset($pid)) {
            $where .= " and pid='{$pid}'";
        }
        $sql = get_sql('oho_custom_cat', 'cid as id,title,pid', $where);
        $list_data = get_result_array($sql);
        $this->output_result(1, 'success', $list_data);
    }

    /**
     * 获取办事列表
     */
    public function get_banshi() {
        $siteid = '103216';
        $list_data = array();
        $sql = "select * from oho_colonys_bs where siteid='{$siteid}' and (ifonline='1' or ifonline='2' or ifonline='3')";
        $query = $this->db->query($sql);
        while ($rt = $this->db->fetch_array($query)) {
            $tid = $rt['tid'];
            $title = $rt['subject'];
            $setflowid = 'setflow_' . $tid;
            if (file_exists(ROOT_PATH . 'files/' . $siteid . '/setflow_' . $tid . '.conf')) {
                $flow_str = file_get_contents(ROOT_PATH . 'files/' . $siteid . '/setflow_' . $tid . '.conf');
            } else {
                continue;
            }
            $object = simplexml_load_string($flow_str);
            $object = json_decode(json_encode($object), true);
            $step_a = $object['Steps']['Step'];
            $step_b = array();
            foreach ($step_a as $rt) {
                $step_b[] = $rt['BaseProperties']['@attributes'];
            }
            $step_d = array();
            foreach ($step_b as $rt) {
                $from = $rt['id'];
                $step_d[$from] = $rt;
            }
            //
            $step_a = $object['Actions']['Action'];
            $step_b = array();
            foreach ($step_a as $rt) {
                $step_b[] = $rt['BaseProperties']['@attributes'];
            }
            $step_c = array();
            foreach ($step_b as $rt) {
                $from = $rt['from'];
                $to = $rt['to'];
                $step_c[$from] = $to;
            }
            $step_e = array();
            //$a='begin';
            $step_e[] = $step_d['begin'];
            $this->parse_banshi_process('begin', $step_e, $step_c, $step_d);
            $api_data = array();
            foreach ($step_e as $rt) {
                $bzid = $rt['id'];
                if (file_exists(ROOT_PATH . 'files/' . $siteid . '/setflow_' . $tid . '_' . $bzid . '.conf')) {
                    $a_a = file_get_contents(ROOT_PATH . 'files/' . $siteid . '/setflow_' . $tid . '_' . $bzid . '.conf');
                    $a_a = unserialize($a_a);
                    foreach ($a_a as $a => $b) {
                        if ($b) {
                            $api_data[$bzid][$a] = $b;
                        }
                    }
                    //echo $source;
                } else {
                    $api_data[$bzid] = array();
                }
            }
            $list_data[] = array(
                'tid' => $tid,
                'title' => $title,
                'fields' => $api_data
            );
        }
        $this->output_result(1, 'success', $list_data);
    }

    // 解析流程
    function parse_banshi_process($a, &$step_e, &$step_c, &$step_d) {
        $b = $step_c[$a];
        if ($b) {
            $step_e[] = $step_d[$b];
            $this->parse_banshi_process($b, $step_e, $step_c, $step_d);
        }
    }

    /**
     * 返回数据
     * @param type $status
     * @param type $msg
     * @param type $data
     */
    function output_result($status, $msg, $data = array()) {
        $this->result['status'] = $status;
        $this->result['msg'] = $msg;
        $this->result['data'] = $data;
        echo json_encode($this->result);
        exit;
    }

    /**
     * 检测数据，可在此方法内做提交数据检查
     */
    private function check_request_data() {
        if (!$this->request_data || !is_array($this->request_data)) {
            $this->output_result(0, '参数有误!');
        }
        return true;
    }

}

?>
