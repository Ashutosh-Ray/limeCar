<?php
/**
 * ThinkPHP [ WE CAN DO IT JUST THINK ]
 +----------------------------------------------------------------------
 * Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
 * 
 +----------------------------------------------------------------------
 * 
 * 
 +----------------------------------------------------------------------
 *   
 * Date: 2015-09-09
 */
namespace app\admin\controller;

use think\Page;
use think\Db;

class Business extends Base {

//超级管理员查看商户
   public function index()
   {
        $map = array();
        $map['type'] = 1;
        $key = I('get.keywords');
        $check_status = I('get.check_status');
        if ($key) {
         $map['shop_name'] = array('like',"%$key%");
        }
        if (in_array($check_status,array('0','1','-1'))) {
           $map['check_status'] = $check_status;
        }
        $map['del_status']=0;
        $count = D('Admin')->where($map)->count();
        $page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $page->show();
        $data = M('Admin')->where($map)->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('list',$data);
      $this->assign('page',$show);

     
       return $this->fetch('index');
   }

  public function addEditBusiness()
   {
    if (IS_POST) {
 
    $id = I('POST.id',0);
    $admin_name = I('post.user_name');
    $shop_name = I('post.shop_name');
    if (empty($admin_name)) {
      exit(json_encode(array('stastus'=>0,'msg'=>'用户名不能为空')));
    }
    if (empty($shop_name)) {
      exit(json_encode(array('stastus'=>0,'msg'=>'商户名称不能为空')));
    }
    if (empty(I('post.shop_lat'))) {
      exit(json_encode(array('stastus'=>0,'msg'=>'纬度不能为空')));
    }
    if (empty(I('post.shop_lon'))) {
      exit(json_encode(array('stastus'=>0,'msg'=>'经度不能为空')));
    }
     if (empty(I('post.shop_logo'))) {
      exit(json_encode(array('stastus'=>0,'msg'=>'请上传logo')));
    }

    $name = D('Admin')->where(array('user_name'=>$admin_name))->find();

    if ($name&&empty($id)) {
      exit(json_encode(array('stastus'=>0,'msg'=>'用户名已经存在')));
    }
   
    $shop_admin = D('Admin')->where('admin_id='.$id)->find();
   
    $_POST['type'] = 1;
     if ($shop_admin) {
      unset($_POST['id']);
      if (empty($_POST['password'])) {
        unset($_POST['password']);
      }else{
          $_POST['password'] = encrypt($_POST['password']);
      }
      if (false !== D('Admin')->where(array('admin_id'=>$id))->save($_POST)) {
        M('ShopTag')->where(array('shop_id'=>$id))->delete();
        $tags = $_POST['tags'];
        if (is_array($tags)){
        foreach ($tags as $key => $tag) {
            $map = array();
            $map['tag_name'] = M('Tag')->where(array('id'=>$tag))->getField('name');
            $map['shop_id'] = $id;
            $map['tag_id'] = $tag;
            M('ShopTag')->add($map);
        }
        }
        exit(json_encode(array('stastus'=>1,'msg'=>'修改成功')));
      }else{
        exit(json_encode(array('stastus'=>0,'msg'=>'修改失败')));
      }
     }else{
          $sname = D('Admin')->where(array('shop_name'=>$shop_name))->find();
      if ($sname) {
      exit(json_encode(array('stastus'=>0,'msg'=>'商户已经存在')));
    }
      $_POST['add_time'] = time();
      $_POST['role_id'] = 8;
      $_POST['check_status'] = 1;
      $_POST['password'] = encrypt($_POST['password']);
      $res =D('Admin')->add($_POST);
        if ($res) {

        $tags = $_POST['tags'];
        if (is_array($tags)){
        foreach ($tags as $key => $tag) {
            $map = array();
            $map['tag_name'] = M('Tag')->where(array('id'=>$tag))->getField('name');
            $map['shop_id'] = $res;
            $map['tag_id'] = $tag;
            M('ShopTag')->add($map);
        }
        }
          exit(json_encode(array('stastus'=>1,'msg'=>'添加成功')));
        }else{
          exit(json_encode(array('stastus'=>0,'msg'=>'添加失败')));
        }
     }
    
  }else{
   $shop_admin = D('Admin')->where(array('admin_id'=>I('GET.id',0),'del_status'=>0))->find();
   $shopTag = M('ShopTag')->where(array('shop_id'=>I('GET.id', 0)))->field('tag_id')->select();
   $stags = array();
        if (is_array($shopTag)) {
           foreach ($shopTag as $key => $tag) {
            $stags[] = $tag['tag_id'];
            }
        }
   $this->assign('data',$shop_admin);
   $this->assign('stags',$stags);
  }
    $tags = M('Tag')->where(array('del_status'=>0))->select();
    $provinces = M('Area')->where(array('type'=>'1'))->select();
    $this->assign('provinces',$provinces);
    $this->assign('tags',$tags);
    
    return $this->fetch();
  }
public function checkBusiness()
{
   $shop_admin = D('Admin')->where(array('admin_id'=>I('GET.id',0),'del_status'=>0))->find();
   $this->assign('data',$shop_admin);
   return $this->fetch();
}

//审核商家
public function doCheckBusiness()
{
  $value = I('post.check_status',0);

  if (empty($value)||!is_numeric($value)||!in_array($value,array(1,0,-1))) {
    exit(json_encode(array('status'=>0,'msg'=>'操作失败1')));
  }
   $shop_admin = D('Admin')->where('admin_id='.I('post.id',0))->find();
   if (!$shop_admin) {
    exit(json_encode(array('status'=>0,'msg'=>'操作失败')));
   }
  
   $result = D('Admin')->where('admin_id='.I('post.id',0))->save(array('check_status'=>$value,'reason'=>I('post.rsn','无')));
  if (!$result)  exit(array('status'=>0,'msg'=>'操作失败'));
  $num = $shop_admin['phone'];
  if ($value==1)  $msg = "申请入驻三品車成功，登录名是".$shop_admin['user_name'].'【三品車】';
  if ($value==-1) $msg = "申请入驻三品車失败，请重新认证。【三品車】";
 
 sendAllMsg($num,$msg,'');
exit(json_encode(array('status'=>1,'msg'=>'操作成功')));
}

  public function del_shop_admin()
  {
    $id = I('get.id');
    $map['admin_id'] = $id;
    $map['type'] = 1;
    $old = D('Admin')->where($map)->find();
    if ($old) {
       D('Admin')->where($map)->save(array('del_status'=>1));
       $this->success('删除成功');
    }else{
      $this->error('删除失败');
    }
  }

  //商户修改密码
public function changeSecret()
{

  if (IS_POST) {
    $admin_id = $_SESSION['admin_id'];
    $oldpwd = encrypt($_POST['old_pwd']);
    $newpwd = encrypt($_POST['new_pwd']);
    if (empty($oldpwd)||!isset($oldpwd)) exit(json_encode(array('msg'=>'请填写旧密码')));
    if (empty($newpwd)||!isset($newpwd)) exit(json_encode(array('msg'=>'请填写新密码')));
    $admin = M('Admin')->where(array('password'=>$oldpwd,'admin_id'=>$admin_id))->find();
    if (!$admin) exit(json_encode(array('msg'=>'旧密码有误')));
    $res = M('Admin')->where(array('admin_id'=>$admin_id))->save(array('password'=>$newpwd));
    if ($res!==false){
      exit(json_encode(array('msg'=>'修改成功')));
    }else{
      exit(json_encode(array('msg'=>'修改失败')));
    } 
  }

  return $this->fetch();
}

  public function del_check_order()
  {
    $id = I('get.id');
    $map['id'] = $id;
    $old = D('CheckOrder')->where($map)->find();
 
    
    if ($old) {
      if ($old['status']==0) {
      $this->error('还未核对不能删除');
    }
       D('CheckOrder')->where($map)->delete();
       $this->success('删除成功');
    }else{
      $this->error('删除失败');
    }
  }


  public function do_check_order()
  {
    $order_sn = $_POST['order_sn'];
    $type=session('type');
    if ($type!=1) {
      $this->error('您没有验劵权限');
    }
    if (empty($order_sn)) {
      $this->error('该卷码有误');
    }
    $old = M('Order')->where(array('order_sn'=>$order_sn))->find();
    if (!$old) {
      $this->error('该卷不存在');
    }
    if ($old['check_status']==1) {
      $this->error('该卷已消费过');
    }
    $order = M('Order')->where(array('order_sn'=>$order_sn))->find();
    $order_goods = M('OrderGoods')->where(array('order_id'=>$order['order_id']))->find();
    $_POST['goods_name'] = $order_goods['goods_name'];
    $_POST['add_time'] = time();
    $_POST['shop_name'] = session('shop_name');
    $_POST['business_id'] = session('admin_id');
    if (M('CheckOrder')->add($_POST)) {
      M('Admin')->where(array('admin_id'=>$_POST['business_id']))->setInc('check_num',1);
      M('Order')->where(array('order_sn'=>$order_sn))->save(array('check_status'=>1));
      $this->success('验券成功');
    }else{
      $this->error('验券失败');
    }

  }

  public function do_check_order2(){
    $order_sn = $_POST['order_sn'];
    $type=session('type');
    if ($type!=1) {
       exit(json_encode(array('status'=>0,'msg'=>'您没有验劵权限')));
    }
    if (empty($order_sn)) {
       exit(json_encode(array('status'=>0,'msg'=>'该卷码有误')));
    }
    $order_goods = M('OrderGoods')->where(array('goods_osn'=>$order_sn))->find();
    if (!$order_goods) {
       exit(json_encode(array('status'=>0,'msg'=>'该卷不存在')));
    }
    if ($order_goods['check_status']==1) {
       exit(json_encode(array('status'=>0,'msg'=>'该卷已消费过')));
    }
    $goodsInfo = M('Goods')->where(array('goods_id'=>$order_goods['goods_id']))->field('goods_id,is_appoint,is_ctime,goods_name')->find();
   
    $arr = array('status'=>-1,'shop_names'=>'','range'=>1,'range_time'=>'');
    // $range = 1;//1代表范围正确
    if ($goodsInfo['is_appoint']==1) {
        $count = M('Oguseb')->where(array('rec_id'=>$order_goods['rec_id'],'shop_id'=>$_SESSION['admin_id']))->count();
        if ($count<1) {
          $arr['range']=0;
        }
        $shops = M('Oguseb')->alias('gb')->join('__ADMIN__ a', 'a.admin_id=gb.shop_id','LEFT')->where(array('gb.rec_id'=>$order_goods['rec_id']))->field('gb.shop_id,a.shop_name')->select();
       if (is_array($shops)) {
            $shop_names = '';
            foreach ($shops as $key => $value) {
              if (empty($shop_names)) {
                 if (!empty($value['shop_name'])) {
                    $shop_names .=$value['shop_name'];
                 }
              }else{
                if (!empty($value['shop_name'])) {
                    $shop_names .='、'.$value['shop_name'];
                 }
              }
            }
            $arr['shop_names'] = $shop_names;
         }else{
            $arr['shop_names'] = '无可用';
         }
    }else{
      $arr['shop_names'] = '不限';
    }

     if ($goodsInfo['is_ctime']==1) {
      $now = strtotime(date('Y-m-d',time()));
      $map = array();
      $map['end_time'] = array('egt',$now);
      $map['start_time'] = array('elt',$now); 
      $map['rec_id'] = $order_goods['rec_id'];
      $count =  M('Ogtime')->where($map)->find();

      if ($count<1) {
        $arr['range']=0;
        # code...
      }
      $ctime = M('Ogtime')->where(array('rec_id'=>$order_goods['rec_id']))->find();
      $arr['range_time'] = date('Y-m-d',$ctime['start_time']).'至'.date('Y-m-d',$ctime['end_time']);
    }else{
      $arr['range_time'] = '不限';
    }


    if ($arr['range']==1) {
      $arr['goods_name'] =$goodsInfo['goods_name'];
      $arr['status']  = 1;
      exit(json_encode($arr));
    }else{
      exit(json_encode($arr));
    }

   }



  public function real_check_order2()
  {
    $order_sn = $_POST['order_sn'];
    $type=session('type');
    if ($type!=1) {
       exit(json_encode(array('status'=>0,'msg'=>'您没有验劵权限')));
    }
    if (empty($order_sn)) {
       exit(json_encode(array('status'=>0,'msg'=>'该卷码有误')));
    }
    $order_goods = M('OrderGoods')->where(array('goods_osn'=>$order_sn))->find();
    if (!$order_goods) {
       exit(json_encode(array('status'=>0,'msg'=>'该卷不存在')));
    }
    if ($order_goods['check_status']==1) {
       exit(json_encode(array('status'=>0,'msg'=>'该卷已消费过')));
    }
    $save_data['check_num'] = $order_goods['check_num']+1;
    if($save_data['check_num']==$order_goods['goods_num']) $save_data['check_status']=1;
    if( M('OrderGoods')->where(array('goods_osn'=>$order_sn))->save($save_data)){
         $_POST['goods_name'] = $order_goods['goods_name'];
         $_POST['add_time'] = time();
         $_POST['shop_name'] = session('shop_name');
         $_POST['business_id'] = session('admin_id');
         unset($_POST['id']);
    
         M('CheckOrder')->add($_POST);
         M('Admin')->where(array('admin_id'=>$_POST['business_id']))->setInc('check_num',1);
         $order_count = M('OrderGoods')->where(array('order_id'=>$order_goods['order_id'],'check_status'=>0))->count();
         if($order_count==0){
             M('Order')->where(array('order_id'=>$order_goods['order_id']))->save(array('check_status'=>1));
         }
          consume_template($order_goods,session('shop_name'));//验卷成功通知
          exit(json_encode(array('status'=>1,'msg'=>'验券成功')));
    }else{
     
      exit(json_encode(array('status'=>0,'msg'=>'验券失败')));
    }

  }

  public function checkOrder()
  {
    $data = M('admin')->where(array('admin_id'=>$_SESSION['admin_id']))->find();

    $this->assign('data',$data);
    return $this->fetch();
  }


  public function checkList()
  {
    $map['business_id'] = session('admin_id');
    $count = D('CheckOrder')->where($map)->count();
    $page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
    $show = $page->show();
    $data = M('CheckOrder')->where($map)->order('id desc')->limit($page->firstRow.','.$page->listRows)->select();
    $this->assign('list',$data);
    $this->assign('page',$show);

    return $this->fetch();

  }

  public function businessCheckList()
  {
    $map['business_id'] = I('get.id');
    $count = D('CheckOrder')->where($map)->count();
    $page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
    $show = $page->show();
    $data = M('CheckOrder')->where($map)->order('id desc')->limit($page->firstRow.','.$page->listRows)->select();
    $this->assign('list',$data);
    $this->assign('page',$show);
    $this->assign('role_id',session('role_id'));
    $this->assign('admin_id',session('admin_id'));
    return $this->fetch();

  }

//核对
  public function check_status()
  {
    $id = I('get.id');
    $map['id'] = $id;
    $data['status'] = 1;
    $data['op_time'] = time();
    $data['op_id'] = session('admin_id');
    $old = D('CheckOrder')->where($map)->find();
    if ($old['status']==1) {
      $this->error('已经核对过了');
    }
    if ($old) {
       D('CheckOrder')->where($map)->save($data);
       $this->success('核对成功');
    }else{
      $this->error('核对失败');
    }
  }

public function area()
{
  if (IS_POST) {
    $pid = $_POST['id'];
    $array = M('Area')->where(array('pid'=>$pid))->select();
    echo json_encode(array('info'=>$array,'status'=>'1'));
  }else{
    exit(json_encode(array('status'=>'0')));
  }
}

public function login_meituan()
{
  $data = ['login'=>'13205148096','password'=>'Xlzh2014','auto_login'=>1,'captcha'=>''];
  $url  = 'https://epassport.meituan.com/account/login';
  $result=$this->login_all($data,'meituan',$url);
  var_dump($result);
}

public function login_baidu()
{
  $data = array();
  $result = $this->login_token();
  $this->login_all($data,'','https://cas.baidu.com/login/phone');
}
public function login_all($data,$type,$login_url)
{
  //设置cookie保存路径
  $cookie = dirname(__FILE__) . '/'.$type.'.txt';
 $result = $this->login_post($login_url, $cookie, $data);
 return $result;

}

public function login_post($url, $cookie, $post) {
        $curl = curl_init();//初始化curl模块
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);//登录提交的地址
        curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//是否自动显示返回的信息
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie); //设置Cookie信息保存在指定的文件中
        curl_setopt($curl, CURLOPT_POST, 1);//post方式提交
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($curl);//执行cURL
        $httpCode = curl_getinfo($curl);
        curl_close($curl);//关闭cURL资源，并且释放系统资源

        return array('code'=>$httpCode['http_code']);

    }

    public function login_token($url,$post) {
        $curl = curl_init();//初始化curl模块
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);//登录提交的地址
        curl_setopt($curl, CURLOPT_HEADER, 0);//是否显示头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//是否自动显示返回的信息
        curl_setopt($curl, CURLOPT_POST, 1);//post方式提交
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息
        $result = curl_exec($curl);//执行cURL
        curl_close($curl);//关闭cURL资源，并且释放系统资源
        return $result;
    }


    public function http_post_json($url, $data)
      {
        $jsonStr = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($jsonStr)
          )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ( $ch );
        return array($httpCode, $response);
      }




      //顺付天下
      public function shunfu()
      {
        $url= "http://test.shunfu-pay.cn/shunfupay-admin/api/pay/doPay.html";  
        $post_data=array(
            'mchNo' =>  'shunfupay001' ,
            'orderNo' =>  time(),
            'amount' =>  '0.01',
            'discountableAmount' =>  '0',
            'undiscountableAmount' =>  '0.01',
            'goodsName' =>  '测试商品' ,
            'goodsDesc' =>  '测试商品1' ,
            'payChannelTypeNo' =>  '0503' ,
            'overtime' =>  '60',
            'operatorId' =>  'dd' ,
            'storeId' =>  'ff', 
            'terminalId' =>  'ggg' ,
            'timeStamp' =>  time(),
            'extendParams' =>  '{\'agentId\':\'123\'}' ,
            
        );
        $post_data['sign'] = MakeSign($post_data,'31997dfe10d50b0236060baeae794d39');
        
        $result = http_post($url,$post_data);
        $result = json_decode($result,1);
        var_dump($result);die();
       
      }
      
      
      /**
       * 一码支付测试调用接口：   http://test.shunfu-pay.cn/shunfupay-admin/api/pay/unifyCodeBySelf.html?mchNo=商户编号
       */
      public function fillShufuPayInfo()
      {
          
          $sUrl = 'http://shunfu-pay.cn/shunfupay-admin/api/merchant/add.html';
          $sBrandNo = 'SZXL10004';
          $sBrandKey = '5b4a03d5cc3d9c59294b873daf175ade';
          
          $iAdminId = intval(I('get.adminId'));
          if(I('post.'))
          {
              $aAdmin = M('admin')->where(array('admin_id'=>$iAdminId))->find();
              $aInsert['adminId'] = $iAdminId;
              $aInsert['brandNo'] = $sBrandNo;
              $aInsert['loginPwd'] = rand(10000000,99999999);
              $aInsert['contact'] = I('post.contact'); //联系人姓名
              $aInsert['email'] = I('post.email');
              $aInsert['contactPhoneNo'] = I('post.contactPhoneNo'); //联系人电话
              $aInsert['fullName'] = I('post.fullName');       //商户名称
              $aInsert['companyAddress'] = I('post.companyAddress');
              $aInsert['shortName'] = I('post.shortName');    
              $aInsert['contactLandline'] = I('post.contactLandline'); 
              $aInsert['area'] = I('post.area');
              $aInsert['bankAccountName'] = I('post.bankAccountName');
              $aInsert['bank'] = I('post.bank');
              $aInsert['subBranchName'] = I('post.subBranchName');
              $aInsert['bankNo'] = I('post.bankNo');
              $aInsert['bankType'] = I('post.bankType');
              $aInsert['clientManager']="王潇阳";
              $aInsert['payNotifyUrl'] = C('WEB_SITE_DOMAIN').'index.php/Home/Notice/unifyCodePayNotify';
              $aInsert['rate'] = I('post.rate');
              
              
              $aInsert['businessLicense'] = I('post.rate');
              $aInsert['idCardFront'] = I('post.rate');
              $aInsert['idCardBack'] = I('post.rate');
              $aInsert['shopPic'] = I('post.rate');
              $aInsert['bankCardFront'] = I('post.rate');
              $aInsert['bankCardBack'] = I('post.rate');
              $aInsert['openingPermit'] = I('post.rate');
              
              $sBusinessLicenseFiles = '';
              if(!empty($aInsert['businessLicense'])) $sBusinessLicenseFiles .= $aInsert['businessLicense'].',';
              if(!empty($aInsert['idCardFront'])) $sBusinessLicenseFiles .= $aInsert['idCardFront'].',';
              if(!empty($aInsert['idCardBack'])) $sBusinessLicenseFiles .= $aInsert['idCardBack'].',';
              if(!empty($aInsert['shopPic'])) $sBusinessLicenseFiles .= $aInsert['shopPic'].',';
              if(!empty($aInsert['bankCardFront'])) $sBusinessLicenseFiles .= $aInsert['bankCardFront'].',';
              if(!empty($aInsert['bankCardBack'])) $sBusinessLicenseFiles .= $aInsert['bankCardBack'].',';
              if(!empty($aInsert['openingPermit'])) $sBusinessLicenseFiles .= $aInsert['businessLicense'].',';
              $aInsert['businessLicenseFiles'] = $sBusinessLicenseFiles;
              
              $aInsert['merchantReturnUrl'] = C('WEB_SITE_DOMAIN').'index.php/Home/Notice/merchantReturn';;
              $aInsert['elecInvioceFlag'] = 'Y';
              $aInsert['mchType'] = 1;
              $aInsert['timeStamp'] = time();
              $aInsert['sign'] = MakeSign($aInsert,$sBrandKey);
              
              if (empty($aInsert['contact'])) $this->error('请填写联系人姓名');
              elseif (empty($aInsert['email'])) $this->error('请填写邮箱');
              elseif (empty($aInsert['contactPhoneNo'])) $this->error('请填写联系人电话');
              elseif (empty($aInsert['fullName'])) $this->error('请填写商户名称');
              elseif (empty($aInsert['companyAddress'])) $this->error('请填写注册地址');
              elseif (empty($aInsert['shortName'])) $this->error('请填写商户简称');
              elseif (empty($aInsert['contactLandline'])) $this->error('请填写固定座机号');
              elseif (empty($aInsert['bankAccountName'])) $this->error('请填写开户名称');
              elseif (empty($aInsert['bank'])) $this->error('请填写开户银行');
              elseif (empty($aInsert['subBranchName'])) $this->error('请填写支行名称');
              elseif (empty($aInsert['bankNo'])) $this->error('请填写开户账号');
              elseif (empty($aInsert['rate'])) $this->error('请填写费率');
              elseif (empty($aInsert['contactLandline'])) $this->error('固定座机号');
              else 
              {
                  $oResult = http_post($sUrl,$aInsert);
                  $aResult = json_decode($oResult,1);
                  unset($aInsert['sign']);
                  
                  if($aResult['result']=='ERROR')
                  {
                      $this->error($aResult['msg']);
                  }
                  else 
                  {
                      $aInsert['mchKey'] = $aResult['data']['mchKey'];
                      $aInsert['mchNo'] = $aResult['data']['mchNo'];
                      $aInsert['qrcode'] = '/public/images/sf_qrcode/'.$aInsert['mchNo'].'.png';
                      $aInsert['create_time'] = time();
                      
                      $sUrl = 'http://shunfu-pay.cn/shunfupay-admin/api/pay/unifyCodeBySelf.html?mchNo='.$aInsert['mchNo'];
                      vendor('phpqrcode.phpqrcode');
                      $url = urldecode($_GET["data"]);
                      \QRcode::png($sUrl,'./public/images/sf_qrcode/'.$aInsert['mchNo'].'.png',5,20);
                      
                      M('Sf_merchant_payment')->add($aInsert);
                  }
              }
          }
          else 
          {
              $this->assign('iAdminId',$iAdminId);
              $this->assign('passwd',rand(100000,999999));
              return $this->fetch('fill_shufu_pay_info');
          }
      }
      
      public function viewShufuPayInfo()
      {
          $iAdminId = intval(I('get.adminId'));
          $aSfMerchantPayment = M('Sf_merchant_payment')->where(array('adminId'=>$iAdminId))->find();
          
          $this->assign('aSfMerchantPayment',$aSfMerchantPayment);
          return $this->fetch('view_shufu_pay_info');
      }
      
      
      
      
      
      
      
}