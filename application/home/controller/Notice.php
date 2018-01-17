<?php
namespace app\home\controller; 
use think\Controller;
/**
* 
*/
class notice extends Controller
{
    

    public function index()
    {
      // $path = dirname(__FILE__)."/test.txt";
      // $myfile = fopen($path, "w") or die("Unable to open file!");
      // $txt = "Bill Gates\n";
      
         $str = file_get_contents('php://input');

         $str = urldecode($str);
          // fwrite($myfile, $str);
          // $str = 'orderNo=201707182037568623&extendParams={"share_gid":65,"share_uid":"2595","user_id":2589}';
          $data = explode('&',$str);
          $result = array();
          foreach ($data as $key => $value) {
              $explode = explode('=',$value);
              $k = $explode[0];
              $result[$k] = $explode[1];
          }
          // $share = json_decode($result['extendParams'],1);
          // update_pay_status($result['orderNo'],array(),$share);
          $post_sign = $result['sign'];
          unset($result['sign']);
          $sign = MakeSign($result,C('shunfuKey'));
          //验证签名
          if ($sign!=$post_sign) {
           exit;
          }
          if ($result['orderNo']) {
              //更新订单状态
              $share = json_decode($result['extendParams'],1);
              update_pay_status($result['orderNo'],array(),$share);
              M('Order')->where(array('order_sn'=>$result['orderNo']))->save(array('gwtradeno'=>$result['gwTradeNo']));
              
          }

 
    }



    /**
     * 商户审核成功后回调函数
     */
    public function merchantReturn()
    {
        $aPost['mchNo'] = I('post.mchNo');
        $aPost['timeStamp'] = I('post.timeStamp');
        $aPost['status'] = I('post.status');
        
        $aSfMerchantPayment = M('Sf_merchant_payment')->where(array('mchNo'=>I('post.mchNo')))->find();
        
        $sSign = MakeSign($aPost,$aSfMerchantPayment['mchKey']);
        file_put_contents('./public/abc.txt',json_encode($aPost).$sSign."##".I('post.sign'));
        
        if($sSign==I('post.sign'))
        {
            M('Sf_merchant_payment')->where(array('mchNo'=>I('post.mchNo')))->save(array('status'=>0));
            exit('success');
        }
    
    }
    
    /**
     * 一码支付回调函数
     *  payChannelTypeNo
        0501：支付宝-当面付-扫码
        0502：支付宝-当面付-条码
        0503：微信-扫码支付
        0504：微信-刷卡/条码支付
        0505：微信-公众号支付
     */
    public function unifyCodePayNotify()
    {
        
        
        /*  post data        {
         "timeStamp": "1502336558421",
         "elecInvoiceQRcode": "",
         "payChannelTypeNo": "0503",
         "buyerId": "oBsaFw3Lum1j2BkAkCdK8U2n5Urs",
         "sign": "d0cb753a184e5bc82a66bd1c121f1ac7",
         "amount": "0.01",
         "gwTradeNo": "2017081011423801027851233",
         "tradeNo3rd": "4003202001201708105454236125",
         "orderNo": "20170810100028696398",
         "goodsDesc": "在线买单",
         "buyerAccount": "",
         "goodsName": "一码支付",
         "mchNo": "SZSF001-0000009"
         }
         2989bb61b117d8ecc6b09d7aa9628a49##8d249aac7b962703fb97796a7a7f5bc0
         */
        
        
        $aSfMerchantPayment = M('Sf_merchant_payment')->where(array('mchNo'=>I('post.mchNo')))->find();
        
        $aInsert['orderNo'] = I('post.orderNo');
        $aInsert['gwTradeNo'] = I('post.gwTradeNo');
        $aInsert['tradeNo3rd'] = I('post.tradeNo3rd');
        $aInsert['amount'] = I('post.amount');
        $aInsert['buyerId'] = I('post.buyerId');
        $aInsert['buyerAccount'] = I('post.buyerAccount');
        $aInsert['goodsName'] = I('post.goodsName');
        $aInsert['goodsDesc'] = I('post.goodsDesc');
        $aInsert['mchNo'] = I('post.mchNo');
        $aInsert['payChannelTypeNo'] = I('post.payChannelTypeNo');
        $aInsert['elecInvoiceQRcode'] = I('post.elecInvoiceQRcode'); 
        $aInsert['timeStamp'] = I('post.timeStamp');
        
        
        $sSign = MakeSign($aInsert,$aSfMerchantPayment['mchKey']);
        

            unset($aInsert['timeStamp']);
            $aInsert['create_time'] = time();
            M('Sf_payment_recode')->add($aInsert);
            exit('success');
     
        
        
        
    }


    
}




