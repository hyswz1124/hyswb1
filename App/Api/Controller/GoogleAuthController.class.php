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
        $user = I('phone');
        if(!preg_match('/1[0-9]{10}/', $user) || strlen($user) != 11) {
            echo api_json(null,'400','手机号码格式不正确');exit();
        }
        $is = M('googleAuth')->where('phone='.$user)->find();
        $rs = true;
        if($is){
            $secret = $is['secret'];
        }else{
            $secret = $googleAuthenticator->createSecret();
            $add['phone'] = $user;
            $add['secret'] = $secret;
            $add['create_time'] = datetimenew();
            $rs = M('yt_google_auth')->add($add);
        }
        $qrCodeUrl = $googleAuthenticator->getQRCodeGoogleUrl('ETHCODE', $secret);
        if(!$rs){
            api_json('', 500, '获取失败，请重试');
        }
        api_json($qrCodeUrl, 200, '获取成功');
    }
}