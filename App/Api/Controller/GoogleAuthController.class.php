<?php
namespace  Api\Controller;

use Common\Model\GoogleAuthenticatorModel;

class GoogleAuthController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        $googleAuthenticator = new GoogleAuthenticatorModel();
        $user = $this->checkLogin();
        $secret = $googleAuthenticator->createSecret();
        $add['uid'] = $user['id'];
        $add['secret'] = $secret;
        $add['create_time'] = datetimenew();
        $rs = M('googleAuth')->add($add);
        $qrCodeUrl = $googleAuthenticator->getQRCodeGoogleUrl('ETHCODE', $secret);
        $data['secret'] = $secret;
        $data['url'] = $qrCodeUrl;
        if(!$rs){
            api_json('', 500, '获取失败，请重试');
        }
        api_json($data, 200, '获取成功');
    }

    public function setAuth(){
        $user = $this->checkLogin();
        $code = I('code', '', 'trim');
        $secret = I('secret', '', 'trim');
        if(!$code or !$secret){
            api_json('', 400, '参数为空');
        }
        $googleAuthenticator = new GoogleAuthenticatorModel();
        $checkResult = $googleAuthenticator->verifyCode($secret, $code, 2);    // 2 = 2*30sec clock tolerance
        if (!$checkResult) {
            api_json('', 400, '验证码跟秘钥不匹配');
        }
        $up['secret'] = $secret;
        $rs = M('users')->where('id='.$user['id'])->save($up);
        if($rs === false){
            api_json('', 500, '绑定失败，请重试');
        }
        api_json('', 200, '成功');
    }
}