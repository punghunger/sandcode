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
        // some codes
        // 返回信息
        $this->output_result(1, '发布成功!', array('id' => $tid));
    }

   

    /**
     * 上传文件
     */
    function upload_file() {
        $this->check_request_data();
        extract($this->request_data);
        $siteid = '1';
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
            $file_data[] = 'http://www.xxx.com/' . $file;
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
        // some codes
        if (true) {
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


    /**
     * 更新办事状态
     * @global type $_timestamp
     */
    function update_banshi_status() {
        global $_timestamp;
        $this->check_request_data();
        extract($this->request_data);
        // some codes
        $this->output_result(1, '更新成功!');
    }

    /**
     * 获取新闻栏目
     */
    public function get_article_category() {
        if (is_array($this->request_data)) {
            extract($this->request_data);
        }
        // some codes
        $this->output_result(1, 'success', $list_data);
    }

    /**
     * 获取办事列表
     */
    public function get_banshi() {
        $list_data = array();
        // some codes
        $this->output_result(1, 'success', $list_data);
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
