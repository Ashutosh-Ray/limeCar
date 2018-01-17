<?php
namespace app\admin\validate;
use think\Validate;
class Goods extends Validate
{
    
    // 验证规则
    protected $rule = [
        
        ['brand_id','require','商品品牌必选'],
        ['goods_content','require','商品详情必填'],
        ['series_id','require','商品系列必选'],
        ['version_id','require','商品型号必选'],
        ['goods_name','require','商品名称必填'],
        ['cat_id', 'number|gt:0', '商品分类必须填写|商品分类必须选择'],
        ['goods_sn', 'unique:goods', '商品货号重复'], // 更多 内置规则 http://www.kancloud.cn/manual/thinkphp5/129356
        ['shop_price','regex:\d{1,10}(\.\d{1,2})?$','本店售价格式不对。'],
        ['shop_price','require','裸车价必填'],
        ['market_price','require','市场指导价价必填'],
        ['market_price','regex:\d{1,10}(\.\d{1,2})?$','市场价格式不对。'],
        ['weight','regex:\d{1,10}(\.\d{1,2})?$','重量格式不对。'],
        ['store_count','require','库存必填'],
        ['exchange_integral','checkExchangeIntegral','积分抵扣金额不能超过商品总额']
    ];
     
    
    /**
     * 检查积分兑换
     * @author dyr
     * @return bool
     */
    protected function checkExchangeIntegral($value, $rule)
    {
        $exchange_integral = $value;//I('exchange_integral', 0);
        $shop_price = I('shop_price', 0);
        $point_rate_value = tpCache('shopping.point_rate');
        $point_rate_value = empty($point_rate_value) ? 0 : $point_rate_value;
        if ($exchange_integral > ($shop_price * $point_rate_value)) {
            return '积分抵扣金额不能超过商品总额';
        } else {
            return true;
        }
    }    
}