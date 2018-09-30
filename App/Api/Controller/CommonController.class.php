<?php
namespace Api\Controller;
use Think\Controller;



/**
 * 公共部分
 */
class CommonController extends Controller
{
	public function _initialize() {

		$token = I('token');
		if(empty($token)){
			echo api_json(null,'300','token不可为空');exit();
		}
		//测试数据
//		session("user_id", 1);
	}


}