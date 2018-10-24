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

    echo  json_encode($return); exit;
}

//短信发送重新书写
function sender_code($phone,$message)
{
//查找最近发送情况
    $map = "select count(id) AS coun_id from yt_phone_code where  created_at >= current_timestamp - interval '2 minutes'  and phone='{$phone}'";
    $count_code = M()->query($map);

    if (intval($count_code['coun_id']) > 0) {
        return ['status' => 'no', 'data' => '获取验证码间隔不能短于两分钟，如有疑问请联系客服'];
    }
    $template = 'http://106.ihuyi.cn/webservice/sms.php?method=Submit&account=cf_tshw&password=azsxdcfvgbhn118&mobile=%s&content=您的验证码是：%s（十分钟内有效）。如非本人操作，请忽略本短信。';
    $code = sprintf('%06d', rand() % 1000000);
    $api = sprintf($template, urlencode($phone), urlencode($code));
    $content = file_get_contents($api);
    $xml = simplexml_load_string($content);

    if ($xml->code->__toString() === '2') {
        $status = "发送成功";
        $data = array("message" => $message, "phone" => $phone, "code" => $code, "status" => $status);
        $id = M('phone_code')->add($data);
        return ['status' => 'ok', 'data' => 120, 'code' => $code];
    } else {
        $status = "发送失败";
        $data = array("message" => $message, "phone" => $phone, "code" => $code, "status" => $status);
        $id = M('phone_code')->add($data);
        return ['status' => 'no', 'data' => $xml->msg->__toString()];

    }
}
//获取短信验证码
function get_code($phone){
    //查找最近发送情况
    $map="select code from yt_phone_code where  created_at <= current_timestamp - interval '2 minutes'  and phone='{$phone}'";
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

/**
 * 此方法程序为交易结算
 * @author  wmt<1027918160@qq.com>
 * @param type $order
 * @return type
 */
function trade_settle($trade_id) {
    $settle_trade = M('trades')->where("status = 0 and id = {$trade_id}")->find();
    if (empty($settle_trade)) {
        return ['status' => 'no', 'data' => '交易不存在'];
    }
    $amount = $settle_trade['eth'];
    if ($settle_trade['mode'] !== 'unlock') {
        $users = [
            'user_recommender_one' => [
                'id' => 0,
                'status'=>0,
                'amount' => 0.08 * $amount,
                'message' => '推荐人一代分成'
            ],
            'user_recommender_two' => [
                'id' => 0,
                'status'=>0,
                'amount' =>0.05 * $amount, //
                'message' => '推荐人二代分成'
            ],
            'divide_bonus_pool_one' => [
                'id' => 0,
                'status'=>0,
                'amount' => 0.05 * $amount,
                'message' => '推荐人一代分红奖金池收入'
            ],
            'divide_bonus_pool_two' => [
                'id' => 0,
                'status'=>0,
                'amount' =>0.03 * $amount, //0.2 * $amount['gain'],
                'message' => '推荐人二代分红奖金池收入'
            ],
            'development_bonus_pool_one' => [
                'id' => 0,
                'status'=>0,
                'amount' => 0.02 * $amount,
                'message' => '推荐人一代发展奖金池收入'
            ],
            'development_bonus_pool_two' => [
                'id' => 0,
                'status'=>0,
                'amount' =>0.02 * $amount, //0.2 * $amount['gain'],
                'message' => '推荐人二代发展奖金池收入'
            ],
//                'vendor' => [
//                    'id' => 0,
//                    'amount' => $amount,
//                    'message' => '订单' . $order_ids . '收益'
//                ]
        ];

        $userd = M('users')->find($settle_trade['user_id']);
        if($userd['one_superId']){
            $users['user_recommender_one']['id'] = $userd['one_superId'];
            $users['user_recommender_one']['status'] = $users['divide_bonus_pool_one']['status'] = $users['development_bonus_pool_one']['status'] = 1;
            $oneSuperTrade = M('trades')->where("(mode = 'recharge' or mode = 'unlock') and user_id=".$userd['one_superId'])->order('id desc')->limit(1)->select();
            if($oneSuperTrade[0]['eth'] < $amount){
                $users['user_recommender_one']['amount'] = 0.08 * $oneSuperTrade[0]['eth'];
                $users['divide_bonus_pool_one']['amount'] = 0.05 * $oneSuperTrade[0]['eth'];
                $users['development_bonus_pool_one']['amount'] =  0.02 * $oneSuperTrade[0]['eth'];
            }

        }
        if($userd['two_superId']){
            $users['user_recommender_two']['id'] = $userd['two_superId'];
            $users['user_recommender_two']['status'] = $users['divide_bonus_pool_two']['status'] = $users['development_bonus_pool_two']['status'] = 1;
            $twoSuperTrade = M('trades')->where("(mode = 'recharge' or mode = 'unlock') and user_id=".$userd['two_superId'])->order('id desc')->limit(1)->select();
            if($twoSuperTrade[0]['eth'] < $amount){
                $users['user_recommender_two']['amount'] = 0.08 * $twoSuperTrade[0]['eth'];
                $users['divide_bonus_pool_two']['amount'] = 0.05 * $twoSuperTrade[0]['eth'];
                $users['development_bonus_pool_two']['amount'] =  0.02 * $twoSuperTrade[0]['eth'];
            }
        }

        foreach ($users as $mode => $user) {
            if (empty($user['id']) && $user['status'] != 1) {
                continue;
            }
            $user['amount'] = round($user['amount'],4);
            $trade['user_id'] = $user['id'];
            $trade['related_id'] = $settle_trade['user_id'];
//                $trade['trade_ids'] = '{' . $order['trade_id'] . '}';
            $trade['mode'] = 'income_' . $mode;
            $trade['message'] = $user['message'];
            $trade['eth'] = $user['amount'];
            $trade['status'] = 1;
            $trade_ids = M('trades')->add($trade);

            if ($mode === 'user_recommender_one' || $mode === 'user_recommender_two') {
                $owner = M('users')->field('eth')->find($user['id']);
                $payment['trade_id'] = $trade_ids;
                $payment['mode'] = 'finances_eth';
                $payment['beamount'] = $owner['eth'];
                $payment['afamount'] = ($owner['eth']) + $user['amount'];
                $payment['eth'] = $user['amount'];
                $payment['status'] = 1;
                M('payments')->add($payment);

                M('users')->where("id = {$user['id']}")->save(['eth' => ($owner['eth']) + $user['amount'],'dynamic_earnings'=>$owner['dynamic_earnings'] +  $user['amount'], 'update_time' => date('Y-m-d H:i:s',time())]);
            }elseif($mode === 'divide_bonus_pool_one' || $mode === 'divide_bonus_pool_two'){
                M('bonus_pool')->where('type = 1')->setInc('eth',$user['amount']);
            }else{
                M('bonus_pool')->where('type = 2')->setInc('eth',$user['amount']);
            }
        }
        $payment['trade_id'] = $trade_id;
        $payment['mode'] = 'eth';
        $payment['beamount'] = $userd['eth'];
        $payment['afamount'] = ($userd['eth']) + $amount;
        $payment['eth'] = $amount;
        $payment['status'] = 1;
        M('payments')->add($payment);
        M('users')->where("id = {$settle_trade['user_id']}")->setInc('eth',$amount);
        M('users')->where("id = {$settle_trade['user_id']}")->setInc('all_eth',$amount);
    }

    M('trades')->where("id  = {$trade_id}")->save(['status' => 1, 'update_time' => date('Y-m-d H:i:s',time())]);
    airdrop_reward($settle_trade['user_id']);

    return ['status' => 'ok', 'data' => '结算成功'];
}
/**
 * 此方法程序为挂单订单结算
 * @author  wmt<1027918160@qq.com>
 * @param type $order
 * @return type
 */

function order_settle($order,$current_user){
    $settle_trade = M('trades')->where("status = 1 and user_id = {$order['user_id']}  and order_no = {$order['order_no']}")->find();
    if (empty($settle_trade)) {
        return ['status' => 'no', 'data' => '订单结算失败'];
    }
    if (empty($order)) {
        return ['status' => 'no', 'data' => '订单结算失败'];
    }
    $amount = $order['eth'];
    $commission = $amount;
    if($settle_trade['mode'] == 'list_deal'){
        $users = [
            'buyers_deal' => [
                'id' => 0,
                'status'=>1,
                'amount' => $amount,
                'message' => '挂单'.$order['order_no'].'购买扣款'
            ],
            'buyers_deal_token' => [
                'id' => 0,
                'status'=>1,
                'amount' => $order['token'],
                'message' => '订单' . $order['order_no'] .'购买收入积分'
            ],
            'buy_back_pool' => [
                'id' => 0,
                'status'=>1,
                'amount' => 0.1 * $commission,
                'message' => '订单' . $order['order_no'] .'挂单交易回购奖金池收入'
            ],
            'divide_bonus_pool' => [
                'id' => 0,
                'status'=>1,
                'amount' =>0.05 * $commission, //
                'message' => '订单' . $order['order_no'] .'挂单交易(分红)奖金池收入'
            ],
            'community_pool' => [
                'id' => 0,
                'status'=>1,
                'amount' => 0.02 * $commission,
                'message' => '订单' . $order['order_no'] .'挂单交易社区收入'
            ],
            'airdrop_pool' => [
                'id' => 0,
                'status'=>1,
                'amount' =>0.03 * $commission, //0.2 * $amount['gain'],
                'message' => '订单' . $order['order_no'] .'挂单交易空投奖金池收入'
            ],
            'buyers_deal_reward' => [
                'id' => 0,
                'status'=>1,
                'amount' => 0.05 * $commission,
                'message' => '订单' . $order['order_no'] .'挂单交易，买家奖励'
            ],
            'deal' => [
                    'id' => 0,
                   'status'=>1,
                    'amount' => $amount * 0.75,
                    'message' => '订单' . $order['order_no'] . '收益'
                ]
        ];
        $users['buyers_deal_reward']['id'] = $users['buyers_deal']['id'] = $current_user['id'];
        $users['deal']['id'] = $order['user_id'];
        foreach($users as $mode=>$user){
            if(empty($user['status'])){
                continue;
            }
            $user['amount'] = round($user['amount'],4);
            $trade['user_id'] = $user['id'];
            $trade['order_no'] = $order['order_no'];
            $trade['related_id'] = $settle_trade['user_id'];
//                $trade['trade_ids'] = '{' . $order['trade_id'] . '}';
            if($mode === 'buyers_deal'){
                $trade['mode'] = $mode;
            }else{
                $trade['mode'] = 'income_' . $mode;
            }
            $trade['message'] = $user['message'];
            $trade['eth'] = $user['amount'];
            $trade['status'] = 1;
            $trade_ids = M('trades')->add($trade);
            if($mode === 'buyers_deal'){
                $owner = M('users')->field('eth')->find($user['id']);
                $payment['trade_id'] = $trade_ids;
                $payment['mode'] = 'eth';
                $payment['beamount'] = $owner['eth'];
                $payment['afamount'] = ($owner['eth']) - $user['amount'];
                $payment['eth'] = $user['amount'];
                $payment['status'] = 1;
                M('payments')->add($payment);
                M('users')->where("id = {$user['id']}")->save(['eth' => ($owner['eth']) - $user['amount'], 'update_time' =>date('Y-m-d H:i:s',time())]);

            }elseif ($mode === 'buyers_deal_reward' || $mode === 'buyers_deal_token') {
                $owner = M('users')->field('token')->find($user['id']);
                $payment['trade_id'] = $trade_ids;
                $payment['mode'] = 'token';
                $payment['betoken'] = $owner['token'];
                $payment['aftoken'] = ($owner['token']) + $user['amount'];
                $payment['token'] = $user['amount'];
                $payment['status'] = 1;
                M('payments')->add($payment);

                M('users')->where("id = {$user['id']}")->save(['token' => ($owner['token']) + $user['amount'], 'update_time' =>date('Y-m-d H:i:s',time())]);
            }elseif ($mode === 'deal') {
                $owner = M('users')->field('eth')->find($user['id']);
                $payment['trade_id'] = $trade_ids;
                $payment['mode'] = 'eth';
                $payment['beamount'] = $owner['eth'];
                $payment['afamount'] = ($owner['eth']) + $user['amount'];
                $payment['eth'] = $user['amount'];
                $payment['status'] = 1;
                M('payments')->add($payment);

                M('users')->where("id = {$user['id']}")->save(['eth' => ($owner['eth']) + $user['amount'], 'update_time' =>date('Y-m-d H:i:s',time())]);
            }elseif($mode === 'buy_back_pool'){
                M('bonus_pool')->where('type = 5')->setInc('eth',$user['amount']);
            }elseif($mode === 'divide_bonus_pool'){
                M('bonus_pool')->where('type = 1')->setInc('eth',$user['amount']);
            }elseif($mode === 'community_pool'){
                M('bonus_pool')->where('type = 4')->setInc('eth',$user['amount']);
            }elseif($mode === 'airdrop_pool'){
                M('bonus_pool')->where('type = 3')->setInc('eth',$user['amount']);
            }
        }
        M('orders')->where('id='.$order['id'])->save(['status'=>1,'update_time' =>date('Y-m-d H:i:s',time())]);
    }
    return ['status' => 'ok', 'data' => '结算成功'];
}
/**
 * 此方法程序为充值额度空投奖励结算
 * @author  wmt<1027918160@qq.com>
 * @param type airdrop_pool
 * @return type
 */
function airdrop_reward($user_id){
        $total_eth = M('trades')->where("mode = 'unlock' and status = 1 and user_id=".$user_id)->sum('eth');
        if($total_eth < 0.1){
            return false;
        }
        $airdrop = M('airdrop_pool_dispose')->where("status = 1 and min_amount <= {$total_eth} and ({$total_eth} < max_amount or max_amount is null)");
        if(!$airdrop){
            return false;
        }
        $owner = M('users')->field('id,eth,paradrop_earnings')->where('id='.$user_id)->find();
        $amount = M('bonus_pool')->where('type = 3')->getField('eth');
        $trade['user_id'] = $user_id;
        $trade['related_id'] = 0;
    //                $trade['trade_ids'] = '{' . $order['trade_id'] . '}';
        $trade['mode'] = 'income_airdrop_reward';
        $trade['message'] = '充值空投奖励收入';
        $trade['eth'] = round($amount * $airdrop['proportion']/100,4);
        $trade['status'] = 1;
        $trade_ids = M('trades')->add($trade);
       if($total_eth){
           $payment['trade_id'] = $trade_ids;
           $payment['mode'] = 'eth';
           $payment['beamount'] = $owner['eth'];
           $payment['afamount'] = ($owner['eth']) + $trade['eth'];
           $payment['eth'] = $trade['eth'];
           $payment['status'] = 1;
           M('payments')->add($payment);
           M('users')->where("id =".$user_id)->save(['eth' => ($owner['eth']) + $trade['eth'],'paradrop_earnings'=>($owner['paradrop_earnings'] + $trade['eth']), 'update_time' =>date('Y-m-d H:i:s',time())]);
           M('bonus_pool')->where('type = 3')->save(['eth'=>($amount - $trade['eth']),'update_time' =>date('Y-m-d H:i:s',time())]);
       }
}

/**
 * Request Headers
 * @param    string $params 获取headers参数
 * @return    array | string
 */
function request_headers($params = '')
{
    // If header is already defined, return it immediately
    /*
    if (!empty($this->headers)) {
        return $this->headers;
    }*/

    // In Apache, you can simply call apache_request_headers()
    if (function_exists('apache_request_headers')) {
        //return apache_request_headers();
    }

    //$this->headers['Content-Type'] = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : @getenv('CONTENT_TYPE');
    $headers = array();
    if (!empty($params)) {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', strtolower($params)));
        return $_SERVER[$key];
    }
    foreach ($_SERVER as $key => $val) {
        if (sscanf($key, 'HTTP_%s', $header) === 1) {
            // take SOME_HEADER and turn it into Some-Header
            $header = str_replace('_', ' ', strtolower($header));
            $header = str_replace(' ', '-', ucwords($header));

            $headers[$header] = $val;
            //$this->headers[$header] = $this->_fetch_from_array($_SERVER, $key, $xss_clean);
        }
    }

    return $headers;
}

/**
 * Gets the value of an environment variable. Supports boolean, empty and null.
 *
 * @param  string $key
 * @param  string $default
 * @return mixed
 */
function env($key, $default = '')
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return;
    }
    if (strlen($value) > 1) {
        $value = trim($value, '"');
    }

    return $value;
}

/**
 * 前端用户密码hash
 * @param string $password
 * @param string $halt
 * @return string
 */
function hash_password($password, $halt = '')
{
    return sha1('tos_' . $password . '_' . $halt);
}

/*
 * 生成4位数字短信验证码
 * @return string
 */
function generate_m_code()
{
    list($s1, $s2) = explode(' ', microtime());
    return substr($s1, 2, 4);
}

/**
 * 用生日计算年龄
 * @param $birthday
 * @return int
 */
function birthday($birthday)
{
    $age = strtotime($birthday);
    if ($age === false) {
        return 0;
    }
    list($y1, $m1, $d1) = explode("-", date("Y-m-d", $age));
    $now = strtotime("now");
    list($y2, $m2, $d2) = explode("-", date("Y-m-d", $now));
    $age = $y2 - $y1;
    if ((int)($m2 . $d2) < (int)($m1 . $d1)) {
        $age -= 1;
    }
    return $age;
}

/**
 * 判断时间格式是否正确
 * @param string $param  输入的时间
 * @param string $format 指定的时间格式
 * @return boolean
 */
function is_datetime($param = '', $format = 'Y-m-d H:i:s')
{
    return date($format, strtotime($param)) === $param;
}

/**
 * 从二维数组中获取某个键值,并返回其数组
 * @param array  $array =array(
 *                      array($key => '',)
 *                      array($key => '',)
 *                      array($key => '',)
 *                      )
 * @param string $key
 * @return array|bool
 */
function get_array_value_to_array($array, $key)
{
    if (!is_array($array)) {
        return false;
    }
    $result = array();
    foreach ($array as $item) {
        if (!is_array($item)) {
            $item = (array)$item;
        }
        $result[] = $item[$key];
    }
    return $result;
}

/**
 * 从二维数组中获取某个键值,并返回以其为键的关联数组
 * @param array  $array =array(
 *                      array($key => '',)
 *                      array($key => '',)
 *                      array($key => '',)
 *                      )
 * @param string $key
 * @return array|bool
 */
function get_array_value_to_map_array($array, $key)
{
    if (!is_array($array)) {
        return false;
    }
    $result = array();
    foreach ($array as $item) {
        if (!is_array($item)) {
            $item = (array)$item;
        }
        if (isset($item[$key])) {
            $result[$item[$key]] = $item;
        } else {
            $result[] = $item;
        }
    }
    return $result;
}

/**
 * 从二维数组中删除某个键值
 * @param array  $array =array(
 *                      array($key => '',)
 *                      array($key => '',)
 *                      array($key => '',)
 *                      )
 * @param string $key
 * @return bool
 */
function remove_array_value(&$array, $key)
{
    if (!is_array($array)) {
        return false;
    }
    foreach ($array as &$item) {
        if (!is_array($item)) {
            $item = (array)$item;
        }
        if (isset($item[$key])) {
            unset($item[$key]);
        }
        //$result[] = $item[$key];
    }
    return true;
}

/**
 * 从配置文件取数组得对应值
 * @param  array(
 *                      array($key => '',)
 *                      array($key => '',)
 *                      array($key => '',)
 *                      )
 * @param string $key
 * @return array|bool
 */
function get_config_value_by_key($array_name, $key)
{
    $result = C($array_name);
    if(is_array($result))
        return  $result[$key];
    return '';
}

/**
 * 显示时间，供模板显示用，时间戳为0，不显示为1970-1-1，显示为''
 * $format 时间格式,$time 时间戳
 */
function show_unix_time($time,$format = 'Y-m-d H:i:s')
{
    if(empty($time))
        return '';
    return date($format,$time);
}

/**
 * 客户端显示时间
 * @param int $time 时间戳
 * @return string
 */
function client_show_time($time)
{
    $diff = time() - (int)$time;
    if ($diff <= 0) {
        return '现在';
    }
    if ($diff < 60) {
        return $diff . '秒前';
    }
    if ($diff < 3600) {
        $minutes = (int)($diff / 60);
        return $minutes . '分钟前';
    }
    if ($diff < 3600 * 24) {
        $hours = (int)($diff / 3600);
        return $hours . '小时前';
    }
    $days = (int)($diff / (3600 * 24));
    if ($days > 31) {
        list($y1, $m1, $d1) = explode("-", date("Y-m-d", $time));
        $now = strtotime("now");
        list($y2, $m2, $d2) = explode("-", date("Y-m-d", $now));
        $years = $y2 - $y1;
        $months = $m2 - $m1;
        if($years > 0){
            $months = $m2 - $m1 + 12;
        }
        if ((int)($m2 . $d2) < (int)($m1 . $d1)) {
            $years -= 1;
        }
        if ($years > 0) {
            return $years . '年前';
        } else {
            if ($d2 < $d1) {
                $months -= 1;
            }
            return $months . '个月前';
        }
    } else {
        return $days . '天前';
    }

}


/**
 * plist编码
 * @param mixed  $data     数据
 * @param string $root     根节点名
 * @param string $item     数字索引的子节点名
 * @param string $attr     根节点属性
 * @param string $id       数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 * @return string
 */
function plist_encode($data, $root = 'array', $item = 'dict', $attr = '', $id = 'id', $encoding = 'utf-8')
{
    if (is_array($attr)) {
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? '' : " {$attr}";
    $plist = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $plist .= '<!DOCTYPE plist PUBLIC "-//Apple Computer//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">';
    $plist .= '<plist version="1.0">';
    $plist .= "<{$root}{$attr}>";
    $plist .= data_to_plist($data, $item);
    $plist .= "</{$root}>";
    $plist .= "</plist>";
    return $plist;
}

/**
 * 数据XML编码
 * @param mixed  $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id   数字索引key转换为的属性名
 * @return string
 */
function data_to_plist($data, $item = 'dict')
{
    $xml = $attr = '';
    foreach ($data as $key => $val) {

        $xml .= "<{$key}>";
        $xml .= (is_array($val) || is_object($val)) ? data_to_plist($val, $item) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}

/**
 * 生成文件名
 * @param string $origin 原始文件名
 * @param string $key    前缀
 * @return string
 */
function unique_file_name($origin, $key = 'to')
{
    $key = substr(session_id(), 0, 4);
    $str = pathinfo($origin, PATHINFO_EXTENSION);
    return uniqid($key) . (empty($str) ? '' : '.') . $str;
}

/**
 * 生成订单号
 * @param string $postfix
 * @return string
 */
function generate_order_no($postfix = '')
{
    list($usec, $sec) = explode(" ", microtime());
    $msec = round($usec * 1000);
    return date('YmdHis', time()) . $msec . $postfix;
}

/**
 * 计算两个经纬度之间的距离
 * @param float $latitude1
 * @param float $longitude1
 * @param float $latitude2
 * @param float $longitude2
 * @return float(千米)
 */
function get_distance($latitude1, $longitude1, $latitude2, $longitude2)
{
    $EARTH_RADIUS = 6371.393;
    $radLat1 = $latitude1 * M_PI / 180.0;
    $radLat2 = $latitude2 * M_PI / 180.0;
    $a = $radLat1 - $radLat2;
    $b = ($longitude1 * M_PI / 180.0) - ($longitude2 * M_PI / 180.0);
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $s = $s * $EARTH_RADIUS;
    return round($s, 3);
}

/**
 * 获取当前页面完整URL地址
 */
function get_url() {
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
    $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
    return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}

/*
 * 随机字符串
 */
function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}


function  encode ( $string  =  '' ,  $skey  =  'hys' ) {
    if(!$string){
        return 0;
    }

    $strArr  =  str_split ( base64_encode ( $string ));
    $strCount  =  count ( $strArr );
    foreach ( str_split ( $skey ) as  $key  =>  $value )
        $key  <  $strCount  &&  $strArr [ $key ].= $value ;
    return  str_replace (array( '=' ,  '+' ,  '/' ), array( 'O0O0O' ,  'o000o' ,  'oo00o' ),  join ( '' ,  $strArr ));
}


function  decode ( $string  =  '' ,  $skey  =  'hys' )
{
    if (!$string) {
        return $string;
    }
    $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
    $strCount = count($strArr);
    foreach (str_split($skey) as $key => $value)
        $key <= $strCount && isset($strArr [$key]) && $strArr [$key][1] === $value && $strArr [$key] = $strArr [$key][0];
    return base64_decode(join('', $strArr));
}

function dd($string = ''){
    var_dump($string);
    exit;
}
function datetimenew(){
    return date('Y-m-d H:i:s', time());
}


//判断数字有几位小数
function getFloatLength($num) {
    $count = 0;

    $temp = explode ( '.', $num );

    if (sizeof ( $temp ) > 1) {
        $decimal = end ( $temp );
        $count = strlen ( $decimal );
    }

    return $count;
}
?>


