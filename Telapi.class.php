<?php
// 设置时区
date_default_timezone_set('PRC');

/**
 * 语音通话接口操作类
 * @author Li Zogheng
 */
class Telapi
{
    // 调用地址
    private $api_url = 'https://api.emic.com.cn';
    // 调用版本
    private $soft_version = '20160118';
    // 主账户
    private $account_sid = '**************************';
    private $auth_token = ' = '**************************';';
    // 子账户
    private $sub_account_sid = ' = '**************************';';
    private $sub_auth_token = ' = '**************************';';
    // 应用ID
    private $appid = ' = '**************************';';
    private $sig_parameter = '';
    private $sub_sig_parameter = '';
    private $auth = '';
    private $sub_auth = '';
    // http头
    private $http_headers = array();
    private $result = array();
    private $user = array();
    // 账户类型，1：主账户，2：子账户
    public $account_type = 1;
    public $debug = false;
    public $db;


    function __construct($account_type = 1)
    {
        // 账号类型
        $this->account_type = $account_type;
        // 当前时间
        $time = date('YmdHis');
        // 主账户令牌
        $param_str = $this->account_sid . $this->auth_token . $time;
        $this->sig_parameter = strtoupper(md5($param_str));
        // 子账户令牌
        $sub_param_str = $this->sub_account_sid . $this->sub_auth_token . $time;
        $this->sub_sig_parameter = strtoupper(md5($sub_param_str));
        //
        $auth = "{$this->account_sid}:{$time}";
        $this->auth = base64_encode($auth);
        //
        $auth = "{$this->sub_account_sid}:{$time}";
        $this->sub_auth = base64_encode($auth);
    }

    /**
     * 设置http头部信息
     * @param int $type 类型，1：默认通用，2：上传文件
     */
    function set_headers($type = 1)
    {
        $auth = $this->account_type == 1 ? $this->auth : $this->sub_auth;
        // 头信息
        $headers = array();
        $headers[] = 'Accept:application/xml';
        $headers[] = $type == 1 ? 'Content-Type:application/xml;charset=utf-8' : 'Content-Type:application/octet-stream';
        //$headers[] = 'Content-Length:'.strlen($data);
        $headers[] = 'Authorization:' . $auth;
        $this->http_headers = $headers;
        return true;
    }

    /**
     * 设置请求地址
     * @param string $method
     * @param string $param
     */
    function set_http_url($method = '', $param = '')
    {
        if ($this->account_type == 1) {
            $accounts = "Accounts/{$this->account_sid}";
            $sig = $this->sig_parameter;
        } else {
            $accounts = "SubAccounts/{$this->sub_account_sid}";
            $sig = $this->sub_sig_parameter;
        }
        $this->http_url = "{$this->api_url}/{$this->soft_version}/{$accounts}/{$method}?sig={$sig}";
        $param && $this->http_url .= "&{$param}";
        return true;
    }

    function do_request($data = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->http_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->http_headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $handle = curl_exec($ch);
//        print_r($info = curl_getinfo($ch));
        curl_close($ch);
        if ($handle) {
            $result = simplexml_load_string($handle);
            $this->result = @json_decode(@json_encode($result), true);
        }
        $this->result['handle'] = (string)$handle;
        return $this->result;
    }

    function get_result()
    {
        print_r($this->result);
    }

    public function account_info()
    {
        $this->set_http_url('AccountInfo');
        $this->do_request();
        $this->get_result();
    }

    public function calls_notify($voice_id, $phone)
    {
        $this->set_headers();
        $data = <<<ETO
<?xml version="1.0" encoding="UTF-8"?>
<voiceNotify>
    <appId>{$this->appid}</appId>
    <voiceId>{$voice_id}</voiceId>
    <to>{$phone}</to>
    <userData>12345678</userData>
</voiceNotify>
ETO;
        $this->set_http_url('Calls/voiceNotify');
        $result = $this->do_request($data);
        // 记录
        $call_data = array(
            'voice_id' => $voice_id,
            'phone' => $phone,
            'call_time' => time(),
            'result_code' => $result['respCode'],
            'result' => $result['handle'],
            'call_id' => $result['voiceNotify']['callId']
        );
        insert_data('oho_call_voice_log', $call_data);
        return $call_data;
    }

    /**
     * 上传音频文件
     * @param $voice    内容
     */
    public function upload_voice($voice)
    {
        $this->set_headers(1);
        $param = "appId={$this->appid}";
        $this->set_http_url('Voice/uploadVoice', $param);
        $result = $this->do_request($voice);
        $data = array('status' => $result['respCode'], 'data' => $result['uploadVoice']);
        return $data;
    }

    /**
     * 检测语言文件是否过期，过期重新上传
     * @return bool
     */
    public function check_voice_id()
    {
        // voice_id文件地址
        $voice_file = ROOT_PATH . 'files/voice_id.txt';
        if (time() - filectime($voice_file) < 26 * 60) {
            return true;
        }
        $sql = "select * from oho_call_voice_log where result_code = '0' order by id desc limit 1";
        $data = $this->db->get_one($sql);
        //不存在，或者大于30分钟（用26分钟，提前判断）没有拨打电话，此时，音频文件会失效，需重新上传
        if (!$data || time() - $data['call_time'] > 26 * 60) {
            $file = ROOT_PATH . 'files/voice.wav';
            $file_con = file_get_contents($file);
            $result = $this->upload_voice($file_con);
            if ($result['status'] == 0) {
                $voice_id = $result['data']['voiceId'];
                // 保存voice_id
                file_put_contents($voice_file, $voice_id);
            } else {
                die('error');
            }
        }
        return true;
    }

    /**
     * 获取voice_id
     * @return int
     */
    function get_voice_id()
    {
        $this->check_voice_id();
        $file = ROOT_PATH . 'files/voice_id.txt';
        $voice_id = file_get_contents($file);
        return intval($voice_id);
    }
}


// 示例
$tel_api = new Telapi();
$tel_api->db = $db;
// 获取语音id
$voice_id = $tel_api->get_voice_id();
$phone = '13688888888';
// 语音通话，使用子账户
$tel_api->account_type = 2;
$call_data = $tel_api->calls_notify($voice_id, $phone);
?>
