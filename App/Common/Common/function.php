<?php

/**
 * 输出json,给手机端调用
 * @param $data 返回数据
 * @param $code 代码编号
 * @param $message 结果消息
 * @return json
 */
function api_json($data = array(), $code, $message = '') {

    $return = array(
        'data' => $data,
        'code' => $code,
        'message' => $message
    );

    return json_encode($return);
}

//短信发送重新书写
function sender_code($phone,$message) {
//查找最近发送情况
    $map="select count(id) AS coun_id from phone_code where  created_at >= current_timestamp - interval '2 minutes'  and phone='{$phone}'";
    $count_code=M()->query($map);

    if (intval($count_code['coun_id']) > 0) {
        return ['status' => 'no', 'data' => '获取验证码间隔不能短于两分钟，如有疑问请联系客服'];
    }
    $template = 'http://106.ihuyi.cn/webservice/sms.php?method=Submit&account=cf_tshw&password=azsxdcfvgbhn118&mobile=%s&content=您的验证码是：%s（十分钟内有效）。如非本人操作，请忽略本短信。';
    $code = sprintf('%06d', rand() % 1000000);
    $api = sprintf($template, urlencode($phone), urlencode($code));
    $content = file_get_contents($api);
    $xml = simplexml_load_string($content);

    if ($xml->code->__toString() === '2') {
        $status="发送成功";
        $data=array("message"=>$message,"phone"=>$phone,"code"=>$code,"status"=>$status);
        $id=M('phone_code')->add($data);
        return ['status' => 'ok', 'data' => 120, 'code' => $code];
    } else {
        $status="发送失败";
        $data=array("message"=>$message,"phone"=>$phone,"code"=>$code,"status"=>$status);
        $id=M('phone_code')->add($data);
        return ['status' => 'no', 'data' => $xml->msg->__toString()];

    }
//获取短信验证码
function get_code($phone){
    //查找最近发送情况
    $map="select code from phone_code where  created_at <= current_timestamp - interval '2 minutes'  and phone='{$phone}'";
    $count_code=M()->query($map);
    return $count_code[0]['code'];
}

//定义短信验证码来源
function code_source($type){
    switch($type){
        case 1:
            $text = '手机注册获取';
            break;
        case 2:
            $text = '修改密码获取';
            break;
        default:
            $text = '手机注册获取';
            break;
    }

    return $text;
}


}
?>


