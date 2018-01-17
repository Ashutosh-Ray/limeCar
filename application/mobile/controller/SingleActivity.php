<?php
namespace app\mobile\controller;
use think\Db;
use think\Page;

class SingleActivity extends MobileBase {

	public $user_id = 0;
    public $user = array();
    public $count =0;
    public $orderCount=0;

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
        }
        // var_dump(session('user'));die();
        $nologin = array(
            'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
            'verifyHandle', 'reg', 'send_sms_reg_code', 'find_pwd', 'check_validate_code',
            'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express',
        );
        if (!$this->user_id && !in_array(ACTION_NAME, $nologin)) {
            header("location:" . U('Mobile/Index/index'));
            exit;
        }

        $this->count = M('AllActivity')->where(array('user_id'=>$this->user_id,'sactivity_id'=>1))->count();
        $map = array();
        $map['og.goods_id'] = 249;
        $map['ord.user_id'] = $this->user_id;
        $map['ord.pay_status'] = 1;
        $this->orderCount = M('Order')->alias('ord')->join('__ORDER_GOODS__ og','og.order_id=ord.order_id')->where($map)->count();
        

        
    }


	public function activity_mobile()
	{
    if ($this->count>0&&$this->orderCount<1) {
          header("location:" . U('Mobile/SingleActivity/activity_goods',array('id'=>249)));
          exit();
        }
        if ($this->orderCount>=1) {
         $this->error('您已经参加过了');
        }
		
		return $this->fetch();
	}


	public function check_mobile()
	{

		 $data = I('post.');
          // var_dump($data);die();
          //验证码
          $map['mobile'] = $data['apply_phone'];
          $map['session_id'] = session_id();
          $map['status']=1;
          $old = M('sms_log')->where($map)->order('id desc')->find();
          $time = time()-$old['add_time'];
          if ($old&&$time>3600){
            exit(json_encode(array('status'=>0,'msg'=>'验证码已超时')));
          }

          if($old['code']!=$data['code']){
            exit(json_encode(array('status'=>0,'msg'=>'验证码有误')));
          }
          $count = M('AllActivity')->where(array('mobile'=>$map['mobile'],'sactivity_id'=>1))->count();
          if ($count>0) {
          	exit(json_encode(array('status'=>0,'msg'=>'手机号已经被使用')));
          }


          $saveData = array();
          $saveData['user_id'] = $this->user_id;
          $saveData['mobile'] = $map['mobile'];
          $saveData['sactivity_id'] = 1;
          $saveData['add_time'] = time();
          $res = M('AllActivity')->add($saveData);
          if ($res) {
          	// header("location:" . U('Mobile/SingleActivity/activity_goods',array('id'=>126)));
          	exit(json_encode(array('status'=>1,'url'=>U('Mobile/SingleActivity/activity_goods',array('id'=>249)))));
          }
	}

	


	public function activity_goods()
	{
		$activity = M('AllActivity')->where(array('user_id'=>$this->user_id,'sactivity_id'=>1))->find();
		 if (is_array($activity)) {
		    if ($this->orderCount>=1) {
         $this->error('您已经参加过了');
         }
		}else{
			header("location:" . U('Mobile/SingleActivity/activity_mobile'));
			exit();
		}
		C('TOKEN_ON',true);        
        $goodsLogic = new \app\home\logic\GoodsLogic();
        $goods_id = I("get.id/d");
        $where['del_status']=0;
        $where['goods_id'] = $goods_id;
        $where['is_vip'] = 1;
        $goods = M('Goods')->where($where)->find();
        if(empty($goods)){
            $this->error('此商品不存在或者已下架');
        }
        $goods_images_list = M('GoodsImages')->where("goods_id", $goods_id)->select(); // 商品 图册
        $cat_id = $goods['cat_id'];
        $goodsAttribute =M('Attribute')->alias('a')
          ->field('a.*,av.id AS attr_val_id,av.attr_val ')
          ->join('__ATTR_VAL__ av','av.attribute_id = a.id','LEFT')
          ->where(array('a.cat_id'=>$cat_id,'del_status'=>0,'av.goods_id'=>$goods_id))
          ->select();
        $Programme = getProgramme('',$goods_id);
        $Programme['goods_id'] = $goods_id;  
        $cases = M('GoodsProgramme')->where($Programme)->select();
        $this->assign('cases',$cases);//购车方案  
        $this->assign('goodsAttribute',$goodsAttribute);//属性值
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('goods',$goods);
        $this->assign('newCates',getCatGrandson (138));// xinche
        $this->assign('usedCates',getCatGrandson (164));// xinche
        $this->assign('curingCates',getCatGrandson (165));// xinche
       
        if($goods['shop_id']==0){
             $shop['shop_name']= '三品車';
             $shop['shop_address']= '林泉街399号';
             $this->assign('shop_name','三品車');
             $this->assign('shop',$shop);
        }else{
            $shop = M('Admin')->where(array('admin_id'=>$goods['shop_id']))->find();

            $this->assign('shop_name',$shop['shop_name']);
            $this->assign('shop',$shop);
        }

        $this->assign('infos',$infos);
        return $this->fetch();

	}

}
?>