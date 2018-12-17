<?php
namespace Home\Controller;

use Common\Model\GoogleAuthenticatorModel;
use Think\Controller;
use Think\View;

class IndexController extends Controller
{
    protected $googleAuthenticator;
    protected $secret = '2SNCHX2PSENUKQSN';

    public function __construct()
    {
        parent::__construct();
//        dd(decode('MhTyQs0MDAO0O0O'));
        $this->googleAuthenticator = new GoogleAuthenticatorModel();
//        $this->secret = $this->googleAuthenticator->createSecret();
    }

    public function index()
    {
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
        echo "Google Charts URL for the QR-Code: ".$qrCodeUrl."</br>";
        $oneCode = $googleAuthenticator->getCode($secret);
        echo "Checking Code '$oneCode' and Secret '$secret':</br>";
        $checkResult = $googleAuthenticator->verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
        if ($checkResult) {
            echo 'OK';
        } else {
            echo 'FAILED';
        }
        exit;
//        $secret = $google->createSecret();
//        echo "Secret is: ".$secret."</br>";
        $qrCodeUrl = $this->googleAuthenticator->getQRCodeGoogleUrl('Blog', $this->secret);
        echo "Google Charts URL for the QR-Code: ".$qrCodeUrl."</br>";
        $oneCode = $this->googleAuthenticator->getCode($this->secret);
        echo "Checking Code '$oneCode' and Secret '$this->secret':</br>";
        $checkResult = $this->googleAuthenticator->verifyCode($this->secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
        if ($checkResult) {
            echo 'OK';
        } else {
            echo 'FAILED';
        }
        exit();
        $rec = $this->think_send_mail('1140977015@qq.com','wmt','你是sb','你是sb！');
        var_dump($rec);
//        $this->display('index');
    }


    public function think_send_mail($to, $name, $subject = '', $body = '', $attachment = null){

        $config = C('THINK_EMAIL');
        vendor('PHPMailer.class#phpmailer');
        vendor('SMTP');
        require __DIR__.'/../../..//vendor/PHPMailer/class.phpmailer.php';
        require __DIR__.'/../../..//vendor/SMTP.php';
        $mail = new \PHPMailer(); //PHPMailer对象
//        $mail->SMTPOptions = array(
//            'ssl' => array(
//                'verify_peer' => false,
//                'verify_peer_name' => false,
//                'allow_self_signed' => true
//            )
//        );
        $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
        $mail->IsSMTP(); // 设定使用SMTP服务

//        $mail->SMTPDebug = true; // 关闭SMTP调试功能
        $mail->SMTPAuth = true; // 启用 SMTP 验证功能
//        $mail->SMTPSecure = 'ssl'; // 使用安全协议
        $mail->Host = $config['SMTP_HOST']; // SMTP 服务器
//        var_dump($mail->Host);

        $mail->Port = $config['SMTP_PORT']; // SMTP服务器的端口号

        $mail->Username = $config['SMTP_USER']; // SMTP服务器用户名

        $mail->Password = $config['SMTP_PASS']; // SMTP服务器密码

        $mail->SetFrom($config['FROM_EMAIL'], $config['FROM_NAME']);

        $replyEmail = $config['REPLY_EMAIL']?$config['REPLY_EMAIL']:$config['FROM_EMAIL'];

        $replyName = $config['REPLY_NAME']?$config['REPLY_NAME']:$config['FROM_NAME'];

        $mail->AddReplyTo($replyEmail, $replyName);
//        $mail -> IsHTML(true);            //发送的内容使用html编写
        $mail->Subject = $subject;

        $mail->AltBody = "为了查看该邮件，请切换到支持 HTML 的邮件客户端";

        $mail->MsgHTML($body);

        $mail->AddAddress($to, $name);

//        $mail -> IsSMTP();            //告诉服务器使用smtp协议发送
//        $mail -> SMTPAuth = true;        //开启SMTP授权
//        $mail -> Host = $config['SMTP_HOST'];    //告诉我们的服务器使用163的smtp服务器发送
//        $mail -> From = $config['FROM_EMAIL'];    //发送者的邮件地址
//        $mail -> FromName = $config['FROM_NAME'];        //发送邮件的用户昵称
//        $mail -> Username = $config['SMTP_USER'];    //登录到邮箱的用户名
//        $mail -> Password = $config['SMTP_PASS'];        //第三方登录的授权码，在邮箱里面设置
//        //编辑发送的邮件内容
//        $mail -> IsHTML(true);            //发送的内容使用html编写
//        $mail -> CharSet = 'utf-8';        //设置发送内容的编码
//        $mail -> Subject = $subject;//设置邮件的标题
//        $mail -> MsgHTML($body);    //发送的邮件内容主体
//        $mail -> AddAddress($to,$name);    //收人的邮件地址

        if(is_array($attachment)){ // 添加附件

            foreach ($attachment as $file){

                is_file($file) && $mail->AddAttachment($file);

            }

        }

        return $mail->Send() ? true : $mail->ErrorInfo;

    }


    public function invite(){
        $id = I('id');
        if($id){
            $data = M('users')->find($id);
            $this->assign('data',$data);
        }
        $this->display();
    }
}