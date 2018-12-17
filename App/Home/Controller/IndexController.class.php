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
        if (!preg_match('/1[0-9]{10}/', $user) || strlen($user) != 11) {
            echo api_json(null, '400', '手机号码格式不正确');
            exit();
        }
        $is = M('googleAuth')->where('phone=' . $user)->find();
        $rs = true;
        if ($is) {
            $secret = $is['secret'];
        } else {
            $secret = $googleAuthenticator->createSecret();
            $add['phone'] = $user;
            $add['secret'] = $secret;
            $add['create_time'] = datetimenew();
            $rs = M('yt_google_auth')->add($add);
        }
        $qrCodeUrl = $googleAuthenticator->getQRCodeGoogleUrl('ETHCODE', $secret);
        if (!$rs) {
            api_json('', 500, '获取失败，请重试');
        }
        api_json($qrCodeUrl, 200, '获取成功');
        echo "Google Charts URL for the QR-Code: " . $qrCodeUrl . "</br>";
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
        echo "Google Charts URL for the QR-Code: " . $qrCodeUrl . "</br>";
        $oneCode = $this->googleAuthenticator->getCode($this->secret);
        echo "Checking Code '$oneCode' and Secret '$this->secret':</br>";
        $checkResult = $this->googleAuthenticator->verifyCode($this->secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
        if ($checkResult) {
            echo 'OK';
        } else {
            echo 'FAILED';
        }
        exit();
        $rec = $this->think_send_mail('1140977015@qq.com', 'wmt', '你是sb', '你是sb！');
        var_dump($rec);
//        $this->display('index');
    }


    public function think_send_mail($to, $name, $subject = '', $body = '', $attachment = null)
    {

        $config = C('THINK_EMAIL');
        vendor('PHPMailer.class#phpmailer');
        vendor('SMTP');
        require __DIR__ . '/../../..//vendor/PHPMailer/class.phpmailer.php';
        require __DIR__ . '/../../..//vendor/SMTP.php';
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

        $replyEmail = $config['REPLY_EMAIL'] ? $config['REPLY_EMAIL'] : $config['FROM_EMAIL'];

        $replyName = $config['REPLY_NAME'] ? $config['REPLY_NAME'] : $config['FROM_NAME'];

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

        if (is_array($attachment)) { // 添加附件

            foreach ($attachment as $file) {

                is_file($file) && $mail->AddAttachment($file);

            }

        }

        return $mail->Send() ? true : $mail->ErrorInfo;

    }


    public function invite()
    {
        $id = I('id');
        if ($id) {
            $data = M('users')->find($id);
            $this->assign('data', $data);
        }
        $this->display();
    }

    public function Login()
    {
        $user = I('user', '', 'trim');
        $passwd = I('pwd', '', 'trim');
        $phone_code = I('phone_code');
        $checkpwd = I('checkpwd');
        $name = I('name');
        $invitation_code = I('invitation_code');
        if (empty($user) || empty($passwd)) {
            $code = '300';
            echo api_json(null, 300, '参数为空');
            exit();
        }
        //过滤匹配
        if (!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/', $user)) {
            $data['user'] = $user;
            $data['eth_address'] = $user;
        } else {
            $data['email'] = $user;
        }

//        if (strlen($passwd) < 6 || strlen($passwd) > 20 || $passwd != $checkpwd) {
//            echo api_json(null,'400','密码输入有误');exit();
//        }
//        if (mb_strlen($name, 'UTF8') < 2 || mb_strlen($name, 'UTF8') > 20) {
//            echo api_json(null,'400','用户名长度不符');exit();
//        }
        $iswhere['email|mphone|eth_address'] = $user;
        $user = M('users')->where($iswhere)->find();
        if ($user) {
            echo api_json(null, '400', '该手机号已注册');
            exit();
        }
        $data['password'] = password_hash($passwd, PASSWORD_DEFAULT);
//        $data['code'] = $this->initcode();
        $data['create_time'] = datetimenew();
        if ($invitation_code) {
            $super = M('users')->field('id,one_superid,eth,node_earnings,dividend_earnings')->where('code=%d and deleted = 0 and status =0', $invitation_code)->find();
            if ($super) {
                $data['one_superid'] = $super['id'];
                $data['two_superid'] = $super['one_superid'];
//                $poration = $this->get_node_level($super['id']);
            }
        }
        $result = M('users')->add($data);
        if (!$result) {
            $code = '500';
            echo api_json(null, $code, '注册失败');
            $code = '200';
            if (isset($super) && $super) {
                $poration = $this->get_node_level($super['id']);
                if ($poration) {
                    $this->node_reward($super, $poration);
                }
            }
            echo api_json(null, 200, ' 注册成功');
        }
    }

    /**
     * 获取节点奖励的级别
     *
     */
    public function get_node_level($super_id)
    {
        $num = M('users')->where('one_superid=' . $super_id)->count();
        $node = M('node_pool_dispose')->where("status = 1 and ((type = 0 and num = {$num}) or (type = 1 and num < {$num}))")->find();
        if ($node && $node['proportion']) {
            $poration = $node['proportion'];
        } else {
            $poration = 0;
        }

        return $poration;
    }


    /**
     * 节点奖励分红
     * author:wmt
     * date:2018-10-19
     */
    public function node_reward($user, $poration)
    {
        $amount = M('bonus_pool')->where('type = 1')->getField('eth');
        $trade['user_id'] = $user['id'];
        $trade['related_id'] = 0;
        //                $trade['trade_ids'] = '{' . $order['trade_id'] . '}';
        $trade['mode'] = 'income_node_reward';
        $trade['message'] = '邀请节点奖励收入';
        $trade['eth'] = $amount * 0.9 * $poration / 100;
        $trade['status'] = 1;
        $trade_ids = M('trades')->add($trade);
        if ($trade_ids) {
            $payment['trade_id'] = $trade_ids;
            $payment['mode'] = 'eth';
            $payment['beamount'] = $user['eth'];
            $payment['afamount'] = ($user['eth']) + $trade['eth'];
            $payment['eth'] = $trade['eth'];
            $payment['status'] = 1;
            M('payments')->add($payment);
            M('users')->where("id =" . $user['id'])->save(['eth' => ($user['eth']) + $trade['eth'], 'dividend_earnings' => ($user['dividend_earnings'] + $trade['eth']), 'node_earnings' => ($user['node_earnings'] + $trade['eth']), 'update_time' => date('Y-m-d H:i:s', time())]);
            M('bonus_pool')->where('type = 1')->save(['eth' => ($amount - $trade['eth']), 'update_time' => date('Y-m-d H:i:s', time())]);
        }

    }

    public function Load(){
        $this->display('Loadhys');
    }
}