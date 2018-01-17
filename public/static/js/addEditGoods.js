//时间选择器插件
var start,over
 layui.use('laydate', function(){
   var laydate = layui.laydate;
   //执行一个laydate实例
   laydate.render({
     elem: '#startTime' //指定元素
     ,showBottom: false
     // ,done: function(value, date){
     //   start=value;
     //   return start
     // }
   });
   laydate.render({
     elem: '#overTime' //指定元素
     ,showBottom: false
   });
 });


 //ajax提交表单
 function sureSubmit() {
    var brand=$("select[name='brand_id']").find("option:selected").text();
    var series=$("select[name='series_id']").find("option:selected").text();
    var version=$("select[name='version_id']").find("option:selected").text();
    brand = $.trim(brand);
    series = $.trim(series);
    version = $.trim(version);
    var val = brand+series+version;
  
    $("input[name='goods_name']").val(val);
    ajaxSubmitForm('addEditGoodsForm','/index.php/admin/Goods/addEditGoods?is_ajax=1');
}


 // 物流设置相 关
  $(document).ready(function(){
     $(":checkbox[cka]").click(function(){
         var $cks = $(":checkbox[ck='"+$(this).attr("cka")+"']");
         if($(this).is(':checked')){
             $cks.each(function(){$(this).prop("checked",true);});
         }else{
             $cks.each(function(){$(this).removeAttr('checked');});
         }
     });
 });
 // 物流设置相 关
 function choosebox(o){
     var vt = $(o).is(':checked');
     if(vt){
         $('input[type=checkbox]').prop('checked',vt);
     }else{
         $('input[type=checkbox]').removeAttr('checked');
     }
 }


  // 上传商品图片成功回调函数
    function call_back(fileurl_tmp){
        $("#original_img").val(fileurl_tmp);
      $("#original_img2").attr('href', fileurl_tmp);
    }
 
    // 上传商品相册回调函数
    function call_back2(paths){
        
        var  last_div = $(".goods_xc:last").prop("outerHTML");  
        for (i=0;i<paths.length ;i++ )
        {                    
            $(".goods_xc:eq(0)").before(last_div);  // 插入一个 新图片
                $(".goods_xc:eq(0)").find('a:eq(0)').attr('href',paths[i]).attr('onclick','').attr('target', "_blank");// 修改他的链接地址
            $(".goods_xc:eq(0)").find('img').attr('src',paths[i]);// 修改他的图片路径
                $(".goods_xc:eq(0)").find('a:eq(1)').attr('onclick',"ClearPicArr2(this,'"+paths[i]+"')").text('删除');
            $(".goods_xc:eq(0)").find('input').val(paths[i]); // 设置隐藏域 要提交的值
        }          
    }



     // 上传之后删除组图input     
     //@access   public
     //@val      string  删除的图片input
     //
    function ClearPicArr2(obj,path)
    {
        $(obj).parent().remove();
      $.ajax({
                    type:'GET',
                    url:"/index.php/Admin/Uploadify/delupload",
                    data:{action:"del", filename:path},
                    success:function(){
                           $(obj).parent().remove(); // 删除完服务器的, 再删除 html上的图片        
                    }
    });
    // 删除数据库记录
      $.ajax({
                    type:'GET',
                    url:"/index.php/Admin/Goods/del_goods_images",
                    data:{filename:path},
                    success:function(){
                          //     
                    }
    });   
    }


    /** 以下 商品属性相关 js*/

// 属性输入框的加减事件
function addAttr(a)
{
  var attr = $(a).parent().parent().prop("outerHTML");  
  attr = attr.replace('addAttr','delAttr').replace('+','-');  
  $(a).parent().parent().after(attr);
}
// 属性输入框的加减事件
function delAttr(a)
{
   $(a).parent().parent().remove();
}
 

/** 以下 商品规格相关 js*/
$(document).ready(function(){ 
    // 商品模型切换时 ajax 调用  返回不同的属性输入框
    $("#spec_type").change(function(){        
        var goods_id = '{$goodsInfo.goods_id}';
        var spec_type = $(this).val();
            $.ajax({
                    type:'GET',
                    data:{goods_id:goods_id,spec_type:spec_type}, 
                    url:"{:U('admin/Goods/ajaxGetSpecSelect')}",
                    success:function(data){                            
                           $("#ajax_spec_data").html('')
                           $("#ajax_spec_data").append(data);
         ajaxGetSpecInput();  // 触发完  马上触发 规格输入框
                    }
            });           
            //商品类型切换时 ajax 调用  返回不同的属性输入框     
            $.ajax({
                 type:'GET',
                 data:{goods_id:goods_id,type_id:spec_type}, 
                 url:"/index.php/admin/Goods/ajaxGetAttrInput",
                 success:function(data){                            
                         $("#goods_attr_table tr:gt(0)").remove()
                         $("#goods_attr_table").append(data);
                 }        
           });
    });
  // 触发商品规格
  $("#spec_type").trigger('change'); 
  
    $("input[name='exchange_integral']").blur(function(){
        var shop_price = parseInt($("input[name='shop_price']").val());
        var exchange_integral = parseInt($(this).val());
        if (shop_price * 100 < exchange_integral) {
          
        }
    });
});

//得到商品分类
function get_category(id,next,select_id){
    var url = '/index.php?m=Home&c=api&a=get_category&parent_id='+ id;
    $.ajax({
        type : "GET",
        url  : url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(v) {
            if(v!=''){
             v = "<option value='0'>请选择商品分类</option>" + v;
            $('#'+next).empty().html(v);
            $('#'+next).show();
            (select_id > 0) && $('#'+next).val(select_id);//默认选中 
            }else{
               v = "<option value='0'>请选择商品分类</option>" + v;
              $('#'+next).empty().html(v);
            $('#'+next).show();
            (select_id > 0) && $('#'+next).val(select_id);//默认选中 
            }
           
        }
    });
}

function selectGoods(){
        var url = "/index.php/admin/Goods/search_goods.html";
        layer.open({
            type: 2,
            title: '选择商品',
            shadeClose: true,
            shade: 0.2,
            area: ['75%', '75%'],
            content: url,
        });
    }
function call_back(goods_id,goods_name,store_count,price){
        $('#goods_id').val(goods_id);
        $('#goods_name').val(goods_name);
        // $('#group_num').val(store_count);
        $('#goods_price').val(price);
        layer.closeAll('iframe');
    }
$(document).ready(function(){
        //插件切换列表
        $('.tab-base').find('.tab').click(function(){
            $('.tab-base').find('.tab').each(function(){
                $(this).removeClass('current');
            });
            $(this).addClass('current');
      var tab_index = $(this).data('index');      
      $(".tab_div_1, .tab_div_2, .tab_div_3, .tab_div_4").hide();     
      $(".tab_div_"+tab_index).show();
  });
    //获取属性列表 
});


//新车添加金融方案
function addProgramme() {
    var content = '<div> <label>首付款:</label><input type="text" name="down_payments[]" onKeyUp="this.value=this.value.replace(/[^\d.]/g,"")"><label>第一年月供:</label><input type="text" name="first_year[]" onKeyUp="this.value=this.value.replace(/[^\d.]/g,"")"><label>一年后残值:</label><input type="text" name="residual_value[]" onKeyUp="this.value=this.value.replace(/[^\d.]/g,"")"><label>尾款贷款期数:</label><input type="text" name="tail_periods[]" onKeyUp="this.value=this.value.replace(/[^\d.]/g,"")"><label>尾款月供:</label><input type="text" name="tail_money[]" onKeyUp="this.value=this.value.replace(/[^\d.]/g,"")"><a onclick="delProgramme(this)" class="ncap-btn"><i class="fa"></i>删除</a></div>';
    $('#programme').append(content);
}

 //新车删除金融方案
 function delProgramme(obj) {
     
    $(obj).parent().remove();
 }


 //二手車添加金融方案
function addProgrammeUsed() {
    var content = '<div> <label>首付款:</label><input type="text" name="down_payments[]" onKeyUp="this.value=this.value.replace(/[^\d.]/g,"")"><label>月供:</label><input type="text" name="first_year[]" onKeyUp="this.value=this.value.replace(/[^\d.]/g,"")"><label>还款期数:</label><input type="text" name="tail_periods[]" onKeyUp="this.value=this.value.replace(/[^\d.]/g,"")"><a onclick="delProgramme(this)" class="ncap-btn"><i class="fa"></i>删除</a></div>';
    $('#programme').append(content);
}

//二手車删除金融方案
function delUsedProgramme(obj) {
    
   $(obj).parent().remove();
}


function getbrandversion(type,id,pro) {
       $.ajax({
                    url:"/index.php/Admin/Goods/ajaxBrandSeriesVersion/type/"+type+'/'+pro+'/'+id,
                    dataType:'json',
                    type:'GET',
                    success:function(res) {
                       if (res.status==1) {
                        var html='<option value="">所有</option>';
                        $.each(res.data,function(index,item){
                           html+='<option value="'+item.id+'">'+item.name+'</option>' 

                        })
                        switch(type)
                                {
                                case 1:
                                 $('select[name="brand_id"]').html(html)
                                  break;
                                case 2:
                                  $('select[name="series_id"]').html(html)
                                  break;
                                case 3:
                                  $('select[name="version_id"]').html(html)
                                  break;  
                                default:
                                 
                                }
                       }
                    }
                })
    }
//添加品牌车系车型
function addBrand(type,brand_id,series_id,title) {
               var id=0;
               var pro='';
               var brand_id=0;
               var series_id=0;
                if (type==1) {id=0}
                if (type==2) {
                    var val = $('select[name="brand_id"]').val();
                    if (val=='') {
                         alert('请选择品牌')
                         return false;
                    }else{
                        id=val;
                        brand_id=val;
                        pro='brand_id';
                    }
                    
                }
                if (type==3) {
                    var val = $('select[name="series_id"]').val();
                    if (val=='') {
                         alert('请选择车系')
                          return false;
                    }else{
                        id=val;
                        series_id=val;
                        brand_id = $('select[name="brand_id"]').val();
                        pro='series_id';
                    }
                }
  
    var url = "/index.php/Admin/Goods/addBrandSeriesVersion/type/"+type+"/brand_id/"+brand_id+"/series_id/"+series_id;
        layer.open({
            type: 2,
            title: title,
            shadeClose: true,
            shade: 0.2,
            area: ['500px', '600px'],
            content: [url,'no'],
            cancel: function(){
              //点击上面的xx的时候
                getbrandversion(type,id,pro);
                
            },
            end:function() {
              //整个layer关闭的时候
               getbrandversion(type,id,pro);
           
            }
        });
}


//服务范围控制
$('.all').click(function(){
  if ($(this).attr("checked")=="checked") {
    $(this).parent().siblings().children('input').attr("checked","checked")
  }else{
    $(this).parent().siblings().children('input').removeAttr("checked")
  }
})
var oths=$('.all').parent().siblings().children('input')
oths.each(function(){
  $(this).click(function(){
    var chknum=$('.all').parent().siblings().children('input').size()
    var chk=0
    oths.each(function(){
      if ($(this).prop("checked")==true) {
        chk++;
      }
    })
    if (chk==chknum) {
      $('.all').prop("checked",true)
    }else{
      $('.all').prop("checked",false)
    }
  })
})


//会员控制
$('.cb-enable').click(function(){
  $(this).parent().parent().parent().next().show()
})
$('.cb-disable').click(function(){
  $(this).parent().parent().parent().next().hide()
})
$('.vip_all').click(function(){
  if ($(this).attr("checked")=="checked") {
    $(this).parent().siblings().children('input').attr("checked","checked")
  }else{
    $(this).parent().siblings().children('input').removeAttr("checked")
  }
})
var vipoths=$('.vip_all').parent().siblings().children('input')
vipoths.each(function(){
  $(this).click(function(){
    var chknum=$('.vip_all').parent().siblings().children('input').size()
    var chk=0
    vipoths.each(function(){
      if ($(this).prop("checked")==true) {
        chk++;
      }
    })
    if (chk==chknum) {
      $('.vip_all').prop("checked",true)
    }else{
      $('.vip_all').prop("checked",false)
    }
  })
})

//会员类型在带入数据的情况下载入后自行判断是否打开
$(document).ready(function(){
  if ($("#goods_category1").prop("checked")) {
    $("#goods_category1").parent().parent().parent().next().show()
  }

  if ($("#is_appoint1").prop("checked")) {
    $("#is_appoint1").parent().parent().parent().next().show()
  }
  if ($("#is_ctime1").prop("checked")) {
    $("#is_ctime1").parent().parent().parent().next().show()
  }
})    