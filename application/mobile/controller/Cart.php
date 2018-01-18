<?php
/**
 * ThinkPHP [ WE CAN DO IT JUST THINK ]
 +----------------------------------------------------------------------
 * * Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
 * 
 +----------------------------------------------------------------------
 * 
 * 
 +----------------------------------------------------------------------
 * $Author: Alince 2015-08-10 $
 */ 
namespace app\mobile\controller;
use think\Db;
class Cart extends MobileBase {
    
    public $cartLogic; // 购物车逻辑操作类    
    public $user_id = 0;
    public $user = array();        
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();                
        $this->cartLogic = new \app\home\logic\CartLogic();
        if(session('?user'))
        {
            $user = session('user');
                $user = M('users')->where("user_id", $user['user_id'])->find();
                session('user',$user);  //覆盖session 中的 user                                         
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user',$user); //存储用户信息
                // 给用户计算会员价 登录前后不一样
                if($user){
                    $user[discount] = (empty($user[discount])) ? 1 : $user[discount];
                    DB::execute("update `__PREFIX__cart` set member_goods_price = goods_price * {$user[discount]} where (user_id ={$user[user_id]} or session_id = '{$this->session_id}') and prom_type = 0");
                }                 
         }            
    }
    
    public function cart(){
        //获取热卖商品
        $hot_goods = M('Goods')->where('is_hot=1 and is_on_sale=1')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();
        $this->assign('hot_goods',$hot_goods);
        return $this->fetch('cart');
    }
    /**
     * 将商品加入购物车
     */
    // function addCart()
    // {
    //     $goods_id = I("goods_id/d"); // 商品id
    //     $goods_num = I("goods_num/d");// 商品数量
    //     $goods_spec = I("goods_spec"); // 商品规格                
    //     $goods_spec = json_decode($goods_spec,true); //app 端 json 形式传输过来
    //     $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
    //     $user_id = I("user_id/d",0); // 用户id        
    //     $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$unique_id,$user_id); // 将商品加入购物车
    //     exit(json_encode($result)); 
    // }
    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {
        $goods_id = I("goods_id/d"); // 商品id
        $goods_num = I("goods_num/d");// 商品数量
        $goods_spec = I("goods_spec/a",array()); // 商品规格
         $sql =  'select a.*,b.* from tb_order as a, tb_order_goods as b where a.order_id=b.order_id AND user_id='.$this->user_id.' AND goods_id='.$goods_id;
                 $orders = Db::query($sql); 
                 $flash = M('FlashSale')->where(array('goods_id'=>$goods_id))->field('buy_limit')->find();
                 $count = count($orders);
                 if ($count>=$flash['buy_limit']&& $flash) {
                        $result['status'] = -1;
                        $result['msg'] = '该商品限购'.$flash['buy_limit'].'件您已经买过'.$count.'件';
                                 }  
                   if ($result) {
                                   exit(json_encode($result)); 
                                 }                                
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$this->user_id); // 将商品加入购物车
        exit(json_encode($result));
    }

    /*
     * 请求获取购物车列表
     */
    public function cartList()
    {
        $cart_form_data = input('cart_form_data'); // goods_num 购物车商品数量
        $cart_form_data = json_decode($cart_form_data,true); //app 端 json 形式传输过来

        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $user_id = I("user_id/d"); // 用户id
        $where['session_id'] = $unique_id; // 默认按照 $unique_id 查询
        if($user_id){
            $where['user_id'] = $user_id;
        }
        $cartList = M('Cart')->where($where)->getField("id,goods_num,selected");

        if($cart_form_data)
        {
            // 修改购物车数量 和勾选状态
            foreach($cart_form_data as $key => $val)
            {
                $data['goods_num'] = $val['goodsNum'];
                $data['selected'] = $val['selected'];
                $cartID = $val['cartID'];
                if(($cartList[$cartID]['goods_num'] != $data['goods_num']) || ($cartList[$cartID]['selected'] != $data['selected']))
                    M('Cart')->where("id", $cartID)->save($data);
            }
            //$this->assign('select_all', $_POST['select_all']); // 全选框
        }

        $result = $this->cartLogic->cartList($this->user, $unique_id,0);
        exit(json_encode($result));
    }

    /**
     * 购物车第二步确定页面
     */
    public function cart2()
    {
        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
        $address_id = I('address_id/d');
        if($address_id)
            $address = M('user_address')->where("address_id", $address_id)->find();
        else
            $address = M('user_address')->where(['user_id'=>$this->user_id,'is_default'=>1])->find();
        
        if(empty($address)){
            header("Location: ".U('Mobile/User/add_address',array('source'=>'cart2')));
        }else{
            $this->assign('address',$address);
        }

        if($this->cartLogic->cart_count($this->user_id,1) == 0 )
            $this->error ('你的购物车没有选中商品','Cart/cart');

        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1); // 获取购物车商品
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->cache(true,TPSHOP_CACHE_TIME)->select();// 物流公司

        // 找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的
        $sql = "select c1.name,c1.money,c1.condition, c2.* from __PREFIX__coupon as c1 inner join __PREFIX__coupon_list as c2  on c2.cid = c1.id and c1.type in(0,1,2,3) and order_id = 0  where c2.uid = {$this->user_id} and ".time()." < c1.use_end_time and c1.condition <= {$result['total_price']['total_fee']}";
        $couponList = DB::query($sql);
        if(I('cid/d') != ''){
            $cid = I('cid/d');
            $checkconpon = M('coupon')->field('id,name,money')->where("id = $cid")->find();    //要使用的优惠券
            $checkconpon['lid'] = I('lid/d');
        }
        $this->assign('couponList', $couponList); // 优惠券列表
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('cartList', $result['cartList']); // 购物车的商品
        $this->assign('total_price', $result['total_price']); // 总计
        $this->assign('checkconpon', $checkconpon); // 使用的优惠券
        //判断购物车中是否有养护产品
    
         $xuni = 0;
        foreach ($result['cartList'] as $key => $value) {
             if ($value['selected']==1) {
                $cid = M('Goods')->where(array('goods_id'=>$value['goods_id']))->find();
            if ($this->xuni($cid['cat_id'])) {
              $xuni = 1;
            }
          }
        }
        $this->assign('xuni',$xuni);
        return $this->fetch();
    }

    //判断是否是虚拟产品
    public function xuni($cid)
    {
        // $cid=$goods['cat_id'];
        $cat = M('GoodsCategory')->where(array('id'=>$cid))->find();

        $array = array();
        $array[] = $cid;
        while ( $cat['parent_id'] != 0) {
            $array[] = $cat['parent_id'];
            $cat = M('GoodsCategory')->where(array('id'=>$cat['parent_id']))->find();
        }
         if (in_array('165',$array)) {
            return true;
        }else{
            return false; 
        }
        
    }
    

    /**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3(){

        if($this->user_id == 0)
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        
        $address_id = I("address_id/d"); //  收货地址id
        $shipping_code =  I("shipping_code"); //  物流编号        
        $invoice_title = I('invoice_title'); // 发票
        $couponTypeSelect =  I("couponTypeSelect"); //  优惠券类型  1 下拉框选择优惠券 2 输入框输入优惠券代码
        $coupon_id =  I("coupon_id/d"); //  优惠券id
        $couponCode =  I("couponCode"); //  优惠券代码
        $pay_points =  I("pay_points/d",0); //  使用积分
        $user_money =  I("user_money/f",0); //  使用余额
        $user_note = trim(I('user_note'));   //买家留言
        $user_money = $user_money ? $user_money : 0;

        if($this->cartLogic->cart_count($this->user_id,1) == 0 ) exit(json_encode(array('status'=>-2,'msg'=>'你的购物车没有选中商品','result'=>null))); // 返回结果状态
        if(!$address_id) exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
        if(!$shipping_code) exit(json_encode(array('status'=>-4,'msg'=>'请选择物流信息','result'=>null))); // 返回结果状态
        
        $address = M('UserAddress')->where("address_id", $address_id)->find();
        $order_goods = M('cart')->where(['user_id'=>$this->user_id,'selected'=>1])->select();
        $result = calculate_price($this->user_id,$order_goods,$shipping_code,0,$address[province],$address[city],$address[district],$pay_points,$user_money,$coupon_id,$couponCode);
                
        if($result['status'] < 0)   
            exit(json_encode($result));         
    // 订单满额优惠活动                     
        $order_prom = get_order_promotion($result['result']['order_amount']);
        $result['result']['order_amount'] = $order_prom['order_amount'] ;
        $result['result']['order_prom_id'] = $order_prom['order_prom_id'] ;
        $result['result']['order_prom_amount'] = $order_prom['order_prom_amount'] ;
            
        $car_price = array(
            'postFee'      => $result['result']['shipping_price'], // 物流费
            'couponFee'    => $result['result']['coupon_price'], // 优惠券            
            'balance'      => $result['result']['user_money'], // 使用用户余额
            'pointsFee'    => $result['result']['integral_money'], // 积分支付
            'payables'     => $result['result']['order_amount'], // 应付金额
            'goodsFee'     => $result['result']['goods_price'],// 商品价格
            'order_prom_id' => $result['result']['order_prom_id'], // 订单优惠活动id
            'order_prom_amount' => $result['result']['order_prom_amount'], // 订单优惠活动优惠了多少钱            
        );
       
        // 提交订单        
        if($_REQUEST['act'] == 'submit_order')
        {  
            if(empty($coupon_id) && !empty($couponCode)){
                $coupon_id = M('CouponList')->where("code", $couponCode)->getField('id');
            }
            $result = $this->cartLogic->addOrder($this->user_id,$address_id,$shipping_code,$invoice_title,$coupon_id,$car_price,$user_note); // 添加订单
            exit(json_encode($result));
        }
            $return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$car_price); // 返回结果状态
            exit(json_encode($return_arr));
    } 


      /**
     *  将商品加入购物车并标记为已经选中
     */
   private function AddCart()
    {
        
        
        $goods_id = I("goods_id/d"); // 商品id
        $goods_num = I("goods_num/d");// 商品数量
        $goods_spec = I("goods_spec/a",array()); // 商品规格                                   
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$this->user_id); // 将商品加入购物车
        //将本次购物车里所有商品取出   
         $lists = M('Cart')->where(array('user_id'=>$this->user_id,'session_id'=>$this->session_id,'selected'=>1))->select();
         $allMoney = new_calculate_price($this->user_id,$lists);
        $this->assign('lists',$lists);
        $this->assign('allmoney',$allMoney['result']);      
    }

     public function submit_order()
    {
      if($this->user_id == 0)
             exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null)));
         $result = $this->cartLogic->addOrder($this->user_id,$this->session_id); // 添加订单
         exit(json_encode($result));

         

    }


    public function order()
    {
        if($this->user_id == 0)
             $this->error('请登录');
          $this->addCart();
          $count_coupon = M("CouponList")->where(array('user_del'=>0,'uid'=>$this->user_id,'order_id'=>0))->count();
          $this->assign('count_coupon',$count_coupon);
          return $this->fetch();
    }


    public function canUseCoupon()
    {
      if($this->user_id == 0)
             $this->error('请登录');
      $coupons = M("CouponList")->where(array('user_del'=>0,'uid'=>$this->user_id,'order_id'=>0))->select();
     foreach ($coupons as $key => $coupon) {
        $coupon['can_use']=1;
        if ($coupon['use_end_time']<time()){
          $coupon['can_use']=0;
          $coupon['no_use'] = '优惠券已过期';
          $coupons[$key]=$coupon;
          continue;
        }

        //判断是否是产品卷
        if ($coupon['type']==1) 
        {
          $goods_id = $coupon['goods_id'];
          $cartGoodcount = M('cart')->where(array('goods_id'=>$goods_id,'user_id'=>$this->user_id,'session_id'=>$this->session_id))->count();
          if ($cartGoodcount<=0) 
          {
            $coupon['can_use']=0;
            $coupons[$key]=$coupon;
            continue;
          }
          //判断产品券是否指定商家
          if ($coupon['is_appoint']==1) 
          {
            $goods = M('Goods')->where(array('goods_id'=>$goods_id,'is_on_sale'=>1,'del_status'=>0))->find();
            if ($goods['is_appoint']==1) 
            {
              $count = M('UcouponSag')->alias('us')->join('__GUSEB__ gb','gb.shop_id=us.shop_id')->where(array('us.clid'=>$coupon['id'],'gb.goods_id'=>$goods_id))->count();
              if ($count<=0) 
              {
               $coupon['can_use']=0;
               $coupons[$key]=$coupon;
                continue;
              }
            }else{
              $coupon['can_use']=0;
              $coupons[$key]=$coupon;
                continue;
            }
          }

        }else{
          //代金券情况下
          $ctags = M('UCouponTag')->where(array('clid'=>$coupon['id']))->count();
          if ($ctags>0) 
          {
           $gcount = M('UCouponTag')->alias('ut')->join('__GOODS_TAG__ gt','gt.tag_id=ut.tag_id')->join('__CART__ c','c.goods_id=gt.goods_id')->where(array('ut.clid'=>$coupon['id'],'c.user_id'=>$this->user_id,'c.session_id'=>$this->session))->count();
           if ($gcount<=0) 
           {
             $coupon['can_use']=0;
             $coupons[$key]=$coupon;
              continue;
           }
          }
        }
        $coupons[$key]=$coupon;
      }
      $this->assign('list',$coupons);
      return $this->fetch();

    }


    /*
     * 订单支付页面
     */
    public function cart4(){

        $order_id = I('order_id/d');
        $order = M('Order')->where("order_id", $order_id)->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("Mobile/User/order_detail",array('id'=>$order_id));
            header("Location: $order_detail_url");
            exit;
        }
       
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            //微信浏览器
            if($order['order_prom_type'] == 4){
                //预售订单
                $payment_where['code'] = 'weixin';
            }else{
                $payment_where['code'] = array('in',array('weixin','cod'));
            }
        }else{
            if($order['order_prom_type'] == 4){
                //预售订单
                $payment_where['code'] = array('neq','cod');
            }
            $payment_where['scene'] = array('in',array('0','1'));
        }
        $payment_where['status'] = '1';
        $payment_where['type'] = 'payment';
        // var_dump($payment_where);
        $paymentList = M('Plugin')->where($payment_where)->select();

        $paymentList = convert_arr_key($paymentList, 'code');
        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
            //判断当前浏览器显示支付方式
            if(($key == 'weixin' && !is_weixin()) || ($key == 'alipayMobile' && is_weixin())){
                unset($paymentList[$key]);
            }
        }

        $bank_img = include APP_PATH.'home/bank.php'; // 银行对应图片
        $payment = M('Plugin')->where("`type`='payment' and status = 1")->select();
        // var_dump($paymentList);die();
        $this->assign('paymentList',$paymentList);
        $this->assign('bank_img',$bank_img);
        $this->assign('order',$order);
        $this->assign('bankCodeList',$bankCodeList);
        $this->assign('pay_date',date('Y-m-d', strtotime("+1 day")));
        return $this->fetch();
    }







    /**
    *可用门店
    */
   public function useBusiness()
   {
    // print_r($_GET);die();
      $lat1 = I('get.lat');
      $lon1 = I('get.lon');
      
      $content = file_get_contents("http://api.map.baidu.com/geocoder/v2/?callback=renderReverse&location=$lat1,$lon1&output=json&pois=1&ak=NjnIbhfMbZ0weXzGXbpBbqB78ozUKM8f");
       $content = str_replace('renderReverse&&renderReverse(', '', $content);
       $content = substr($content, 0,strlen($content)-1);

         $json = json_decode($content);
         $cstr = $json->result->addressComponent->city;
         $astr = $json->result->addressComponent->district;
         $city =  substr($cstr,0,strlen($cstr)-3);
         $area =  substr($astr,0,strlen($astr)-3);
        $cinfo =  M('Area')->where(array('name'=>$city,'type'=>2))->find();
        $ainfo =  M('Area')->where(array('name'=>array('like',"%$area%"),'type'=>3))->find();
   

      $map['type'] = 1;
      $map['status'] = 1;
      $map['check_status'] = 1;
      $map['del_status'] = 0;
      $area_id = I('get.area_id');
      $shop_type = I('get.shop_type');

      if (!empty($area_id)&&isset($area_id)){
        $map['area'] = $area_id;
        $ainfo =  M('Area')->where(array('id'=>$area_id,'type'=>3))->find();
       
      }else{
        $ainfo['name'] = '所有';
        // $map['city'] = $cinfo['id'];
        // $map['area'] = $ainfo['id'];
      }

      if (!empty($shop_type)&&isset($shop_type))  $map['shop_type'] = $shop_type;
     
      $data = M('Admin')->where($map)->field('admin_id,shop_name,shop_address,shop_lat,shop_lon,shop_logo,province,city,area,shop_type,check_num')->select();
      if ($_GET['check_num']==3) {
        $data = M('Admin')->where($map)->order('check_num desc')->field('admin_id,shop_name,shop_address,shop_lat,shop_lon,shop_logo,province,city,area,shop_type,check_num')->select();
     
      }
      if (I('id',0)) {
         $dataArr = M('Guseb')->alias('gub')->join('__ADMIN__ a','a.admin_id=gub.shop_id')->where(array('gub.goods_id'=>I('id',0),'a.type'=>1,'a.status'=>1,'a.check_status'=>1,'a.del_status'=>0))->order('a.check_num desc')->field('a.admin_id,a.shop_name,a.shop_address,a.shop_lat,a.shop_lon,a.shop_logo,a.province,a.city,a.area,a.shop_type,a.check_num')->select();
         if (is_array($dataArr)&&count($dataArr)>0) {
            $data = $dataArr;
         }
       } 
      $list = array();
      foreach ($data as $key => $value) {
          $distance=getDistance($lat1,$lon1,$value['shop_lat'],$value['shop_lon']);
          $value['distance'] =number_format($distance/1000,2) ;
          $value['tags'] = M('ShopTag')->where(array('shop_id'=>$value['admin_id']))->field('tag_name')->select();
          $list[$distance] = $value;
         
      }
     
     $areas =  M('Area')->where(array('pid'=>$cinfo['id'],'type'=>3))->select();
     ksort($list);
   // if ($_GET['sort']==2&&$_GET['check_num']!=3) {
       
   // }
     
     $this->assign('list',$list);
     $this->assign('area',$ainfo);
     $this->assign('areas',$areas);
     $this->assign('lat',$lat1);
     $this->assign('lon',$lon1);
     $this->assign('sort',$_GET['sort']);
     return $this->fetch();
   }

   /**
    * 跳转到微信地图
    */
public function jumpMap()
{
   $admin_id = I('get.id');
   $info = M('Admin')->where(array('admin_id'=>$admin_id))->find();
   $this->assign('info',$info);
   return $this->fetch();

}

}
