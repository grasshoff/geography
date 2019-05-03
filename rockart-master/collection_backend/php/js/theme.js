// JavaScript Document
	var ua = navigator.userAgent;
	var clickevent = (ua.match(/iPad/i)) ? "touchstart" : "click"; // fix click event for iPad
$( document ).ready(function() {
	var divWidth = $('.intro').width();
	if (  $('.intro h1').height() > 100 )  {
	 	$('.intro h1').css("font-size","33px");
 		$('.intro h1 a').css("font-size","33px");
	}else{
		$('.intro h1').css("font-size","48px");
 		$('.intro h1 a').css("font-size","48px");
	}
	
	// scroll according  entry in main content of collection to top 
	$('#collection_container > #collection_page > #collection_sidebar > .menu:not(.cite) a').click(function(e) {
		//prevent scrollbar from disappearing
		var lastElement = $("#collection_main").children().last();
		var height = lastElement.position().top + lastElement.height();
		$("#collection_page").height(height);
		
		// move according element to top
	    e.preventDefault();
	    var targetName = $(this).attr('href').substring(1);
	    var target = $("#collection_main a[name='"+targetName+"']").first().parent().find("h1");
	    var top = target.position().top;
	    $("#collection_main").animate({"margin-top": -top}, 500);
	});	
});

var img_src ='';


$('img.thmb').hover(function(e) {
      img_src= $(this).attr('src');
	  $(this).attr('src','/img/sci.png');
	  $(this).next('figcaption').css({'display':'none'});
	  $(this).css({'background':'url('+img_src+')'});
  }, function(e) {
	  	$(this).next('figcaption').css({'display':'block'});
		  $(this).attr('src',img_src);
  }
);

$(document).ready(function(){
	$(".dropbtn").click(function(e){
	    $("#menu-content").toggle();
	     e.stopPropagation();
	});

	$("#menu-content").click(function(e){
	    e.stopPropagation();
	    $("#menu-content").hide();
	});


	$(document).click(function(){
	    $("#menu-content").hide();
	});

	var headerImage = $(".title_img");
	if (headerImage.length > 0){
		var headerImageHeight = headerImage.height();
		var headerImageWidth = headerImage.width();
	
		var headerTitleContainer = $(".title_container");
		var headerTitleH1 = $(".title_container h1");
		var headerTitleH1Span = $(".title_container h1 span");
	
		var fontSize = headerTitleH1Span.css("font-size").replace("px", "");
		while (true){
			
			bb = headerTitleH1Span[0].getBoundingClientRect();
			
			if (
				(bb.height < headerImageHeight) && 
				(bb.width < headerImageWidth) ) {
				break;
			}

			fontSize = fontSize - 5;
	
			if (fontSize < 10){
				break;
			}
	
			headerTitleH1Span.css("font-size", fontSize);
		}

		headerTitleH1.fadeTo(0,1);
	}
	
	//add perfect scrollbar to containers (eg. menu and metadata)
	$('.ps-container').each(function(){
		var thisContainer = this;
		var ps = new PerfectScrollbar(thisContainer);
	});
});