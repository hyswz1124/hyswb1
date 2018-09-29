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
?>