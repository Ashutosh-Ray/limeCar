<?php
/**
 * ThinkPHP [ WE CAN DO IT JUST THINK ]
 +----------------------------------------------------------------------
 * * Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
 * 网站地址: http://www.imshop.cn
 +----------------------------------------------------------------------
 * 
 * 
 +----------------------------------------------------------------------
 * $Author: Alince 2015-08-10 $
 *
 * 为兼容以前的Thinkphp3.2老用户习惯, 用TP5助手函数实现 M( ) D( ) U( ) S( )等单字母函数
 */ 
 use think\Db;
/**
 * ThinkPHP [ WE CAN DO IT JUST THINK ]检验登陆
 * @param
 * @return bool
 */
function is_login(){
    if(isset($_SESSION['admin_id']) && $_SESSION['admin_id'] > 0){
        return $_SESSION['admin_id'];
    }else{
        return false;
    }
}
/**
 * 获取用户信息
 * @param $user_id_or_name  用户id 邮箱 手机 第三方id
 * @param int $type  类型 0 user_id查找 1 邮箱查找 2 手机查找 3 第三方唯一标识查找
 * @param string $oauth  第三方来源
 * @return mixed
 */
function get_user_info($user_id_or_name,$type = 0,$oauth='',$openid=''){
    $map = array();
    if($type == 0)
        $map['user_id'] = $user_id_or_name;
    if($type == 1)
        $map['email'] = $user_id_or_name;
    if($type == 2)
        $map['mobile'] = $user_id_or_name;
    if($type == 3){
        $map['openid'] = $user_id_or_name;
        $map['oauth'] = $oauth;
    }
    if($type == 4){
        $map['unionid'] = $user_id_or_name;
        $map['oauth'] = $oauth;
    }
     $user = M('users')->where($map)->find();
    if (!$user&&$type==4&&!empty($openid)) {
        $arr = array();
        $arr['openid'] = $openid;
        $arr['oauth'] = $oauth;
        $user = M('users')->where($arr)->find();
    }


    if (!$user&&$type==4&&!empty($openid)) {
        $arr = array();
        $arr['unionid'] = $user_id_or_name;
        $arr['oauth'] = $oauth;
        $user = M('users')->where($arr)->find();
    }
   
    return $user;
}

/**
 * 更新会员等级,折扣，消费总额
 * @param $user_id  用户ID
 * @return boolean
 */
function update_user_level($user_id){
    $level_info = M('user_level')->order('level_id')->select();
    $total_amount = M('order')->where("user_id=:user_id AND pay_status=1 and order_status not in (3,5)")->bind(['user_id'=>$user_id])->sum('order_amount+user_money');
    if($level_info){
        foreach($level_info as $k=>$v){
            if($total_amount >= $v['amount']){
                $level = $level_info[$k]['level_id'];
                $discount = $level_info[$k]['discount']/100;
            }
        }
        $user = session('user');
        $updata['total_amount'] = $total_amount;//更新累计修复额度
        //累计额度达到新等级，更新会员折扣
        if(isset($level) && $level>$user['level']){
            $updata['level'] = $level;
            $updata['discount'] = $discount;    
        }
        M('users')->where("user_id", $user_id)->save($updata);
    }
}

/**
 *  商品缩略图 给于标签调用 拿出商品表的 original_img 原始图来裁切出来的
 * @param type $goods_id  商品id
 * @param type $width     生成缩略图的宽度
 * @param type $height    生成缩略图的高度
 */
function goods_thum_images($goods_id,$width,$height){

     if(empty($goods_id))
         return '';
    //判断缩略图是否存在
    $path = "public/upload/goods/thumb/$goods_id/";
    
    $original_img = M('Goods')->where("goods_id", $goods_id)->getField('original_img');
    if(empty($original_img)){
      $GoodsImages= M('GoodsImages')->where("goods_id", $goods_id)->find();
       $original_img = $GoodsImages['image_url'];
    }
    $array = explode('/', $original_img);
    $str = end($array);
    $arr = explode('.', $str);
    $imgname =  $arr[0];
    $goods_thumb_name ="goods_thumb_{$goods_id}_{$width}_{$height}_$imgname";
 
    // 这个商品 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg'; 
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg'; 
    if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif'; 
    if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png'; 
         
    // $original_img = M('Goods')->where("goods_id", $goods_id)->getField('original_img');
  
    if(empty($original_img)) return '';
    
    $original_img = '.'.$original_img; // 相对路径
    if(!file_exists($original_img)) return '';

    //$image = new \think\Image();
    $image = \think\Image::open($original_img);
        
    $goods_thumb_name = $goods_thumb_name. '.'.$image->type();
    //生成缩略图
    if(!is_dir($path)) 
        mkdir($path,0777,true);
    
    //参考文章 http://www.mb5u.com/biancheng/php/php_84533.html  改动参考 http://www.thinkphp.cn/topic/13542.html
    $image->thumb($width, $height,2)->save($path.$goods_thumb_name,'jpg',100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
    
    //图片水印处理
    $water = tpCache('water');
    if($water['is_mark']==1){
        $imgresource = './'.$path.$goods_thumb_name;
        if($width>$water['mark_width'] && $height>$water['mark_height']){
            if($water['mark_type'] == 'img'){
                $image->open($imgresource)->water(".".$water['mark_img'],$water['sel'],$water['mark_degree'])->save($imgresource);
            }else{
                //检查字体文件是否存在
                if(file_exists('./zhjt.ttf')){
                    $image->open($imgresource)->text($water['mark_txt'],'./zhjt.ttf',20,'#000000',$water['sel'])->save($imgresource);
                }
            }
        }
    }
   
    return '/'.$path.$goods_thumb_name;
}

/**
 * 商品相册缩略图
 */
function get_sub_images($sub_img,$goods_id,$width,$height){
    //判断缩略图是否存在
    $path = "public/upload/goods/thumb/$goods_id/";
    $array = explode('/', $sub_img['image_url']);
    $str = end($array);
    $arr = explode('.', $str);
    $imgname =  $arr[0];
    $goods_thumb_name ="goods_sub_thumb_{$sub_img['img_id']}_{$width}_{$height}_$imgname";
    //这个缩略图 已经生成过这个比例的图片就直接返回了
    if(file_exists($path.$goods_thumb_name.'.jpg'))  return '/'.$path.$goods_thumb_name.'.jpg';
    if(file_exists($path.$goods_thumb_name.'.jpeg')) return '/'.$path.$goods_thumb_name.'.jpeg';
    if(file_exists($path.$goods_thumb_name.'.gif'))  return '/'.$path.$goods_thumb_name.'.gif';
    if(file_exists($path.$goods_thumb_name.'.png'))  return '/'.$path.$goods_thumb_name.'.png';
    
    $original_img = '.'.$sub_img['image_url']; //相对路径
    if(!file_exists($original_img)) return '';
    // return $sub_img['image_url'];
    //$image = new \think\Image();
    //$image->open($original_img);
        $image = \think\Image::open($original_img);
    $goods_thumb_name = $goods_thumb_name. '.jpg';
    // $goods_thumb_name = $goods_thumb_name. '.'.$image->type();
    // 生成缩略图
    if(!is_dir($path)){
        mkdir($path,777,true);
        chmod($path, 0777);
    }
    
    $image->thumb($width, $height,2)->save($path.$goods_thumb_name,NULL,100); //按照原图的比例生成一个最大为$width*$height的缩略图并保存
    return '/'.$path.$goods_thumb_name;
}

//获取商品相册的第一张图
function getFirstImg($goods_id,$width,$height)
{

    $first = M('goods_images')->where(array('goods_id'=>$goods_id))->select();
    $original_img = M('goods')->where(array('goods_id'=>$goods_id))->find();
    if(!$first) return "";
    foreach ($first as $key => $value) {
        if (in_array($original_img['original_img'],$value)) {
            unset($first[$key]);
        }
    }
    if(!$first[0]){ $first[0]= M('goods_images')->where(array('goods_id'=>$goods_id))->find();}
    $imgurl = get_sub_images($first[0],$goods_id,$width,$height);
    return $imgurl;
}

/**
 * 刷新商品库存, 如果商品有设置规格库存, 则商品总库存 等于 所有规格库存相加
 * @param type $goods_id  商品id
 */
function refresh_stock($goods_id){
    $count = M("SpecGoodsPrice")->where("goods_id", $goods_id)->count();
    if($count == 0) return false; // 没有使用规格方式 没必要更改总库存

    $store_count = M("SpecGoodsPrice")->where("goods_id", $goods_id)->sum('store_count');
    M("Goods")->where("goods_id", $goods_id)->save(array('store_count'=>$store_count)); // 更新商品的总库存
}

/**
 * 根据 order_goods 表扣除商品库存
 * @param type $order_id  订单id
 */
function minus_stock($order_id){
    $orderGoodsArr = M('OrderGoods')->where("order_id", $order_id)->select();
    foreach($orderGoodsArr as $key => $val)
    {
        // 有选择规格的商品
        if(!empty($val['spec_key']))
        {   // 先到规格表里面扣除数量 再重新刷新一个 这件商品的总数量
            M('SpecGoodsPrice')->where(['goods_id'=>$val['goods_id'],'key'=>$val['spec_key']])->setDec('store_count',$val['goods_num']);
            refresh_stock($val['goods_id']);
        }else{
            M('Goods')->where("goods_id", $val['goods_id'])->setDec('store_count',$val['goods_num']); // 直接扣除商品总数量
        }
        M('Goods')->where("goods_id", $val['goods_id'])->setInc('sales_sum',$val['goods_num']); // 增加商品销售量
        //更新活动商品购买量
        if($val['prom_type']==1 || $val['prom_type']==2){
            $prom = get_goods_promotion($val['goods_id']);
            if($prom['is_end']==0){
                $tb = $val['prom_type']==1 ? 'flash_sale' : 'group_buy';
                M($tb)->where("id", $val['prom_id'])->setInc('buy_num',$val['goods_num']);
                M($tb)->where("id", $val['prom_id'])->setInc('order_num');
            }
        }
    }
}

/**
 * 邮件发送
 * @param $to    接收人
 * @param string $subject   邮件标题
 * @param string $content   邮件内容(html模板渲染后的内容)
 * @throws Exception
 * @throws phpmailerException
 */
function send_email($to,$subject='',$content=''){    
    vendor('phpmailer.PHPMailerAutoload'); ////require_once vendor/phpmailer/PHPMailerAutoload.php';
    $mail = new PHPMailer;
    $config = tpCache('smtp');
    $mail->CharSet  = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->isSMTP();
    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //调试输出格式
    //$mail->Debugoutput = 'html';
    //smtp服务器
    $mail->Host = $config['smtp_server'];
    //端口 - likely to be 25, 465 or 587
    $mail->Port = $config['smtp_port'];
    
    if($mail->Port === 465) $mail->SMTPSecure = 'ssl';// 使用安全协议
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    //用户名
    $mail->Username = $config['smtp_user'];
    //密码
    $mail->Password = $config['smtp_pwd'];
    //Set who the message is to be sent from
    $mail->setFrom($config['smtp_user']);
    //回复地址
    //$mail->addReplyTo('replyto@example.com', 'First Last');
    //接收邮件方
    if(is_array($to)){
        foreach ($to as $v){
            $mail->addAddress($v);
        }
    }else{
        $mail->addAddress($to);
    }

    $mail->isHTML(true);// send as HTML
    //标题
    $mail->Subject = $subject;
    //HTML内容转换
    $mail->msgHTML($content);
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    //添加附件
    //$mail->addAttachment('images/phpmailer_mini.png');
    //send the message, check for errors
    return $mail->send();
} 

/**
 * 检测是否能够发送短信
 * @param unknown $scene
 * @return multitype:number string
 */
function checkEnableSendSms($scene)
{
    
    $scenes = C('SEND_SCENE');
    $sceneItem = $scenes[$scene];
    if (!$sceneItem) {
        return array("status" => -1, "msg" => "场景参数'scene'错误!");
    }
    $key = $sceneItem[2];
    $sceneName = $sceneItem[0];
    $config = tpCache('sms');
    $smsEnable = $config[$key];

    if (!$smsEnable) {
        return array("status" => 0, "msg" => "['$sceneName']发送短信被关闭'");
    }
    //判断是否添加"注册模板"
    $size = M('sms_template')->where("send_scene", $scene)->count('tpl_id');
    if (!$size) {
        return array("status" => -1, "msg" => "请先添加['$sceneName']短信模板");
    }
    
    return array("status"=>1,"msg"=>"可以发送短信");
}

/**
 * 发送短信逻辑
 * @param unknown $scene
 */
function sendSms($scene, $sender, $params)
{

    $smsTemp = M('sms_template')->where("send_scene", $scene)->find();    //用户注册.
    $code = !empty($params['code']) ? $params['code'] : false;
    $consignee = !empty($params['consignee']) ? $params['consignee'] : false;
    $user_name =  !empty($params['user_name']) ? $params['user_name'] : false;
    $mobile = !empty($params['mobile']) ? $params['mobile'] : false;
    $order_sn = $params['order_sn'];
    $session_id = session_id();
    $config = tpCache('sms');
    $product = $config['sms_product'];

    $smsParams = array(
        1 => "{\"code\":\"$code\",\"product\":\"$product\"}",                                                                   //1. 用户注册
        2 => "{\"code\":\"$code\"}",                                                                                                          //2. 用户找回密码
        3 => "{\"consignee\":\"$consignee\",\"phone\":\"$mobile\"}",                                                       //3. 客户下单
        4 => "{\"order_sn\":\"$order_sn\"}",                                                                                               //4. 客户支付
        5 => "{\"user_name\":\"$user_name\",\"order_sn\":\"$order_sn\",\"consignee\":\"$consignee\"}",  //5.商家发货
        6 => "{\"user_name\":\"$user_name\",\"code\":\"$code\"}"                                                            //6. 修改手机号码
    );
    
    $smsParam = $smsParams[$scene];
    //提取发送短信内容
    $scenes = C('SEND_SCENE');
    $msg = $scenes[$scene][1];
    $params_arr = json_decode($smsParam);
    foreach ($params_arr as $k => $v) {
        $msg = str_replace('${' . $k . '}', $v, $msg);
    }
    
    //发送记录存储数据库
    $log_id = M('sms_log')->insertGetId(array('mobile' => $sender, 'code' => $code, 'add_time' => time(), 'session_id' => $session_id, 'status' => 0, 'scene' => $scene, 'msg' => $msg));
    $msg = $msg. ' 如果非本人操作请您忽略【三品車】';
    $resp = realSendSMS($sender, $msg);
    // $resp = realSendSMS($sender, $smsTemp['sms_sign'], $smsParam, $smsTemp['sms_tpl_code']);
    if ($resp['status'] == 1) {
        M('sms_log')->where(array('id' => $log_id))->save(array('status' => 1)); //修改发送状态为成功
    }else{
        M('sms_log')->where(array('id' => $log_id))->update(array('error_msg'=>$resp['msg'])); //发送失败, 将发送失败信息保存数据库
    }
    return $resp;
}
 function sendAllMsg($num,$tpl_value,$code,$tpl_id)
{
    $session_id = session_id();
   //发送记录存储数据库
  $log_id = M('sms_log')->insertGetId(array('mobile' => $num, 'code' => $code, 'add_time' => time(), 'status' => 0,  'msg' => $tpl_value,'session_id'=>$session_id,'tpl_id'=>$tpl_id));
   $resp = realSendSMS($num, $tpl_value,$tpl_id);

    if ($resp['status'] == 1) {
        M('sms_log')->where(array('id' => $log_id))->save(array('status' => 1)); //修改发送状态为成功
    }else{
        M('sms_log')->where(array('id' => $log_id))->update(array('error_msg'=>$resp['msg'])); 
    }
    return $resp;
}


// function realSendSMS1($mobile, $msg){

//     $sContent = $msg;

//     $phone =  $mobile;       
    
//             $body = array(
//                 'action' => 'send',
//                 'userid' => '',
//                 'account' => 'AB00042',
//                 'password' => 'Zhangming1750@',
//                 'mobile' => $phone,
//                 'extno' => '',
//                 'content' => $sContent,
//                 'sendtime' => ''
//             );
//             $ch = curl_init();
//             curl_setopt($ch, CURLOPT_URL, "https://dx.ipyy.net/sms.aspx");
//             curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
//             curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//             curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//             curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//             $result = curl_exec($ch);
//             curl_close($ch);
            
//             $result = simplexml_load_string($result);
//             $sMsg = (string)$result->message;
//             $returnstatus = (string)$result->returnstatus;
          
//             if ($returnstatus=='Success') {
//                return array('status' => 1, 'msg' => $sMsg);
//             }else{
//                  return array('status' => 0, 'msg' => $sMsg);
//             }
  
// }

function realSendSMS($mobile, $tpl_value,$tpl_id)
{
     $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
  
     $smsConf = array(
      'key'   => '5dd79c3568b54fc8f2538144d1bfbe1e', //您申请的APPKEY
      'mobile'    => $mobile, //接受短信的用户手机号码
      'tpl_id'    => $tpl_id, //您申请的短信模板ID，根据实际情况修改
      'tpl_value' =>urlencode($tpl_value) //您设置的模板变量，根据实际情况修改
    );
      $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $sendUrl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $smsConf);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);

   
    $result = json_decode($result,true);
    $res = array();
    switch ($result['error_code']) {
        case '0':
           $res['status']=1;
            break;
        case '205401':
           $res['status']=$result['error_code'];
           $res['msg'] = '错误的手机号码';
            break;
                
        case '205402':
           $res['status']=$result['error_code'];
           $res['msg'] = '错误的短信模板ID';
            break;
        case '205403':
           $res['status']=$result['error_code'];
           $res['msg'] = '网络错误,请重试';
            break;
        case '205404':
           $res['status']=$result['error_code'];
           $res['msg'] = '发送失败，具体原因请参考返回reason';
            break;
        case '205405':
           $res['status']=$result['error_code'];
           $res['msg'] = '号码异常/同一号码发送次数过于频繁';
            break;
        case '205406':
           $res['status']=$result['error_code'];
           $res['msg'] = '不被支持的模板';
            break;                
        default:
            # code...
            break;
    }
     return $res;
    
}


/**
 * 查询快递
 * @param $postcom  快递公司编码
 * @param $getNu  快递单号
 * @return array  物流跟踪信息数组
 */
function queryExpress($postcom , $getNu) {
/*    $url = "http://wap.kuaidi100.com/wap_result.jsp?rand=".time()."&id={$postcom}&fromWeb=null&postid={$getNu}";
    //$resp = httpRequest($url,'GET');
    $resp = file_get_contents($url);
    if (empty($resp)) {
        return array('status'=>0, 'message'=>'物流公司网络异常，请稍后查询');
    }
    preg_match_all('/\\<p\\>&middot;(.*)\\<\\/p\\>/U', $resp, $arr);
    if (!isset($arr[1])) {
        return array( 'status'=>0, 'message'=>'查询失败，参数有误' );
    }else{
        foreach ($arr[1] as $key => $value) {
            $a = array();
            $a = explode('<br /> ', $value);
            $data[$key]['time'] = $a[0];
            $data[$key]['context'] = $a[1];
        }
        return array( 'status'=>1, 'message'=>'1','data'=> array_reverse($data));
    }*/
    $url = "https://m.kuaidi100.com/query?type=".$postcom."&postid=".$getNu."&id=1&valicode=&temp=0.49738534969422676";
    $resp = httpRequest($url,"GET");
    return json_decode($resp,true);
}

/**
 * 获取某个商品分类的 儿子 孙子  重子重孙 的 id
 * @param type $cat_id
 */
function getCatGrandson ($cat_id)
{
    $GLOBALS['catGrandson'] = array();
    $GLOBALS['category_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['catGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['category_id_arr'] = M('GoodsCategory')->cache(true,TPSHOP_CACHE_TIME)->getField('id,parent_id');
    // 先把所有儿子找出来
    $son_id_arr = M('GoodsCategory')->where("parent_id", $cat_id)->cache(true,TPSHOP_CACHE_TIME)->getField('id',true);
    foreach($son_id_arr as $k => $v)
    {
        getCatGrandson2($v);
    }
    return $GLOBALS['catGrandson'];
}

/**
 * 获取某个文章分类的 儿子 孙子  重子重孙 的 id
 * @param type $cat_id
 */
function getArticleCatGrandson ($cat_id)
{
    $GLOBALS['ArticleCatGrandson'] = array();
    $GLOBALS['cat_id_arr'] = array();
    // 先把自己的id 保存起来
    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
    // 把整张表找出来
    $GLOBALS['cat_id_arr'] = M('ArticleCat')->getField('cat_id,parent_id');
    // 先把所有儿子找出来
    $son_id_arr = M('ArticleCat')->where("parent_id", $cat_id)->getField('cat_id',true);
    foreach($son_id_arr as $k => $v)
    {
        getArticleCatGrandson2($v);
    }
    return $GLOBALS['ArticleCatGrandson'];
}

/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getCatGrandson2($cat_id)
{
    $GLOBALS['catGrandson'][] = $cat_id;
    foreach($GLOBALS['category_id_arr'] as $k => $v)
    {
        // 找到孙子
        if($v == $cat_id)
        {
            getCatGrandson2($k); // 继续找孙子
        }
    }
}


/**
 * 递归调用找到 重子重孙
 * @param type $cat_id
 */
function getArticleCatGrandson2($cat_id)
{
    $GLOBALS['ArticleCatGrandson'][] = $cat_id;
    foreach($GLOBALS['cat_id_arr'] as $k => $v)
    {
        // 找到孙子
        if($v == $cat_id)
        {
            getArticleCatGrandson2($k); // 继续找孙子
        }
    }
}

/**
 * 查看某个用户购物车中商品的数量
 * @param type $user_id
 * @param type $session_id
 * @return type 购买数量
 */
function cart_goods_num($user_id = 0,$session_id = '')
{
//    $where = " session_id = '$session_id' ";
//    $user_id && $where .= " or user_id = $user_id ";
    // 查找购物车数量
//    $cart_count =  M('Cart')->where($where)->sum('goods_num');
    $cart_count = Db::name('cart')->where(function ($query) use ($user_id, $session_id) {
        $query->where('session_id', $session_id);
        if ($user_id) {
            $query->whereOr('user_id', $user_id);
        }
    })->sum('goods_num');
    $cart_count = $cart_count ? $cart_count : 0;
    return $cart_count;
}

/**
 * 获取商品库存
 * @param type $goods_id 商品id
 * @param type $key  库存 key
 */
function getGoodNum($goods_id,$key)
{
    if(!empty($key))
        return M("SpecGoodsPrice")->where(['goods_id' => $goods_id, 'key' => $key])->getField('store_count');
    else
        return  M("Goods")->where("goods_id", $goods_id)->getField('store_count');
}
 
/**
 * 获取缓存或者更新缓存
 * @param string $config_key 缓存文件名称
 * @param array $data 缓存数据  array('k1'=>'v1','k2'=>'v3')
 * @return array or string or bool
 */
function tpCache($config_key,$data = array()){
    $param = explode('.', $config_key);
    if(empty($data)){
        //如$config_key=shop_info则获取网站信息数组
        //如$config_key=shop_info.logo则获取网站logo字符串
        $config = F($param[0],'',TEMP_PATH);//直接获取缓存文件
        if(empty($config)){
            //缓存文件不存在就读取数据库
            $res = D('config')->where("inc_type",$param[0])->select();
            if($res){
                foreach($res as $k=>$val){
                    $config[$val['name']] = $val['value'];
                }
                F($param[0],$config,TEMP_PATH);
            }
        }
        if(count($param)>1){
            return $config[$param[1]];
        }else{
            return $config;
        }
    }else{
        //更新缓存
        $result =  D('config')->where("inc_type", $param[0])->select();
        if($result){
            foreach($result as $val){
                $temp[$val['name']] = $val['value'];
            }
            foreach ($data as $k=>$v){
                $newArr = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0]);
                if(!isset($temp[$k])){
                    M('config')->add($newArr);//新key数据插入数据库
                }else{
                    if($v!=$temp[$k])
                        M('config')->where("name", $k)->save($newArr);//缓存key存在且值有变更新此项
                }
            }
            //更新后的数据库记录
            $newRes = D('config')->where("inc_type", $param[0])->select();
            foreach ($newRes as $rs){
                $newData[$rs['name']] = $rs['value'];
            }
        }else{
            foreach($data as $k=>$v){
                $newArr[] = array('name'=>$k,'value'=>trim($v),'inc_type'=>$param[0]);
            }
            M('config')->insertAll($newArr);
            $newData = $data;
        }
        return F($param[0],$newData,TEMP_PATH);
    }
}

/**
 * 记录帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $user_money     可用余额变动
 * @param   int     $pay_points     消费积分变动
 * @param   string  $desc    变动说明
 * @param   float   distribut_money 分佣金额
 * @return  bool
 */
function accountLog($user_id, $user_money = 0,$pay_points = 0, $desc = '',$distribut_money = 0){
    /* 插入帐户变动记录 */
    $account_log = array(
        'user_id'       => $user_id,
        'user_money'    => $user_money,
        'pay_points'    => $pay_points,
        'change_time'   => time(),
        'desc'   => $desc,
    );
    /* 更新用户信息 */
//    $sql = "UPDATE __PREFIX__users SET user_money = user_money + $user_money," .
//        " pay_points = pay_points + $pay_points, distribut_money = distribut_money + $distribut_money WHERE user_id = $user_id";
    $update_data = array(
        'user_money'        => ['exp','user_money+'.$user_money],
        'pay_points'        => ['exp','pay_points+'.$pay_points],
        'distribut_money'   => ['exp','distribut_money+'.$distribut_money],
    );
    if(($user_money+$pay_points+$distribut_money) == 0)
        return false;   
    $update = Db::name('users')->where('user_id',$user_id)->update($update_data);
    if($update){
        M('account_log')->add($account_log);
        return true;
    }else{
        return false;
    }
}

/**
 * 订单操作日志
 * 参数示例
 * @param type $order_id  订单id
 * @param type $action_note 操作备注
 * @param type $status_desc 操作状态  提交订单, 付款成功, 取消, 等待收货, 完成
 * @param type $user_id  用户id 默认为管理员
 * @return boolean
 */
function logOrder($order_id,$action_note,$status_desc,$user_id = 0)
{
    $status_desc_arr = array('提交订单', '付款成功', '取消', '等待收货', '完成','退货');
    // if(!in_array($status_desc, $status_desc_arr))
    // return false;

    $order = M('order')->where("order_id", $order_id)->find();
    $action_info = array(
        'order_id'        =>$order_id,
        'action_user'     =>$user_id,
        'order_status'    =>$order['order_status'],
        'shipping_status' =>$order['shipping_status'],
        'pay_status'      =>$order['pay_status'],
        'action_note'     => $action_note,
        'status_desc'     =>$status_desc, //''
        'log_time'        =>time(),
    );
    return M('order_action')->add($action_info);
}

/*
 * 获取地区列表
 */
function get_region_list(){
    //获取地址列表 缓存读取
    if(!S('region_list')){
        $region_list = M('region')->select();
        $region_list = convert_arr_key($region_list,'id');        
        S('region_list',$region_list);
    }

    return $region_list ? $region_list : S('region_list');
}
/*
 * 获取用户地址列表
 */
function get_user_address_list($user_id){
    $lists = M('user_address')->where(array('user_id'=>$user_id))->select();
    return $lists;
}

/*
 * 获取指定地址信息
 */
function get_user_address_info($user_id,$address_id){
    $data = M('user_address')->where(array('user_id'=>$user_id,'address_id'=>$address_id))->find();
    return $data;
}
/*
 * 获取用户默认收货地址
 */
function get_user_default_address($user_id){
    $data = M('user_address')->where(array('user_id'=>$user_id,'is_default'=>1))->find();
    return $data;
}
/**
 * 获取订单状态的 中文描述名称
 * @param type $order_id  订单id
 * @param type $order     订单数组
 * @return string
 */
function orderStatusDesc($order_id = 0, $order = array())
{
    if(empty($order))
        $order = M('Order')->where("order_id", $order_id)->find();

    // 货到付款
    if($order['pay_code'] == 'cod')
    {
        if(in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0)
            return 'WAITSEND'; //'待发货',
    }
    else // 非货到付款
    {
        if($order['pay_status'] == 0 && $order['order_status'] == 0)
            return 'WAITPAY'; //'待支付',
        if($order['pay_status'] == 1 &&  in_array($order['order_status'],array(0,1)) && $order['shipping_status'] != 1)
            return 'WAITSEND'; //'待发货',
    }
    if(($order['shipping_status'] == 1) && ($order['order_status'] == 1))
        return 'WAITRECEIVE'; //'待收货',
    if($order['order_status'] == 2)
        return 'WAITCCOMMENT'; //'待评价',
    if($order['order_status'] == 3)
        return 'CANCEL'; //'已取消',
    if($order['order_status'] == 4)
        return 'FINISH'; //'已完成',
    if($order['order_status'] == 5)
        return 'CANCELLED'; //'已作废',
    return 'OTHER';
}

/**
 * 获取订单状态的 显示按钮
 * @param type $order_id  订单id
 * @param type $order     订单数组
 * @return array()
 */
function orderBtn($order_id = 0, $order = array())
{
    if(empty($order))
        $order = M('Order')->where("order_id", $order_id)->find();
    /**
     *  订单用户端显示按钮
    去支付     AND pay_status=0 AND order_status=0 AND pay_code ! ="cod"
    取消按钮  AND pay_status=0 AND shipping_status=0 AND order_status=0
    确认收货  AND shipping_status=1 AND order_status=0
    评价      AND order_status=1
    查看物流  if(!empty(物流单号))
     */
    $btn_arr = array(
        'pay_btn' => 0, // 去支付按钮
        'cancel_btn' => 0, // 取消按钮
        'receive_btn' => 0, // 确认收货
        'comment_btn' => 0, // 评价按钮
        'shipping_btn' => 0, // 查看物流
        'return_btn' => 0, // 退货按钮 (联系客服)
    );


    // 货到付款
    if($order['pay_code'] == 'cod')
    {
        if(($order['order_status']==0 || $order['order_status']==1) && $order['shipping_status'] == 0) // 待发货
        {
            $btn_arr['cancel_btn'] = 1; // 取消按钮 (联系客服)
        }
        if($order['shipping_status'] == 1 && $order['order_status'] == 1) //待收货
        {
            $btn_arr['receive_btn'] = 1;  // 确认收货
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }       
    }
    // 非货到付款
    else
    {
        if($order['pay_status'] == 0 && $order['order_status'] == 0) // 待支付
        {
            $btn_arr['pay_btn'] = 1; // 去支付按钮
            $btn_arr['cancel_btn'] = 1; // 取消按钮
        }
        if($order['pay_status'] == 1 && in_array($order['order_status'],array(0,1)) && $order['shipping_status'] == 0) // 待发货
        {
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
        if($order['pay_status'] == 1 && $order['order_status'] == 1  && $order['shipping_status'] == 1) //待收货
        {
            $btn_arr['receive_btn'] = 1;  // 确认收货
            $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
        }
    }
    if($order['order_status'] == 2)
    {
        $btn_arr['comment_btn'] = 1;  // 评价按钮
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    if($order['shipping_status'] != 0)
    {
        $btn_arr['shipping_btn'] = 1; // 查看物流
    }
    if($order['shipping_status'] == 2 && $order['order_status'] == 1) // 部分发货
    {            
        $btn_arr['return_btn'] = 1; // 退货按钮 (联系客服)
    }
    
    return $btn_arr;
}

/**
 * 给订单数组添加属性  包括按钮显示属性 和 订单状态显示属性
 * @param type $order
 */
function set_btn_order_status($order)
{
    $order_status_arr = C('ORDER_STATUS_DESC');
    $order['order_status_code'] = $order_status_code = orderStatusDesc(0, $order); // 订单状态显示给用户看的
    $order['order_status_desc'] = $order_status_arr[$order_status_code];
    $orderBtnArr = orderBtn(0, $order);
    return array_merge($order,$orderBtnArr); // 订单该显示的按钮
}


/**
 * 支付完成修改订单
 * @param $order_sn 订单号
 * @param array $ext 额外参数
 * @return bool|void
 */
function update_pay_status($order_sn,$ext=array(),$share=array())
{


    if(stripos($order_sn,'recharge') !== false){
        //用户在线充值
        $count = M('recharge')->where(['order_sn'=>$order_sn,'pay_status'=>0])->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
        if($count == 0) return false;
        $order = M('recharge')->where("order_sn", $order_sn)->find();
        M('recharge')->where("order_sn",$order_sn)->save(array('pay_status'=>1,'pay_time'=>time()));
        accountLog($order['user_id'],$order['account'],0,'会员在线充值');
    }elseif (stripos($order_sn,'partnerP') !== false) {
       //申请成为合伙人
        $count = M('PartnerPay')->where(['order_sn'=>$order_sn,'pay_status'=>0])->count();
         if($count == 0) return false;
            $old =  M('PartnerPay')->where("order_sn",$order_sn)->find();
            $parent_id = $old['parent_id'];
             share_money($parent_id,$old['account'],$old['order_id'],1,'邀请新人返佣',$old['user_id']);
            M('PartnerPay')->where("order_sn",$order_sn)->save(array('pay_status'=>1,'pay_time'=>time()));
            M('Partner')->where(array('partner_id'=>$old['apply_id']))->save(array('partner_status'=>1,'pay_time'=>time(),'end_time'=>strtotime("+1 year"),'day'=>date('d')));
            M('Partner')->where(array('user_id'=>$parent_id,'partner_status'=>1))->setInc('child_num');
            M('Users')->where(array('user_id'=>$old['user_id']))->save(array('level'=>7));//更新会员等级

         // M('PartnerPay')->where("order_sn",$order_sn)->save(array('pay_status'=>1,'pay_time'=>time()));
         // $old =  M('PartnerPay')->where("order_sn",$order_sn)->find();
         // M('Partner')->where(array('partner_id'=>$old['apply_id']))->save(array('partner_status'=>1,'pay_time'=>time(),'end_time'=>strtotime("+1 year"),'day'=>date('d')));
         
        
    } else{
        // 如果这笔订单已经处理过了
        $count = M('order')->where("order_sn = :order_sn and pay_status = 0 OR pay_status = 2")->bind(['order_sn'=>$order_sn])->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
        if($count == 0) return false;
        // 找出对应的订单
        $order = M('order')->where("order_sn",$order_sn)->find();
        //预售订单
        if ($order['order_prom_type'] == 4) {
            // 预付款支付 有订金支付 修改支付状态  部分支付
            if($order['total_amount'] != $order['order_amount'] && $order['pay_status'] == 0){
                //支付订金
                M('order')->where("order_sn", $order_sn)->save(array('order_sn'=> date('YmdHis').mt_rand(1000,9999) ,'pay_status' => 2, 'pay_time' => time(),'paid_money'=>$order['order_amount']));
            }else{
                //全额支付 无订金支付 支付尾款
                M('order')->where("order_sn", $order_sn)->save(array('pay_status' => 1, 'pay_time' => time()));
            }
            $orderGoodsArr = M('OrderGoods')->where(array('order_id'=>$order['order_id']))->find();
            M('goods_activity')->where(array('act_id'=>$order['order_prom_id']))->setInc('act_count',$orderGoodsArr['goods_num']);

        } else {
            // 修改支付状态  已支付
            M('order')->where("order_sn", $order_sn)->save(array('pay_status'=>1,'pay_time'=>time()));
        }
        goods_sn_share($order_sn,$share,$order['order_id']);
        buysucess_template($order);//支付成功通知
        // 减少对应商品的库存
        minus_stock($order['order_id']);
        // 给他升级, 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
        update_user_level($order['user_id']);
        // 记录订单操作日志
        if(array_key_exists('admin_id',$ext)){
            logOrder($order['order_id'],$ext['note'],'付款成功',$ext['admin_id']);
        }else{
            logOrder($order['order_id'],'订单付款成功','付款成功',$order['user_id']);
        }
        //分销设置
        M('rebate_log')->where("order_id" ,$order['order_id'])->save(array('status'=>1));
        // 成为分销商条件
        $distribut_condition = tpCache('distribut.condition');
        if($distribut_condition == 1)  // 购买商品付款才可以成为分销商
            M('users')->where("user_id", $order['user_id'])->save(array('is_distribut'=>1));
        
        //用户支付, 发送短信给商家
        $res = checkEnableSendSms("4");
        if(!$res || $res['status'] !=1) return ;
            
        $sender = tpCache("shop_info.mobile");
        if(empty($sender))return;
        $params = array('order_sn'=>$order_sn);
        sendSms("4", $sender, $params);
    }
   

}

//给每个订单的商品添加商品码，以及判断是否有分销的商品
 function goods_sn_share($order_sn,$share_arr=array(),$order_id)
{


    $order = M('order')->where(array('order_sn'=>$order_sn))->find();
 
    if($order['pay_status']!=1) return false; 
    $order_goods = M('OrderGoods')->where(array('order_id'=>$order['order_id']))->select();
    foreach ($order_goods as $key => $value) {
        if (empty($value['goods_osn'])) {
            $goods_osn = rand_str(7);
            while ( M('OrderGoods')->where(array('goods_osn'=>$goods_osn))->find()) {
                $goods_osn = rand_str(7);
            }
            M('OrderGoods')->where(array('rec_id'=>$value['rec_id']))->save(array('goods_osn'=>$goods_osn));
           
        }
    }


// var_dump($_SESSION['share_uid']&&$_SESSION['share_gid']&&M('OrderGoods')->where(array('goods_id'=>$_SESSION['share_gid'],'order_id'=>$order['order_id']))->find());exit;


    if ($share_arr['share_uid']&&$share_arr['share_gid']&&M('OrderGoods')->where(array('goods_id'=>$share_arr['share_gid'],'order_id'=>$order['order_id']))->find()) {
        $where = array();
        $where['user_id'] = $share_arr['share_uid'];
        $where['goods_id'] = $share_arr['share_gid'];
        $share = M('Wxshare')->where($where)->find();
       if(!is_array($share)) return false;
        M('OrderGoods')->where(array('order_id'=>$order_id,'goods_id'=>$share_arr['share_gid']))->save(array('share_id'=>$share['id']));
  
       $path = '/home/ubuntu/website/shop/application/home/controller/test.txt';

      $myfile = fopen($path, "w") or die("Unable to open file!");
       fwrite($myfile, $order_id);
     
        $data = array();
        if (empty($share['buy_ids'])) {
            
            $data['buy_ids'] = $share_arr['user_id'];
        }else{
           $data['buy_ids'] =$share['buy_ids'].'|'.$share_arr['user_id']; 
        }
         $data['buy_num'] = $share['buy_num']+1;
        $result = M('Wxshare')->where($where)->save($data);
        

         $buy['share_id'] = $share['id'];
          $buy['uid'] = $share_arr['user_id'];
          $scanData = M('buy_share')->where($buy)->find();
           if($scanData){
            $num = $scanData['buy_num']+1;
            M('buy_share')->where(array('id'=>$scanData['id']))->save(array('buy_num'=>$num));
           
          }else{
            $scan['add_time'] = time();
             M('buy_share')->add($buy);
          } 
      

    }


}

function rand_str($codeLen=6)
{
   $str="abcdefghijkmnpqrstuvwxyz0123456789";
   $rand="";

    for($i=0; $i<$codeLen-1; $i++){
        $rand .= $str[mt_rand(0, strlen($str)-1)];  //如：随机数为30  则：$str[30]
    }
   return $rand;
}

    /**
     * 订单确认收货
     * @param $id   订单id
     */
    function confirm_order($id,$user_id = 0){
        $where['order_id'] = $id;
        if($user_id){
            $where['user_id'] = $user_id;
        }
        $order = M('order')->where($where)->find();
        if($order['order_status'] != 1)
            return array('status'=>-1,'msg'=>'该订单不能收货确认');
        
        $data['order_status'] = 2; // 已收货        
        $data['pay_status'] = 1; // 已付款        
        $data['confirm_time'] = time(); // 收货确认时间
        if($order['pay_code'] == 'cod'){
            $data['pay_time'] = time();
        }
        $row = M('order')->where(array('order_id'=>$id))->save($data);
        if(!$row)        
            return array('status'=>-3,'msg'=>'操作失败');
        
        order_give($order);// 调用送礼物方法, 给下单这个人赠送相应的礼物
        
        //分销设置
        M('rebate_log')->where("order_id", $id)->save(array('status'=>2,'confirm'=>time()));
               
        return array('status'=>1,'msg'=>'操作成功');
    }

/**
 * 给订单送券送积分 送东西
 */
function order_give($order)
{
    $order_goods = M('order_goods')->where("order_id",$order['order_id'])->cache(true)->select();
    //查找购买商品送优惠券活动
    foreach ($order_goods as $val)
    {
        if($val['prom_type'] == 3)
        {
            $prom = M('prom_goods')->where('type=3 and id=:id')->bind(['id'=>$val['prom_id']])->find();
            if($prom){
                $coupon = M('coupon')->where("id", $prom['expression'])->find();//查找优惠券模板
                if($coupon && $coupon['createnum']>0){                                                          
                    $remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
                    if($remain > 0)                                            
                    {
                        $data = array('cid'=>$coupon['id'],'type'=>$coupon['type'],'uid'=>$order['user_id'],'send_time'=>time());
                        M('coupon_list')->add($data);       
                        M('Coupon')->where("id", $coupon['id'])->setInc('send_num'); // 优惠券领取数量加一
                    }
                }
            }
         }
    }
    
    //查找订单满额送优惠券活动
    $pay_time = $order['pay_time'];
    $prom_order_where = [
        'type'          => ['gt', 1],
        'end_time'      => ['gt', $pay_time],
        'start_time'    => ['lt', $pay_time],
        'money'         => ['elt', $order['order_amount']]
    ];
    $prom = M('prom_order')
        ->where($prom_order_where)
        ->order('money desc')
        ->find();
    if($prom){
        if($prom['type']==3){
            $coupon = M('coupon')->where("id",$prom['expression'])->find();//查找优惠券模板
            if($coupon){
                if($coupon['createnum']>0){
                    $remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
                    if($remain > 0)
                    {
                       $data = array('cid'=>$coupon['id'],'type'=>$coupon['type'],'uid'=>$order['user_id'],'send_time'=>time());
                       M('coupon_list')->add($data);           
                       M('Coupon')->where("id",$coupon['id'])->setInc('send_num'); // 优惠券领取数量加一
                    }               
                }
            }
        }else if($prom['type']==2){
            accountLog($order['user_id'], 0 , $prom['expression'] ,"订单活动赠送积分");
        }
    }
    $points = M('order_goods')->where("order_id", $order['order_id'])->sum("give_integral * goods_num");
    $points && accountLog($order['user_id'], 0,$points,"下单赠送积分");
}


/**
 * 查看商品是否有活动
 * @param goods_id 商品ID
 */

function get_goods_promotion($goods_id,$user_id=0){
    $now = time();
    $goods = M('goods')->where("goods_id", $goods_id)->find();
    $where = [
        'end_time' => ['gt', $now],
        'start_time' => ['lt', $now],
        'id' => $goods['prom_id'],
    ];
    
    $prom['price'] = $goods['shop_price'];
    $prom['prom_type'] = $goods['prom_type'];
    $prom['prom_id'] = $goods['prom_id'];
    $prom['is_end'] = 0;
    
    if($goods['prom_type'] == 1){//抢购
        $prominfo = M('flash_sale')->where($where)->find();
        if(!empty($prominfo)){
            if($prominfo['goods_num'] == $prominfo['buy_num']){
                $prom['is_end'] = 2;//已售馨
            }else{
                //核查用户购买数量
                $where = "user_id = :user_id and order_status!=3 and  add_time>".$prominfo['start_time']." and add_time<".$prominfo['end_time'];
                $order_id_arr = M('order')->where($where)->bind(['user_id'=>$user_id])->getField('order_id',true);
                if($order_id_arr){
                    $goods_num = M('order_goods')->where("prom_id={$goods['prom_id']} and prom_type={$goods['prom_type']} and order_id in (".implode(',', $order_id_arr).")")->sum('goods_num');
                    if($goods_num < $prominfo['buy_limit']){
                        $prom['price'] = $prominfo['price'];
                    }
                }else{
                    $prom['price'] = $prominfo['price'];
                }
            }               
        }
    }
    
    if($goods['prom_type']==2){//团购
        $prominfo = M('group_buy')->where($where)->find();
        if(!empty($prominfo)){          
            if($prominfo['goods_num'] == $prominfo['buy_num']){
                $prom['is_end'] = 2;//已售馨
            }else{
                $prom['price'] = $prominfo['price'];
            }               
        }
    }
    if($goods['prom_type'] == 3){//优惠促销
        $parse_type = array('0'=>'直接打折','1'=>'减价优惠','2'=>'固定金额出售','3'=>'买就赠优惠券','4'=>'买M件送N件');
        $prominfo = M('prom_goods')->where($where)->find();
        if(!empty($prominfo)){
            if($prominfo['type'] == 0){
                $prom['price'] = $goods['shop_price']*$prominfo['expression']/100;//打折优惠
            }elseif($prominfo['type'] == 1){
                $prom['price'] = $goods['shop_price']-$prominfo['expression'];//减价优惠
            }elseif($prominfo['type']==2){
                $prom['price'] = $prominfo['expression'];//固定金额优惠
            }
        }
    }
    
    if(!empty($prominfo)){
        $prom['start_time'] = $prominfo['start_time'];
        $prom['end_time'] = $prominfo['end_time'];
    }else{
        $prom['prom_type'] = $prom['prom_id'] = 0 ;//活动已过期
        $prom['is_end'] = 1;//已结束
    }
    
    if($prom['prom_id'] == 0){
        M('goods')->where("goods_id", $goods_id)->save($prom);
    }
    return $prom;
}

/**
 * 查看订单是否满足条件参加活动
 * @param order_amount 订单应付金额
 */
function get_order_promotion($order_amount){
    $parse_type = array('0'=>'满额打折','1'=>'满额优惠金额','2'=>'满额送倍数积分','3'=>'满额送优惠券','4'=>'满额免运费');
    $now = time();
    $prom = M('prom_order')->where("type<2 and end_time>$now and start_time<$now and money<=$order_amount")->order('money desc')->find();
    $res = array('order_amount'=>$order_amount,'order_prom_id'=>0,'order_prom_amount'=>0);
    if($prom){
        if($prom['type'] == 0){
            $res['order_amount']  = round($order_amount*$prom['expression']/100,2);//满额打折
            $res['order_prom_amount'] = $order_amount - $res['order_amount'] ;
            $res['order_prom_id'] = $prom['id'];
        }elseif($prom['type'] == 1){
            $res['order_amount'] = $order_amount- $prom['expression'];//满额优惠金额
            $res['order_prom_amount'] = $prom['expression'];
            $res['order_prom_id'] = $prom['id'];
        }
    }
    return $res;        
}

//该优惠券能否使用
function canUse($user_id = 0,$coupon_id=0)
{
        $coupon = array();

    //判断能否使用优惠券
    if (is_numeric($coupon_id)&&!empty($coupon_id)) 
    {
       $coupon = M('CouponList')->where(array('user_del'=>0,'uid'=>$user_id,'order_id'=>0,'id'=>$coupon_id))->find();
       $coupon['can_use']=1;
       if ($coupon['use_end_time']<time()){
          $coupon['can_use']=0;
          $coupon['no_use'] = '优惠券已过期';
        }

        //判断是否指定商家
        if ($coupon['is_appoint']==1) 
        {
           $count = M('UcouponShop')->alias('us')->join('__GUSEB__ gb','gb.shop_id=us.shop_id')->join('__CART__ c','c.goods_id=gb.goods_id')->join('__GOODS__ g','g.goods_id=c.goods_id')->where(array('us.clid'=>$coupon['id'],'c.user_id'=>$this->user_id,'c.selected'=>1,'g.is_appoint'=>1))->count();

          if ($count<=0) 
          {
            $coupon['can_use']=0;
          }
        }
        //判断是否是产品卷
        if ($coupon['type']==1) 
        {
          $goods_id = $coupon['goods_id'];
          $cartGoodcount = M('cart')->where(array('goods_id'=>$goods_id,'user_id'=>$user_id,'selected'=>1))->count();
          if ($cartGoodcount<=0) 
          {
            $coupon['can_use']=0;

          }
          // //判断产品券是否指定商家
          // if ($coupon['is_appoint']==1) 
          // {

          //   $goods = M('Goods')->where(array('goods_id'=>$goods_id,'is_on_sale'=>1,'del_status'=>0))->find();
          //   if ($goods['is_appoint']==1) 
          //   {
          //     $count = M('UcouponShop')->alias('us')->join('__GUSEB__ gb','gb.shop_id=us.shop_id')->where(array('us.clid'=>$coupon['id'],'gb.goods_id'=>$goods_id))->count();
          //     if ($count<=0) 
          //     {
          //      $coupon['can_use']=0;
          //     }
          //   }else{
          //     $coupon['can_use']=0;
          //   }
          // }

        }else{
          //代金券情况下
          
          $tags = M('UcouponTag')->alias('ut')->join('__TAG__ t','ut.tag_id=t.id')->where(array('ut.clid'=>$coupon['id']))->field('ut.tag_id,t.name')->select();
            $ctags = count($tags);
            $coupon['tag_count'] = $ctags;
          if ($ctags>0) 
          {
           $gcount = M('UCouponTag')->alias('ut')->join('__GOODS_TAG__ gt','gt.tag_id=ut.tag_id')->join('__CART__ c','c.goods_id=gt.goods_id')->where(array('ut.clid'=>$coupon['id'],'c.user_id'=>$user_id,'c.selected'=>1))->count();
           if ($gcount<=0) 
           {
             $coupon['can_use']=0;
           
           }
          }
        }

    }
    return $coupon;
}

function new_calculate_price($user_id = 0, $order_goods,$coupon_id=0){

    $allMoney=0;//总价
    $order_amount=0;
    $allNum = 0;//总件数
    $coupon_price =0;
    $coupon = canUse($user_id,$coupon_id);
    
    if ($coupon['can_use']==1) {
       if ($coupon['type']==1) {
          $coupon_price = M('Goods')->where(array('goods_id'=>$coupon['goods_id']))->getField('shop_price');
       }else{
         $coupon_price = $coupon['money'];
       }
    }
    foreach ($order_goods as $k => $val) {
        $allMoney+=$val['goods_price']*$val['goods_num'];
        $allNum  += $val['goods_num'];
    }

    $order_amount = $allMoney-$coupon_price;


     $result = array(
        'total_amount' => $allMoney, // 商品总价
        'order_amount' => $order_amount, // 应付金额
        'shipping_price' => 0, // 物流费
        'goods_price' => $allMoney, // 商品总价
        'cut_fee' =>0, // 共节约多少钱
        'anum' => $allNum, // 商品总共数量
        'integral_money' => 0,  // 积分抵消金额
        'user_money' => 0, // 使用余额
        'coupon_price' => $coupon_price,// 优惠券抵消金额
        'order_goods' => $order_goods, // 商品列表 多加几个字段原样返回
    );
     
    return array('status' => 1, 'msg' => "计算价钱成功", 'result' => $result);
}

/**
 * 计算订单金额
 * @param type $user_id  用户id
 * @param type $order_goods  购买的商品
 * @param type $shipping  物流code
 * @param type $shipping_price 物流费用, 如果传递了物流费用 就不在计算物流费
 * @param type $province  省份
 * @param type $city 城市
 * @param type $district 县
 * @param type $pay_points 积分
 * @param type $user_money 余额
 * @param type $coupon_id  优惠券
 * @param type $couponCode  优惠码
 */

function calculate_price($user_id = 0, $order_goods, $shipping_code = '', $shipping_price = 0, $province = 0, $city = 0, $district = 0, $pay_points = 0, $user_money = 0, $coupon_id = 0, $couponCode = '')
{
    $cartLogic = new app\home\logic\CartLogic();
    $user = M('users')->where("user_id", $user_id)->find();// 找出这个用户

    if (empty($order_goods)){
        return array('status' => -9, 'msg' => '商品列表不能为空', 'result' => '');
    }

    $goods_id_arr = get_arr_column($order_goods, 'goods_id');
    $goods_arr = M('goods')->where("goods_id in(" . implode(',', $goods_id_arr) . ")")->cache(true,TPSHOP_CACHE_TIME)->getField('goods_id,weight,market_price,is_free_shipping'); // 商品id 和重量对应的键值对
    foreach ($order_goods as $key => $val) {
        // 如果传递过来的商品列表没有定义会员价
        if (!array_key_exists('member_goods_price', $val)) {
            $user['discount'] = $user['discount'] ? $user['discount'] : 1; // 会员折扣 不能为 0
            $order_goods[$key]['member_goods_price'] = $val['member_goods_price'] = $val['goods_price'] * $user['discount'];
        }
        //如果商品不是包邮的
        if ($goods_arr[$val['goods_id']]['is_free_shipping'] == 0)
            $goods_weight += $goods_arr[$val['goods_id']]['weight'] * $val['goods_num']; //累积商品重量 每种商品的重量 * 数量

        $order_goods[$key]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];    // 小计
        $order_goods[$key]['store_count'] = getGoodNum($val['goods_id'], $val['spec_key']); // 最多可购买的库存数量
        if ($order_goods[$key]['store_count'] <= 0)
            return array('status' => -10, 'msg' => $order_goods[$key]['goods_name'] . "库存不足,请重新下单", 'result' => '');

        $goods_price += $order_goods[$key]['goods_fee']; // 商品总价
        $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price']; // 共节约
        $anum += $val['goods_num']; // 购买数量
    }
    // 优惠券处理操作
    $coupon_price = 0;
    if ($coupon_id && $user_id) {
        $coupon_price = $cartLogic->getCouponMoney($user_id, $coupon_id, 1); // 下拉框方式选择优惠券
    }
    if ($couponCode && $user_id) {
        $coupon_result = $cartLogic->getCouponMoneyByCode($couponCode, $goods_price); // 根据 优惠券 号码获取的优惠券
        if ($coupon_result['status'] < 0)
            return $coupon_result;
        $coupon_price = $coupon_result['result'];
    }
    // 处理物流
    if ($shipping_price == 0) {
        $freight_free = tpCache('shopping.freight_free'); // 全场满多少免运费
        if ($freight_free > 0 && $goods_price >= $freight_free) {
            $shipping_price = 0;
        } else {
            $shipping_price = $cartLogic->cart_freight2($shipping_code, $province, $city, $district, $goods_weight);
        }
    }
    
    $use_percent_point = tpCache('shopping.point_use_percent');     //最大使用限制: 最大使用积分比例, 例如: 为50时, 未50% , 那么积分支付抵扣金额不能超过应付金额的50%
    if($pay_points > 0 && $use_percent_point == 0){
        return array('status' => -1, 'msg' => "该笔订单不能使用积分", 'result' => '积分'); // 返回结果状态
    }
    
    if ($pay_points && ($pay_points > $user['pay_points']))
        return array('status' => -5, 'msg' => "你的账户可用积分为:" . $user['pay_points'], 'result' => ''); // 返回结果状态
    if ($user_money && ($user_money > $user['user_money']))
        return array('status' => -6, 'msg' => "你的账户可用余额为:" . $user['user_money'], 'result' => ''); // 返回结果状态

    $order_amount = $goods_price + $shipping_price - $coupon_price; // 应付金额 = 商品价格 + 物流费 - 优惠券

    $user_money = ($user_money > $order_amount) ? $order_amount : $user_money;  // 余额支付原理等同于积分
    $order_amount = $order_amount - $user_money; //  余额支付抵应付金额
    
    /*判断能否使用积分
     1..积分低于point_min_limit时,不可使用
     2.在不使用积分的情况下, 计算商品应付金额
     3.原则上, 积分支付不能超过商品应付金额的50%, 该值可在平台设置
     @{ */
    $point_rate = tpCache('shopping.point_rate'); //兑换比例: 如果拥有的积分小于该值, 不可使用
    $min_use_limit_point = tpCache('shopping.point_min_limit'); //最低使用额度: 如果拥有的积分小于该值, 不可使用
    
    
    if ($min_use_limit_point > 0 && $pay_points > 0 && $pay_points < $min_use_limit_point) {
        return array('status' => -1, 'msg' => "您使用的积分必须大于{$min_use_limit_point}才可以使用", 'result' => ''); // 返回结果状态
    }
    // 计算该笔订单最多使用多少积分
    //$limit = $order_amount * ($use_percent_point / 100) * $point_rate;
    //if(($use_percent_point !=100 ) && $pay_points > $limit) {
    //   return array('status'=>-1,'msg'=>"该笔订单, 您使用的积分不能大于{$limit}",'result'=>'积分'); // 返回结果状态
    //}
    // }
     
    $pay_points = ($pay_points / tpCache('shopping.point_rate')); // 积分支付 100 积分等于 1块钱
    $pay_points = ($pay_points > $order_amount) ? $order_amount : $pay_points; // 假设应付 1块钱 而用户输入了 200 积分 2块钱, 那么就让 $pay_points = 1块钱 等同于强制让用户输入1块钱
    $order_amount = $order_amount - $pay_points; //  积分抵消应付金额
  
    $total_amount = $goods_price + $shipping_price;
    //订单总价  应付金额  物流费  商品总价 节约金额 共多少件商品 积分  余额  优惠券
    $result = array(
        'total_amount' => $total_amount, // 商品总价
        'order_amount' => $order_amount, // 应付金额
        'shipping_price' => $shipping_price, // 物流费
        'goods_price' => $goods_price, // 商品总价
        'cut_fee' => $cut_fee, // 共节约多少钱
        'anum' => $anum, // 商品总共数量
        'integral_money' => $pay_points,  // 积分抵消金额
        'user_money' => $user_money, // 使用余额
        'coupon_price' => $coupon_price,// 优惠券抵消金额
        'order_goods' => $order_goods, // 商品列表 多加几个字段原样返回
    );
    return array('status' => 1, 'msg' => "计算价钱成功", 'result' => $result); // 返回结果状态
}

/**
 * 获取商品一二三级分类
 * @return type
 */
function get_goods_category_tree(){
    $arr = $result = array();
    // $_SESSION['shop_type']==1?$cat_list = M('goods_category')->where("is_show = 1 and shop_id=".$_SESSION['shop_id'])->order('sort_order')->cache(true)->select():
    $cat_list = M('goods_category')->where("is_show = 1 and shop_id=0 and level=1")->order('sort_order')->cache(true)->select();//所有分类
    
    foreach ($cat_list as $val){
        if($val['level'] == 2){
            $arr[$val['parent_id']][] = $val;
        }
        if($val['level'] == 3){
            $crr[$val['parent_id']][] = $val;
        }
        if($val['level'] == 1){
            $tree[] = $val;
        }
    }

    foreach ($arr as $k=>$v){
        foreach ($v as $kk=>$vv){
            $arr[$k][$kk]['sub_menu'] = empty($crr[$vv['id']]) ? array() : $crr[$vv['id']];
        }
    }

    if(is_array($tree)){
        foreach ($tree as $val){
        $val['tmenu'] = empty($arr[$val['id']]) ? array() : $arr[$val['id']];
        $result[$val['id']] = $val;
    }
    }

    
    return $result;
}

/**
 * 写入静态页面缓存
 */
function write_html_cache($html){
    
    return;
    $html_cache_arr = C('HTML_CACHE_ARR');
    $request = think\Request::instance();
    $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
    $m_c_a_str = strtolower($m_c_a_str);
    //exit('write_html_cache写入缓存<br/>');
    foreach($html_cache_arr as $key=>$val)
    {
        $val['mca'] = strtolower($val['mca']);
        if($val['mca'] != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
            continue;
        
        if(!is_dir(RUNTIME_PATH.'html'))
                mkdir(RUNTIME_PATH.'html');
        $filename =  RUNTIME_PATH.'html'.DIRECTORY_SEPARATOR.$m_c_a_str;
        // 组合参数  
        if(isset($val['p']))
        {                    
            foreach($val['p'] as $k=>$v)        
                $filename.='_'.$_GET[$v];
        } 
        $filename.= '.html';        
        file_put_contents($filename, $html);
    }    
}

/**
 * 读取静态页面缓存
 */
function read_html_cache(){    
    $html_cache_arr = C('HTML_CACHE_ARR');
    $request = think\Request::instance();
    $m_c_a_str = $request->module().'_'.$request->controller().'_'.$request->action(); // 模块_控制器_方法
    $m_c_a_str = strtolower($m_c_a_str);
    //exit('read_html_cache读取缓存<br/>');
    foreach($html_cache_arr as $key=>$val)
    {
        $val['mca'] = strtolower($val['mca']);
        if($val['mca'] != $m_c_a_str) //不是当前 模块 控制器 方法 直接跳过
            continue;
          
        $filename =  RUNTIME_PATH.'html'.DIRECTORY_SEPARATOR.$m_c_a_str;
        // 组合参数        
        if(isset($val['p']))
        {                    
            foreach($val['p'] as $k=>$v)        
                $filename.='_'.$_GET[$v];
        } 
        $filename.= '.html';
        if(file_exists($filename))
        {
            echo file_get_contents($filename);           
            exit();           
        }
    }    
}


/** 
* @desc 根据两点间的经纬度计算距离 
* @param float $lat 纬度值 
* @param float $lng 经度值 
*/
function getDistance($lat1, $lng1, $lat2, $lng2) 
{ 
$earthRadius = 6367000; //approximate radius of earth in meters 
 
$lat1 = ($lat1 * pi() ) / 180; 
$lng1 = ($lng1 * pi() ) / 180; 
 
$lat2 = ($lat2 * pi() ) / 180; 
$lng2 = ($lng2 * pi() ) / 180; 

 
$calcLongitude = $lng2 - $lng1; 
$calcLatitude = $lat2 - $lat1; 
$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2); 
$stepTwo = 2 * asin(min(1, sqrt($stepOne))); 
$calculatedDistance = $earthRadius * $stepTwo; 
 
return round($calculatedDistance); 
} 


 function wanrmb($scale="10000",$value)
{
    $value = $value/$scale;
    $value = sprintf("%.2f",$value);
    return $value;
}

function calcu_goods_price($value=0)
{
    $val = $value/10000;
   if ($val>=1) {
      $result = $value/10000;
      $result = sprintf("%.2f",substr(sprintf("%.3f", $result), 0, -1));
      return $result.'万';
   }else{
    return $value/1;
   }
}



 function shop_name($shop_id)
{
    if(empty($shop_id)) return '三品車';
   $shop_name = M('admin')->where(array('admin_id'=>$shop_id))->find();
   return $shop_name['shop_name'];
}

function shop_logo($shop_id)
{
   if(empty($shop_id)) return '/template/new/static/images/sanpinche100.jpg';
   $shop_logo = M('admin')->where(array('admin_id'=>$shop_id))->find();
   return $shop_logo['shop_logo'];
}


function getProgramme($type,$goods_id){
    if (empty($goods_id)||!is_numeric($goods_id)) return false;
     $data = M('GoodsProgramme')->where(array('goods_id'=>$goods_id))->field('down_payments,first_year')->order('down_payments asc')->group('down_payments,first_year')->select();
     if ($type==1) {
         return calcu_goods_price($data[0]['first_year']);
     }elseif ($type==2) {
         return calcu_goods_price($data[0]['down_payments']);
     }else{
        return $data[0];
     }
     
}


function getPartnerConfig($user_id,$case_id=0){
    $where = array();
    $where['del_status']=0;
    if (is_numeric($user_id)&&$user_id!=0) {
       $where['user_id'] = $user_id;
        $config = M('PartnerConfig')->where($where)->order('id desc')->find();
       
    }
    
    if (!is_array($config)&&is_numeric($case_id)&&$case_id!=0) {
   
        $where['user_id'] = 0;
        $where['use_status']=1;
        $where['id']=$case_id;
        $config = M('PartnerConfig')->where($where)->find();
        unset($where['id']);
     }
     if (!is_array($config)) {
      
        $where['user_id'] = 0;
        $where['use_status']=1;
        $where['is_seed']=0;
        $config = M('PartnerConfig')->where($where)->order('id desc')->find();
     }
  
     return $config;
}

//分利润user_id本人id，allmoney所有的利润 $new_id新人id  $profit_id收益来源id，$profit_type收益类型 $desc描述

function share_money($user_id,$allMoney,$profit_id,$profit_type,$desc="",$new_id=0)
{
    
    $result = array();
    if (empty($user_id)||!is_numeric($user_id)) {
        $result['status']=0;
        $result['msg']   ='用户id有误';
    }
    $config = getPartnerConfig($user_id);
    $first  = $allMoney*$config['first_rate']/100.00;
    $second = $allMoney*$config['second_rate']/100.00;//本人的收益
    $third  = $allMoney*$config['third_rate']/100.00;
    $new    = $allMoney*$config['new_rate']/100.00;
    //新人
    $new_info = M('Partner')->where(array('del_status'=>0,'user_id'=>$new_id))->order('partner_id desc')->find();

    if (is_array($new_info)){
     
        $new_all = $new_info['all_commision']+$new;
        $new_surplus = $new_info['surplus_commision']+$new;
        M('Partner')->where(array('partner_id'=>$new_info['partner_id']))->save(array('all_commision'=>$new_all,'surplus_commision'=>$new_surplus));
        add_money_log($new_id,$new,'commision',1,$profit_id,$profit_type,'新人加盟福利');
    }

    //自己
    $self_info = M('Partner')->where(array('user_id'=>$user_id))->find();
    
    if (!is_array($self_info)) return false;
    $child_num = $self_info['child_num'];
    if (!empty($child_num)&&is_numeric($child_num)) {
        $average = $third/$child_num; 
        //分类给三级
       $third_partner =  M('Partner')->where(array('del_status'=>0,'parent_id'=>$user_id,'partner_status'=>1))->select();
       if(is_array($third_partner)){
        foreach ($third_partner as $key => $value) {
            $all_commision = $value['all_commision']+$average;
            $surplus_commision = $value['surplus_commision']+$average;
           M('Partner')->where(array('partner_id'=>$value['partner_id']))->save(array('all_commision'=>$all_commision,'surplus_commision'=>$surplus_commision));
          
           add_money_log($value['user_id'],$average,'commision',1,$profit_id,$profit_type,$desc);
        }
       }
    }


    //本人
    $self_all = $self_info['all_commision']+$second;
    $self_surplus = $self_info['surplus_commision']+$second;
    M('Partner')->where(array('partner_id'=>$self_info['partner_id']))->save(array('all_commision'=>$self_all,'surplus_commision'=>$self_surplus));
     
    add_money_log($user_id,$second,'commision',1,$profit_id,$profit_type,$desc);
   
    //父类
    $parent_info = M('Partner')->where(array('del_status'=>0,'user_id'=>$self_info['parent_id'],'partner_status'=>1))->find();
        if (is_array($parent_info)){
         
        $parent_all = $parent_info['all_commision']+$first;
        $parent_surplus = $parent_info['surplus_commision']+$first;
        M('Partner')->where(array('partner_id'=>$parent_info['partner_id']))->save(array('all_commision'=>$parent_all,'surplus_commision'=>$parent_surplus));
        add_money_log($self_info['parent_id'],$first,'commision',1,$profit_id,$profit_type,$desc);
    }
  

}

//log_type:money账户余额 commision佣金去向point积分
function add_money_log($user_id,$number=0,$log_type,$type,$profit_id=0,$profit_type=0,$desc="")
{
   $log = array();
   $log['user_id']     = $user_id;
   $log['profit_type'] = $profit_type;
   $log['type']        = $type;
   $log['desc']        = $desc;
   $log['log_type']    = $log_type;
   $log['profit_id']   = $profit_id;
   $log['number']      = $number;
   $log['change_time']   = time();
   M('MoneyLog')->add($log);
}

//生成二维码
function make_qr_code($url,$dirname,$imgname,$size = 4, $margin = 3.5)
{
    vendor('phpqrcode.phpqrcode'); 
    error_reporting(E_ERROR); 
    $url = urldecode($url);
    $path = './public/upload/'.$dirname.'/';


    if (!is_dir($path)) {

       $val= mkdir($path,0777,true);
       
        chmod($path, 0777);
       
    }
   
    $path = $path.$imgname;
  
    \QRcode::png($url,$path,$size,$margin);

    return '/public/upload/'.$dirname.'/'.$imgname;
}


function getNonceStr($length = 32) 
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {  
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
        } 
        return $str;
    }




//send_mode_msg发送模版消息
 function send_template_msg($template_id,$openid,$needdata)
 {
     $wx_user = M('wx_user')->field('appid,appsecret')->find();
     $jssdk = new \app\mobile\logic\Jssdk($wx_user['appid'],$wx_user['appsecret']);
     $result =$jssdk->template_msg($template_id,$openid,$needdata);
     $data = array();
     $data['add_time'] = time();
     $data['template_id'] = $template_id;
     $data['msg'] = json_encode($needdata);
     $data['openid'] = $openid;
     $data['errcode'] = $result['errcode'];
     $res = M('TemplateLog')->add($data);

 }



 //template_id="rO6QFPPvNLVdcscdYrKZG6r4XSF25qDSkr_3I0PM-mo"购买成功通知
 function buysucess_template($order)
 {
      $order_id = $order['order_id'];
      $order_goods = M('OrderGoods')->where(array('order_id'=>$order_id,'check_status'=>0))->find();
      $openid = M('Users')->where(array('user_id'=>$order['user_id']))->getField('openid');
      $goodsinfo = M('Goods')->where(array('goods_id'=>$order_goods['goods_id']))->field('is_ctime,is_appoint')->find();
      $data = array();
      $data['url'] = 'http://www.sanpinche.com/Mobile/User/order_detail/id/'.$order_id;
      $data['topcolor'] = '#ff0066';
      $first['value'] = '购买成功啦！';
      $first['color'] = '#ff9000';
      $data['data']['first'] = $first;

      $keyword1['value'] = $order_goods['goods_name'];
      // $keyword1['color'] = '#22DD48';
      $data['data']['keyword1'] = $keyword1;

      $keyword2['value'] = number_format($order_goods['goods_num']*$order_goods['goods_price'],2);
      // $keyword2['color'] = '#ff9933';
      $data['data']['keyword2'] = $keyword2;
      if ($order_goods['is_ctime']==1) {
          $ctime = M('Ogtime')->where(array('rec_id'=>$order_goods['rec_id']))->find();
      $keyword3['value'] = date('Y-m-d',$ctime['start_time']).'至'.date('Y-m-d',$ctime['end_time']);
      }else{
        $keyword3['value'] = '不限';
      }
     
      // $keyword3['color'] = '#ff0066';
      $data['data']['keyword3'] = $keyword3;

      //获取可用商家
      if ($order_goods['is_appoint']==1) {
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
            $keyword4['value'] = $shop_names;
         }else{
            $keyword4['value'] = '不限';
         }
      }else{
        $keyword4['value'] = '不限';
      }
      
      // $keyword4['color'] = '#ff0066';
      $data['data']['keyword4'] = $keyword4;

      $keyword5['value'] = $order_goods['goods_osn'];
      // $keyword4['color'] = '#ff0066';
      $data['data']['keyword5'] = $keyword5;

      $remark['value'] = '消费时请向商家出示验券码。部分参加活动的特价产品仅可在活动期间在指定商家使用。如有疑问请拨打客服电话：400-877-8063';
      // $remark['color'] = '#2783c9';
      $data['data']['remark'] = $remark;
      send_template_msg('rO6QFPPvNLVdcscdYrKZG6r4XSF25qDSkr_3I0PM-mo',$openid,$data);
 }

  //template_id="gQi2zYKKSi8VF1iTNIMMxiWwFRgcoDTKqWeBBTjJZsI"验卷成功通知
 function consume_template($order_goods,$shop_name)
 {
      // $order_goods = M('OrderGoods')->where(array('order_id'=>$order_id,'check_status'=>0))->find();
        $user_id = M('Order')->where(array('order_id'=>$order_goods['order_id']))->getField('user_id');
        $openid = M('Users')->where(array('user_id'=>$user_id))->getField('openid');
      $data = array();
      $keyword1['value'] = '产品名称';
      $data['data']['productType'] = $keyword1;

      $keyword2['value'] = $order_goods['goods_name'];
      $data['data']['name'] = $keyword2;
      
      $keyword3['value'] = date('Y-m-d H:i:s',time());
      // $keyword3['color'] = '#ff0066';
      $data['data']['time'] = $keyword3;
      $keyword4['value'] = '商家';
      $data['data']['accountType'] = $keyword4;
      $keyword5['value'] = $shop_name;
      $data['data']['account'] = $keyword5;

      // $remark['value'] = '消费商家：'.$shop_name;
      // // $remark['color'] = '#2783c9';
      // $data['data']['remark'] = $remark;
      send_template_msg('gQi2zYKKSi8VF1iTNIMMxiWwFRgcoDTKqWeBBTjJZsI',$openid,$data);
 }

