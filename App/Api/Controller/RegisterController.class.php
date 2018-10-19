<?php
namespace Api\Controller;

use Think\Controller;

class RegisterController extends CommonController
{
    /**
     * 用户注册
     */
    public function index(){
        $user = I('user', '', 'trim');
        $passwd = I('pwd', '', 'trim');
        $phone_code = I('phone_code');
        $checkpwd = I('checkpwd');
        $name = I('name');
        $invitation_code = I('invitation_code');
        if(empty($user) || empty($passwd)){
            $code = '300';
           echo api_json(null,$code,D('Error')->getText($code));exit();
        }
//        if(empty($phone_code)){
//            echo api_json(null,'300','手机验证码为空');exit();
//        }
        //过滤匹配
        if(!preg_match('/1[0-9]{10}/', $user) || strlen($user) != 11) {
            if(!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $user)){
                echo api_json(null,'400','手机号码或邮箱格式不正确');exit();
            }else{
                $data['email'] = $user;
            }
        }else{
            $data['mphone'] = $user;
        }

//        if (strlen($passwd) < 6 || strlen($passwd) > 20 || $passwd != $checkpwd) {
//            echo api_json(null,'400','密码输入有误');exit();
//        }
//        if (mb_strlen($name, 'UTF8') < 2 || mb_strlen($name, 'UTF8') > 20) {
//            echo api_json(null,'400','用户名长度不符');exit();
//        }
        $user = M('users')->where("mphone='{$user}' or email='{$user}'")->find();
        if($user){
            echo api_json(null,'400','手机号或者邮箱已注册');exit();
        }
//       $phone_codes = get_code($phone);
//        if(!$phone_codes || ($phone_code != $phone_codes)){
//            echo api_json(null,'400','手机验证码不正确');exit();
//        }
        $data['password'] = password_hash($passwd, PASSWORD_DEFAULT);
        $data['code'] = $this->initcode();
        $data['create_time'] = datetimenew();
        if($invitation_code){
            $super = M('users')->field('id,one_superId,eth,node_earnings')->where('code=%d and deleted = 0 and status =0',$invitation_code)->find();
            if($super){
                $data['one_superId'] = $super['id'];
                $data['two_superId'] = $super['one_superId'];
                $poration = $this->get_node_level($super['id']);
            }
        }
        $result = M('users')->add($data);
        if(!$result){
            $code = '500';
            echo api_json(null,$code,D('Error')->getText($code));exit();
        }
        $code = '200';
        if(isset($poration) && $poration){
            $this->node_reward($super,$poration);
        }
        echo api_json(1,$code,D('Error')->getText($code));
    }
    /**
     * 获取节点奖励的级别
     *
     */
    public function get_node_level($super_id){
         $num = M('users')->where('one_superId='.$super_id)->count();
         if($num > 1000){
             $poration = 45;
         }elseif($num = 1000){
             $poration = 30;
         }elseif($num = 800){
             $poration = 20;
         }elseif($num = 500){
             $poration = 10;
         }elseif($num = 200){
             $poration = 5;
         }else{
             $poration = 0;
         }
        return $poration;
    }
    /**
     * 节点奖励分红
     * author:wmt
     * date:2018-10-19
     */
    public function node_reward($user,$poration){
        $amount = M('bonus_pool')->where('type = 1')->getField('eth');
        $trade['user_id'] = $user['id'];
        $trade['related_id'] = 0;
        //                $trade['trade_ids'] = '{' . $order['trade_id'] . '}';
        $trade['mode'] = 'income_node_reward';
        $trade['message'] = '邀请节点奖励收入';
        $trade['eth'] = $amount * 0.9 * $poration/100;
        $trade['status'] = 1;
        $trade_ids = M('trades')->add($trade);
        if($trade_ids){
            $payment['trade_id'] = $trade_ids;
            $payment['mode'] = 'eth';
            $payment['beamount'] = $user['eth'];
            $payment['afamount'] = ($user['eth']) + $trade['eth'];
            $payment['eth'] = $trade['eth'];
            $payment['status'] = 1;
            M('payments')->add($payment);
            M('users')->where("id =".$user['id'])->save(['eth' => ($user['eth']) + $trade['eth'],'node_earnings'=>($user['node_earnings'] + $trade['eth']), 'update_time' =>date('Y-m-d H:i:s',time())]);
            M('bonus_pool')->where('type = 1')->save(['eth'=>($amount - $trade['eth']),'update_time' =>date('Y-m-d H:i:s',time())]);
        }

    }
    /**
     * 找回密码
     */
    public function retrieve_pwd(){
        $phone = I('user');
        $passwd = I('pwd');
        $phone_code = I('phone_code');
        $checkpwd = I('checkpwd');
        if(empty($phone) || empty($checkpwd) || empty($passwd)){
            $code = '300';
            echo api_json(null,$code,D('Error')->getText($code));exit();
        }
//        if(empty($phone_code)){
//            echo api_json(null,'300','手机验证码为空');exit();
//        }
//        $phone_codes = get_code($phone);
//        if(!$phone_codes || ($phone_code != $phone_codes)){
//            echo api_json(null,'400','手机验证码不正确');exit();
//        }
        if (strlen($passwd) < 6 || strlen($passwd) > 20 || $passwd != $checkpwd) {
            echo api_json(null,'400','密码输入有误');exit();
        }
        $user = M('users')->where("mphone='{$phone}' or email='{$phone}'")->find();
        if(!$user){
            echo api_json(null,'400','手机号未注册');exit();
        }
        $result = M('users')->where('id='.$user['id'])->save(['password'=>password_hash($passwd, PASSWORD_DEFAULT)]);
        if($result){
            $code = '200';
            echo api_json(null,$code,D('Error')->getText($code));exit();
        }else{
            $code = '500';
            echo api_json(null,$code,D('Error')->getText($code));exit();
        }
    }

    /**
     * 获取验证码
     */
    public function get_captcha(){
        $phone = I('phone');
        $type = I('type');
        if(empty($phone) || empty($type)){
            $code = '300';
            echo api_json(null,$code,D('Error')->getText($code));exit();
        }
        $message = code_source($type);
//        var_dump($message);
        $captcha= sender_code($phone,$message);
        if($captcha['status'] == 'ok'){
            $code = '200';
            echo api_json($captcha['code'],$code,D('Error')->getText($code));exit();
        }else{
            $code = '600';
            echo api_json(null,$code,$captcha['data']);exit();
        }
    }
}