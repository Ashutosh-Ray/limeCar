<?php
/**
* 代买車
*/
namespace app\admin\controller;
use app\admin\logic\OrderLogic;
use think\AjaxPage;
use think\Page;
use think\Db;
class Agency extends Base
{
	//意向列表
	public function index()
	{
		$keywords = I('get.keywords');
		if (!empty($keywords)) {
			$where['name'] = array('like',"%$keywords%");
			$where2['phone'] =array('like',"%$keywords%");
			$where3['content'] =array('like',"%$keywords%");
			// $where ="'name|phone|content','like','%$keywords%'";
		}		
		
		$count = Db::table('tb_agency')->where($where)->whereOr($where2)->whereOr($where3)->count();
		
		$page = new Page($count,10);
		$list = Db::table('tb_agency')->where($where)->whereOr($where2)->whereOr($where3)->limit($page->firstRow.','.$page->listRows)->order('add_time desc')->select();
		$this->assign('list',$list);
		$this->assign('page',$page->show());
		return $this->fetch();
	}



	
}

?>