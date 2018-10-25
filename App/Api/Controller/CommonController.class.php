<?php
namespace Api\Controller;
use Think\Controller;



/**
 * 公共部分
 */
class CommonController extends Controller
{
    protected $userId   = 0;
    protected $systemId   = 0;
    protected $nickname = '';


    public function __construct()
    {
        parent::__construct();
        $this->systemId = 0;
    }

//	public function _initialize() {
//
//		$token = I('token');
//		if(empty($token)){
//			echo api_json(null,'300','token不可为空');exit();
//		}
//		//测试数据
////		session("user_id", 1);
//	}

    public function _empty()
    {
        echo api_json(null,404,'未知的请求');exit();
    }

    /**
     * 检查登陆状态
     */
    protected function checkLogin()
    {
        $token = I('token');
        if(!$token){
            api_json(null, 108, '缺少 token');
        }
        $where['token'] = $token;
        $field = 'id, nickname, code, mphone, email, is_js, is_freeze,  token, super_token, eth, eth_address, all_earnings,all_token, all_eth, dynamic_earnings, dividend_earnings, node_earnings, paradrop_earnings, invite_earnings, govern_earnings, frozen_earnings';
        $data = M('users')->where($where)->field($field)->find();
        if (!$data) {
            api_json(null, 109, 'token错误');
        } else {
            if($data['status'] == -1){
                api_json(null, 110, '用户已被禁用');
            }
        }
        //查询分红奖金池
        $bonusWhere['type'] = 1;
        $bonusWhere['status'] = 0;
        $fhbonus = M('bonus_pool')->where($bonusWhere)->find();
        $data['fhbonus'] = $fhbonus['eth'];
        //查询下一个节点人数
        list($data['newnum'], $data['lastnum']) = self::get_node_level($data['id']);
        //查询是否在游戏
        $gameWhere['uid'] = $data['id'];
        $gameWhere['type'] = 0;
        $gameid = M('game')->where($gameWhere)->find();
        if(!$gameid['id']){
            $gameid['id'] = 0;
        }
        $data['gameid'] = $gameid['id'];
        $address = M('wallet')->find(1);
        $data['official_eth'] = $address['address'];
        return $data;
    }

    /**
     * 获取节点人数
     *
     */
    public function get_node_level($super_id){
        $num = M('users')->where('one_superId='.$super_id)->count();
        $node = M('node_pool_dispose')->where("status = 1 and type = 0  and num > {$num}")->find();
        if($node && $node['num']){
            $poration = $node['num'];
        }else{
            $poration = 0;
        }

        return array($num, $poration-$num );
    }

    /**
     * 获取邀请码
     */
    private function getcode() {
        $code = $this->initcode();
        if ($this->recode($code)) {
            return $code;
        } else {
            $this->getcode();
        }
    }


    /**
     * 获取 token
     */
    private function gettoken() {
        $token = $this->inittoken();
        if ($this->retoken($token)) {
            return $token;
        } else {
            $this->gettoken();
        }
    }


    /**
     * 生成邀请码
     */
    public function initcode() {
        $code = substr(base_convert(md5(uniqid(md5(microtime(true)),true)), 16, 10), 0, 6);
        return $code;
    }

    /**
     * 邀请码是否重复
     */
    private function recode($code) {
        $is = M('users')->where(array('code'=>$code))->find();
        if ($is) {
            return false;
        }
        return true;
    }

    /*
     * 生成token
     */
    function inittoken()
    {
        $str = md5(uniqid(md5(microtime(true)),true));
        $str = sha1($str);  //加密
        return $str;
    }

    /**
     * token是否重复
     */
    private function retoken($token) {
        $is = M('users')->where(array('token'=>$token))->find();
        if ($is) {
            return false;
        }
        return true;
    }
    public function upload($root_directory,$subdirectory){
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg','pdf');// 设置附件上传类型
        $upload->rootPath  =     $root_directory; // 设置附件上传根目录
        $upload->savePath  =     $subdirectory; // 设置附件上传（子）目录
        if(!file_exists($root_directory.$subdirectory)){
            mkdir($root_directory.$subdirectory,493,true);
        }
        // 上传文件
        $info   =   $upload->upload();
        //print_r($info);
        if(!$info) {// 上传错误提示错误信息
            return ['status'=>'no','data'=>$upload->getError()];
        }else{// 上传成功
            foreach($info as $file){
                $bigimg = $file['savepath'].$file['savename'];
            }
            return ['status'=>'ok','data'=>$bigimg];
        }
    }

}