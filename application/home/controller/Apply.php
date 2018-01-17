<?php
namespace app\home\controller; 
use app\home\logic\UsersLogic;
use app\home\logic\CartLogic;
use app\home\model\Message;
use think\Controller;
use think\Url;
use think\Page;
use think\Config;
use think\Verify;
use think\Db;
/**
* 	商家申请入驻三品車
*/
class Apply extends Base
{

  public function index()
  {
     $ip = getIP();
     $content = file_get_contents("http://apis.map.qq.com/ws/location/v1/ip?ip=$ip&key=EIMBZ-RBNH4-NZQUG-X7RKQ-3SPPH-YSFCX");
      // $content = file_get_contents("http://apis.map.qq.com/ws/location/v1/ip?key=EIMBZ-RBNH4-NZQUG-X7RKQ-3SPPH-YSFCX");
     $json = json_decode($content);
     $lat = $json->result->location->lat;
     $lng = $json->result->location->lng;
     $url ="http://apis.map.qq.com/ws/geocoder/v1/?location=$lat,$lng&key=EIMBZ-RBNH4-NZQUG-X7RKQ-3SPPH-YSFCX&output=json&callback=?";
     $data = file_get_contents($url);
     $adress = json_decode($data);
     $info = $adress->result->address;
  	 $sms_time_out = tpCache('sms.sms_time_out')>0 ? tpCache('sms.sms_time_out') : 120;
  	if (IS_POST) {
 
   	$id = I('POST.id',0);
   	$admin_name = I('post.user_name');
   	$shop_name = I('post.shop_name');
    if (empty($admin_name)) {
      exit(json_encode(array('status'=>0,'msg'=>'用户名不能为空')));
    }
    if (empty($shop_name)) {
      exit(json_encode(array('status'=>0,'msg'=>'商户名称不能为空')));
    }
    if (empty(I('post.shop_lat'))) {
      exit(json_encode(array('status'=>0,'msg'=>'纬度不能为空')));
    }
    if (empty(I('post.shop_lon'))) {
      exit(json_encode(array('status'=>0,'msg'=>'经度不能为空')));
    }
     if (empty(I('post.shop_logo'))) {
      exit(json_encode(array('status'=>0,'msg'=>'请上传logo')));
    }
    if (empty(I('post.business_licence'))) {
      exit(json_encode(array('status'=>0,'msg'=>'请上传营业执照')));
    }
     if (empty(I('post.card_font'))) {
      exit(json_encode(array('status'=>0,'msg'=>'请上传法人身份证正面')));
    }
    if (empty(I('post.card_back'))) {
      exit(json_encode(array('status'=>0,'msg'=>'请上传法人身份证反面')));
    }
    if (empty(I('post.code'))) {
      exit(json_encode(array('status'=>0,'msg'=>'手机验证码不能为空')));
    }
    $where['mobile'] = I('post.phone');
    $where['status'] = 1;
    $code = I('post.code');
    $where['session_id'] = session_id();
    $res = M('SmsLog')->where($where)->order('id DESC')->find();
    if (!is_array($res) || !$res['code'] == $code) {
    	exit(json_encode(array('status'=>0,'msg'=>'验证码错误')));
    }
    if (is_array($res) && $res['code'] == $code) {
    	// exit(json_encode(array('status'=>0,'msg'=>'验证码超时1')));
    	if (time()>$res['add_time']+$sms_time_out) {
    		exit(json_encode(array('status'=>0,'msg'=>'验证码超时')));
    	}
    }

   	$name = D('Admin')->where(array('user_name'=>$admin_name,'check_status'=>1))->find();

   	if ($name&&empty($id)) {
   		exit(json_encode(array('status'=>0,'msg'=>'用户名已经存在')));
   	}
   
   	$shop_admin = D('Admin')->where('admin_id='.$id)->find();
   	$_POST['password'] = encrypt($_POST['password']);
   	$_POST['type'] = 1;
     if ($shop_admin) {
     	unset($_POST['id']);
     	if (empty($_POST['password'])) {
     		unset($_POST['password']);
     	}
     	if (false !== D('Admin')->where(array('admin_id'=>$id))->save($_POST)) {
     		exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
     	}else{
     		exit(json_encode(array('status'=>0,'msg'=>'修改失败')));
     	}
     }else{
     	  	$sname = D('Admin')->where(array('shop_name'=>$shop_name))->find();
   		if ($sname) {
   		exit(json_encode(array('status'=>0,'msg'=>'商户已经存在')));
   	}
     	$_POST['add_time'] = time();
      $_POST['role_id'] = 1;

        if (D('Admin')->add($_POST)) {
        	exit(json_encode(array('status'=>1,'msg'=>'添加成功')));
        }else{
        	exit(json_encode(array('status'=>0,'msg'=>'添加失败')));
        }
     }
   	
  }else{
   $shop_admin = D('Admin')->where('admin_id='.I('GET.id',0))->find();
   $this->assign('data',$shop_admin);
  }
    
        $this->assign('sms_time_out', $sms_time_out);
    $provinces = M('Area')->where(array('type'=>'1'))->select();
    $this->assign('provinces',$provinces);
     $this->assign('shop_lon',$json->result->location->lng);
     $this->assign('shop_lat',$json->result->location->lat);
     $this->assign('shop_address',$info);
   	return $this->fetch();
  }

  public function changeInfo()
  {
    return $this->fetch();
  }
public function addEditBusiness()
   {
   	
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


   public function tengXunMap()
   {
     // $ip = getIP();
     // $ip = '122.193.102.222';
     // $content = file_get_contents("http://apis.map.qq.com/ws/location/v1/ip?ip=$ip&key=EIMBZ-RBNH4-NZQUG-X7RKQ-3SPPH-YSFCX");
     // $content = file_get_contents("http://apis.map.qq.com/ws/location/v1/ip?key=EIMBZ-RBNH4-NZQUG-X7RKQ-3SPPH-YSFCX");
     // $json = json_decode($content);
     // $content = file_get_contents("http://api.map.baidu.com/geoconv/v1/?coords=120.585316,31.298886&from=1&to=5&ak=NjnIbhfMbZ0weXzGXbpBbqB78ozUKM8f");
  
     // $json2 = json_decode($content);
     // $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=NjnIbhfMbZ0weXzGXbpBbqB78ozUKM8f&ip={$ip}&coor=bd09ll");
     // $json3 = json_decode($content);
     if(!empty($_GET['lat'])&&!empty($_GET['lng'])){
        $this->assign('shop_lon',$_GET['lng']);
     $this->assign('shop_lat',$_GET['lat']);
     }else{
      $ip = getIP();
     $content = file_get_contents("http://apis.map.qq.com/ws/location/v1/ip?ip=$ip&key=EIMBZ-RBNH4-NZQUG-X7RKQ-3SPPH-YSFCX");
        // $content = file_get_contents("http://apis.map.qq.com/ws/location/v1/ip?key=EIMBZ-RBNH4-NZQUG-X7RKQ-3SPPH-YSFCX");
        $json = json_decode($content);
  
        $this->assign('shop_lon',$json->result->location->lng);
        $this->assign('shop_lat',$json->result->location->lat);
     }
     

     return $this->fetch();
   }
}