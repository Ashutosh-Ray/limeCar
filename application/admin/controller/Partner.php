<?php
/**
* 
*/
namespace app\admin\controller;
use think\AjaxPage;
class Partner extends Base
{
	public function partner_list()
	{
		$keywords = I('keywords');
		$where = array();
		$where['p.del_status']=0;
		$where['p.partner_status']=1;

		if(!empty($keywords)) $where['p.aplly_name|p.apply_phone'] = array('like',"%$keywords%");
			
         	$count =M('Partner')->alias('p')
          	->field('p.*,c.initial_fee')
          	->join('__PARTNER_CONFIG__ c','c.id = p.case_id','LEFT')
          	->where($where)
         	 ->count();
			$page  = new AjaxPage($count,10);
			$list =M('Partner')->alias('p')
          	->field('p.*,c.initial_fee')
          	->join('__PARTNER_CONFIG__ c','c.id = p.case_id','LEFT')
          	->where($where)
         	->order('p.partner_id desc')
         	->limit($page->firstRow.','.$page->listRows)
         	->select();
		$show = $page->show();
		$this->assign('list',$list);
		$this->assign('show',$show);
		
		if (IS_AJAX) {
			return $this->fetch('ajax_partner_list');
		}else{
			return $this->fetch();
		}
	}

	public function del_partner()
	{
		$partner_id = I('get.partner_id');
		$res = M('Partner')->where(array('partner_id'=>$partner_id))->save(array('del_status'=>1));
		if ($res) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	
	//返佣审核
	public function commision_check_list()
	{
		$keywords = I('keywords');
		$where = array();
		$where['del_status']=0;
		if(!empty($keywords)) $where['buy_name|buy_phone|apply_phone'] = array('like',"%$keywords%");
		$applyC= M('ApplyCommision');
		$count = $applyC->where($where)->count();
		$page  = new AjaxPage($count,10);
		$list  = $applyC->where($where)->order('id desc')->limit($page->firstRow.','.$page->listRows)->select();
		$show = $page->show();
		$this->assign('list',$list);
		$this->assign('show',$show);
		if (IS_AJAX) {
			return $this->fetch('ajax_comisiom_list');
		}else{
			return $this->fetch();
		}
		
	}

	//删除返佣审核
	public function del_check()
	{
		$id = I('get.id');
		$res = M('ApplyCommision')->where(array('id'=>$id))->save(array('del_status'=>1));
		if ($res) {
			exit(json_encode(array('status'=>1,'msg'=>'删除成功')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'删除失败')));
		}
	}

	//审核返佣申请
	public function check_apply()
	{
		$data = I('post.');
		$data['check_status']=1;
		$data['op_id'] = $_SESSION['admin_id'];
		$res = M('ApplyCommision')->where(array('id'=>$data['id']))->save($data);

		if ($res) {
			share_money($data['user_id'],$data['all_profit'],$data['id'],2,"企业分润",$new_id=0);
			exit(json_encode(array('status'=>1,'msg'=>'审核成功')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'审核失败')));
		}
	}
    
    //返佣方案列表
	public function commision_case_list()
	{
		$keywords = I('keywords');
		$where = array();
		$where['del_status']=0;
		$where['user_id']=0;
		if(!empty($keywords)) $where['case_name'] = array('like',"%$keywords%");
		$config= M('PartnerConfig');
		$count = $config->where($where)->count();
		$page  = new AjaxPage($count,10);
		$list  = $config->where($where)->order('id desc')->limit($page->firstRow.','.$page->listRows)->select();
		$show = $page->show();

		$this->assign('list',$list);
		$this->assign('show',$show);

		if (IS_AJAX) {
			return $this->fetch('ajax_case_list');
		}else{
			return $this->fetch();
		}
		
	}



	//返佣方案设置
	public function addEditor_commision_set()
	{
		$id = I('get.id/d');
		$user_id = I('get.user_id/d',0);
		$act = '';
		$info = array();
		if ($id) {
			if (is_numeric($user_id)&&!empty($user_id)) {
				$info = getPartnerConfig($user_id,$id);

			}else{
				$info = M('PartnerConfig')->where(array('id'=>$id))->find();
			}
			
			$act = 'edit';
		}else{
			$act = 'add';
		}

		$this->assign('act',$act);
		$this->assign('user_id',$user_id);
		$this->assign('info',$info);

		return $this->fetch();
	}

	//操作方案
	public function do_set()
	{
		$back = array();
		$data = I('post.');
		$data['op_id']=$_SESSION['admin_id'];
		if ($data['act'] == 'add') {
			$data['add_time'] = time();
			$data['use_status'] = 1;
			$map['del_status']=0;
			$map['partner_status']=array('in',array('1','-2'));
			if (!empty($data['user_id'])&&is_numeric($data['user_id'])) {
				$res = M('PartnerConfig')->add($data);
				$map['user_id']=$data['user_id'];
			}else{
				M('PartnerConfig')->where(array('user_id'=>0,'is_seed'=>$data['is_seed']))->save(array('use_status'=>0));
				$res = M('PartnerConfig')->add($data);
				if ($data['is_seed']!=1) {
					$map['case_id']=0;
					M('Partner')->where($map)->save(array('case_id'=>$res));
				}
				
			}
			
		}

		if ($data['act'] == 'edit') {
			$data['edit_time'] = time();
			if (!empty($data['user_id'])&&is_numeric($data['user_id'])) {
				$count = M('PartnerConfig')->where(array('id'=>$data['id'],'user_id'=>$data['user_id']))->count();
				
				if ($count==0) {
						$data['add_time'] = time();
						$data['use_status'] = 1;
						unset($data['id']);
						$res = M('PartnerConfig')->add($data);
						$map['del_status']=0;
						$map['user_id']=$data['user_id'];
						$map['partner_status']=array('in',array('1','-2'));
						M('Partner')->where($map)->save(array('case_id'=>$res));
					}else{
						$res = M('PartnerConfig')->where(array('id'=>$data['id']))->save($data);	
					}	
				
			}else{
				$back['status']=0;
				$back['msg']   ='该方案不能编辑';
				exit(json_encode($back));
			}
			
		}

		if ($data['act'] == 'del') {
			$count = M('Partner')->where(array('case_id'=>$data['id']))->count();
			$old = M('PartnerConfig')->where(array('id'=>$data['id']))->field('user_id,is_seed')->find();
			$old['use_status']=1;
			$old['id'] = array('neq',$data['id']);
			$configC = M('PartnerConfig')->where($old)->count();
			if ($configC==0) {
				exit(json_encode(array('status'=>0,'msg'=>'必须有一个可用')));
			}
			if ($count>0) {
				$back['status']=0;
				$back['msg']   ='该方案已经使用不能删除';
				exit(json_encode($back));
			}
			$res = M('PartnerConfig')->where(array('id'=>$data['id']))->save(array('del_status'=>1));
			
			
		}


		
		if ($res!==false) {
			$back['status']=1;
			$back['msg']   ='操作成功';
			
		}else{
			$back['status']=0;
			$back['msg']   ='操作失败';
		}

		exit(json_encode($back));
	}

	public function change_case_use()
	{
		$use_status = I('use_status/d',0);
		$is_seed    = I('is_seed/d',0);
		$case_id    = I('case_id');

		if (empty($case_id)||!is_numeric($case_id)) {
			exit(json_encode(array('status'=>0,'msg'=>'id有误')));
		}
		$map = array('del_status'=>0,'use_status'=>$use_status,'is_seed'=>$is_seed);
		$map['user_id']=0;
		if ($use_status==1) {
			M('PartnerConfig')->where($map)->save(array('use_status'=>0));
			$res =M('PartnerConfig')->where(array('del_status'=>0,'id'=>$case_id))->save(array('use_status'=>1));
		}else{
			$map['id'] = array('neq',$case_id);
			$map['use_status']=1;
			$count = M('PartnerConfig')->where($map)->count();
			if ($count==0) {
				exit(json_encode(array('status'=>0,'msg'=>'必须有一个可用')));
			}
			$res =M('PartnerConfig')->where(array('id'=>$case_id))->save(array('use_status'=>0));
		}


		if ($res) {
			exit(json_encode(array('status'=>1,'msg'=>'操作成功')));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'操作失败')));
		}
	}


	public function see_qr_code()
	{
		$id = I('id/d',0);
		$old = M('PartnerConfig')->where(array('id'=>$id))->find();
		if (!empty($old['qr_code'])&&file_exists($old['qr_code'])) {
			header("location:".$old['qr_code']);exit();
		}
		$url= '/Mobile/Partner/partner_reg/parent_id/0/case_id/'.$id;
		$url = "http://".$_SERVER['HTTP_HOST'].$url;
		$qr_code = make_qr_code($url,'seed-code/'.date('Y-m-d'),$id.'.jpg');
		$res = M('PartnerConfig')->where(array('id'=>$id))->save(array('qr_code'=>$qr_code));
		
		header("location:$qr_code");exit();
	}

}