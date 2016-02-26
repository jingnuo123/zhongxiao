$(function(){


/* menu list */
$('#menu-btn').click(function(event){
     $('.main').toggleClass('translate');
     event.stopPropagation();
     event.cancelBubble=true;
})


$('.main').click(function(){
     $('.main').removeClass('translate');
})

$('.menu-list').click(function(){
     $('.main').addClass('translate');
     event.stopPropagation();
     event.cancelBubble=true;
})
})