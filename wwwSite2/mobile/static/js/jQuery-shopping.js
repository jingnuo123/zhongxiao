function flyToCart(obj){
	var obj = $(obj).find(".flyobj");
	var target_top = $("#cart").offset().top;
	var target_left = $("#cart").offset().left+40;
	var position = obj.offset();
	var width = obj.width();
	var height = obj.height();
	var clone = obj.clone().insertAfter("#footer");//复制一个到飞行容器中
	//new_clone.;
	clone.css({position:"absolute","z-index":"99999",top:position.top,left:position.left,width:width,height:height}).animate(
		{top:target_top+70,left:target_left,width:"50px",height:"50px"},//飞到购物篮图标下方
		400,
		function(){clone.animate(
			{top:target_top+10,left:target_left+20,width:"10px",height:"10px"},//飞进购物篮
			200,
			function(){
				clone.fadeOut().remove();//消失并清空内容
			}
		)}
	);
	//购物篮内商品列表的滚动条到底部
	var cart_list = $(".cart_list");
	cart_list.scrollTop(100000);
}