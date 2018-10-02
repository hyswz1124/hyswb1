<?php
namespace Api\Controller;

use Think\Controller;

class RegisterController extends CommonController
{
    public function indexs()
    {
        var_dump(222);
    }
    public function index(){
        $phone = I('phone');
        $email = I('emaill');
        $passwd = I('pwd');
        $phone_code = I('phone_code');
        $checkpwd = I('checkpwd');
        $name = I('name');
        $invitation_code = I('invitation_code');
        if(empty($phone) || empty($email) || empty($passwd)){
            $code = '300';
           echo api_json(null,$code,D('Error')->getText($code));exit();
        }
//        if(empty($phone_code)){
//            echo api_json(null,'300','手机验证码为空');exit();
//        }
        //过滤匹配
        if (!preg_match('/1[0-9]{10}/', $phone) || strlen($phone) != 11) {
            echo api_json(null,'400','手机号码格式不正确');exit();
        }
        if (strlen($passwd) < 6 || strlen($passwd) > 20 || $passwd != $checkpwd) {
            echo api_json(null,'400','密码输入有误');exit();
        }
//        if (mb_strlen($name, 'UTF8') < 2 || mb_strlen($name, 'UTF8') > 20) {
//            echo api_json(null,'400','用户名长度不符');exit();
//        }
        $user = M('users')->where("mphone='{$phone}' or email='{$email}'")->find();
        if($user){
            echo api_json(null,'400','手机号或者邮箱已注册');exit();
        }
//       $phone_codes = get_code($phone);
//        if(!$phone_codes || ($phone_code != $phone_codes)){
//            echo api_json(null,'400','手机验证码不正确');exit();
//        }
        $data = [
//            'nickname'=>$name,
            'mphone'=>$phone,
            'email'=>$email,
            'password'=>password_hash($passwd, PASSWORD_DEFAULT),
            'code'=>$this->initcode()
        ];
        if($invitation_code){
            $super = M('users')->field('id,one_superId')->where('code=%d and deleted = 0 and status =0',$invitation_code)->find();
            if($super){
                $data['one_superId'] = $super['id'];
                $data['two_superId'] = $super['one_superId'];
            }
        }
        $result = M('users')->add($data);
        if(!$result){
            $code = '500';
            echo api_json(null,$code,D('Error')->getText($code));exit();
        }
        $code = '200';
        echo api_json(1,$code,D('Error')->getText($code));
    }
    /**
     * 找回密码
     */
    public function retrieve_pwd(){
        $phone = I('phone');
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