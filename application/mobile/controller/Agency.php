<?php
/**
* 代买車
*/
namespace app\mobile\controller;
use think\AjaxPage;
use think\Page;
use think\Db;
class Agency extends MobileBase
{
	public $user;
	public $partner;
	public function _initialize()
	{
		parent::_initialize();
		if(session('?user')){
			$user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            $this->user = $user;
            $this->partner = M('Partner')->where(array('user_id'=>$this->user['user_id'],'partner_status'=>1))->find();
            $user['partner'] = $this->partner;
            session('user',$user);  //覆盖session 中的 
		}

	}
	
	public function index()
	{
		return $this->fetch();
	}


	public function sub_agency()
	{
		if (IS_AJAX) {
				$data = $_POST;
				if (empty($data['code'])) exit(json_encode(array('status'=>0,'msg'=>'验证码不能为空')));
				if (empty($data['name'])) exit(json_encode(array('status'=>0,'msg'=>'姓名不能为空')));
				if (empty($data['phone'])) exit(json_encode(array('status'=>0,'msg'=>'手机号不能为空')));
				if (empty($data['content'])) exit(json_encode(array('status'=>0,'msg'=>'意向车型不能为空')));
						$map = array();
				  		$map['mobile'] = $data['phone'];
				  		$map['session_id'] = session_id();
				  		$map['status']=1;
        		  		$old = M('sms_log')->where($map)->order('id desc')->find();
        		  		$time = time()-$old['add_time'];
        		if ($old&&$time>3600)  exit(json_encode(array('status'=>0,'msg'=>'验证码已超时')));
        		if ($old['code']!=$data['code']) exit(json_encode(array('status'=>0,'msg'=>'验证码有误')));
        		if (strlen($data['content'])>500) exit(json_encode(array('status'=>0,'msg'=>'意向车型描述过长')));
        			$start = strtotime(date("Y-m-d"),time());
            		$end = $start+60*60*24; 
            		$where['phone'] = $data['phone'];
            		$where['add_time'] = array(array('gt',$start),array('lt',$end));
            		$count=M('Agency')->where($where)->count();
            		if ($count>=10) exit(json_encode(array('status'=>0,'msg'=>'每天只有10次')));
					unset($data['code']);
					$data['add_time'] = time();
					$data['user_id'] = $this->user['user_id'];
					$res = M('Agency')->add($data);
					if ($res) {
						exit(json_encode(array('status'=>1,'msg'=>'提交成功')));
					}else{
						exit(json_encode(array('status'=>0,'msg'=>'提交失败')));
					}
        }
	}

	
	
}

?>