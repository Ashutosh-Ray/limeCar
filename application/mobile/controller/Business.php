<?php
namespace app\mobile\controller;
use think\Db;
class Business extends MobileBase{
	public function businessInfo()
	{
		$id = I('id');
		$info = M('Admin')->where(array('admin_id'=>$id,'status'=>1,'check_status'=>1,'del_status'=>0))->field('shop_name,shop_address,shop_lon,shop_lat,shop_logo,check_num')->find();
		if (!is_array($info)) {
			$this->error('该商家已不存在');
		}
		$hotgoods = M('goods')->alias('g')->join('__GUSEB__ b','g.goods_id =b.goods_id')->where(array('g.is_on_sale'=>1,'b.shop_id'=>$id))->select();
		$this->assign('info',$info);
		$this->assign('hotgoods',$hotgoods);
		return $this->fetch();
	}
}