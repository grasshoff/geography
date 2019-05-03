var imgFileExtensions = [".jpg",".JPG",".png",".PNG"];
var BOX = "box";
var repPath="";
var thumbImgArr = new Array();
var numThumbImgs = 0;

//*****************************************************
// showAllObjLevel1
//*****************************************************
function showAllObjLevel1(dataList,arg,repositoryPathForSci ){
 	repPath=repositoryPathForSci;
 //console.log(dataList, arg,"-------\n");
var htmlLi = '';
$('#path').empty();
 $('.navHorizontalList ul').remove();

 $('.alphabet').remove();
 
 for (var i in dataList){
	 
	htmlLi=singleLevelHtmlObjectsList(htmlLi, dataList[i] , true);	
 }
  
  
	$('#single').append(htmlLi);	
	
}	



//*******************************************************************
// 		singleLevelHtmlObjectsList()
//******************************************************************
function singleLevelHtmlObjectsList(html, itemsList ){

	var allDesignData = ''+itemsList["design"]+'_data?startkey="'+arg+'"';
	var designData= getKeyAndValue(viewURL+allDesignData);
	var thumbIDString=  designData[0]._key[arg][BOX];
	
	console.log("singleLevelHtmlObjectsList  designData",designData);
	
	if (itemsList['subtitle'] ===null) itemsList['subtitle'] ="";
 	for(var item in itemsList) {	
		// console.log("item =",item,"itemsList =",itemsList[item]);

		 if (item === reqObjID ){
				if (rep==='true'){
					html += '<li class="whiteKofferLang indent"><div class="kofferWrap"><div class="txtOverImage nowrap">'+itemsList['title']+'<br>  <span class="bigFont">'+itemsList['subtitle']+'<br>'+itemsList['Obj'][0]+'</span> <br>';	
				}
				else{
					html += '<li class="kofferLang"><div class="kofferWrap"><div class="txtOverImage">'+itemsList['title']+'<br> <span class="bigFont">'+itemsList['subtitle']+'</span> <br>';	
				}
			html += '</div>';  
				
	 			for ( j=0; j<itemsList[item].length; j++){
					var thumb= imageSrc(arg, designData,itemsList[item][j]);					 
					if(j === 0)	{
						
						if (rep==='true'){
							 
							var n = itemsList[item][j].indexOf('/');
							var  sciFile= itemsList[item][j].substring(0, n);
		 
							var sciPath= repPath+sciFile+"/"+sciFile+".sCi";
							var sciObjId = objectIdFromSciURL(sciPath);
							var img= getImage(itemsList[item][j]);
							
							html+=  '<div  class="thumbFirstRow"><a href="/' + projectShort + '/single/' + sciObjId + '/' + j + '" target="_blank"><figure><img src="'+thumb+'" class="thmb"><figcaption >'+thumbIDString+'<br>'+itemsList[item][j]+'</figcaption></figure></a> </div>';		
						}else{
							html+=  '<div  class="thumbFirstRow"><a href="/' + projectArg + '/object/' + itemsList[item][j] + '"><figure><img src="'+thumb+'" class="thmb"><figcaption >'+thumbIDString+'<br>'+itemsList[item][j]+'</figcaption></figure></a> </div>';				
						}
						
					}
					else {
					
					html+=  ' <div class="thumbs"><a href="/' + projectArg + '/object/' + itemsList[item][j] + '"><figure><img src="'+thumb+'" class="thmb"><figcaption >'+thumbIDString+'<br>'+itemsList[item][j]+'</figcaption> </figure></a> </div>';	
						 
					}
				}	
				
				
		}
		html += '</div>';
		html += '</div>  </li>';
	}
	return html;
}
//**********************************************************
// 		getImage()
//**********************************************************
function getImage(path){
	console.log("Found this path", path);
		var n = path.lastIndexOf("/");
		var img= path.substring(n + 1);
		return (img);
	}
//*******************************************************************
// 			subHtmlObjectsList()
//******************************************************************
function subHtmlObjectsList(html,firstLine, itemsList){
	$('.liHeader').empty();
	//////console.log(filterBy);
	if ( (itemsList['title'] !== 'Unknown') && (filterBy !== reqObjID) ){
		var htmlHeader='<span class="liText">> '+itemsList['title']+'</span>';
		$('.liHeader').append(htmlHeader);
	}
	var allDesignData = ''+itemsList["design"]+'_data?startkey="'+arg+'"';
	var designData= getKeyAndValue(viewURL+allDesignData);	
	var thumbIDString=  designData[0]._key[arg][BOX];
		console.log("subHtmlObjectsList designData", designData);
	
	for(var item in itemsList) {
	 if (item === reqObjID ){
	 
		// go through object Items
		for ( j=0; j<itemsList[item].length; j++){
			 
			 
			if (itemsList['subtitle'] === null){
				if (rep==='true'){
					if (firstLine===""){
						html += '<li class="whiteKkofferLang"><div class="kofferWrap"><div class="txtOverImage">'+itemsList[item][j]+'<br>';
					}else{
						html += '<li class="whiteKkofferLang"><div class="kofferWrap"><div class="txtOverImage nowrap"><span>'+firstLine+'</span> <br> '+itemsList[item][j]+'<br>';
						}
				}
				else{
					if (firstLine===""){
					html += '<li class="kofferLang"><div class="kofferWrap"><div class="txtOverImage">'+itemsList[item][j]+'<br>';
					}else{
						html += '<li class="kofferLang"><div class="kofferWrap"><div class="txtOverImage"><span>'+firstLine+'</span> <br> '+itemsList[item][j]+'<br>';
						}
				}
			}else{
				var dispSubtitle = itemsList[item][j];
				if (typeof dispSubtitle == 'string' || dispSubtitle instanceof String) {
					var dispSubtitleSlash = dispSubtitle.indexOf('/');
					if (dispSubtitleSlash > 0 && dispSubtitleSlash < dispSubtitle.length - 1) {
						dispSubtitle = dispSubtitle.substr(0, dispSubtitleSlash);
					}
				}

				if (rep==='true'){
					if (firstLine===""){
						html += '<li class="whiteKofferLang"><div class="kofferWrap"><div class="txtOverImage nowrap"><span class="bigFont">'+itemsList['subtitle']+'</span> <br>'+dispSubtitle+'<br>';
					}else{
						html += '<li class="whiteKofferLang"><div class="kofferWrap"><div class="txtOverImage nowrap"><span>'+firstLine+'</span> <br> <span class="bigFont">'+itemsList['subtitle']+'</span> <br>'+dispSubtitle+'<br>';
						}
				}
				else{
					if (firstLine===""){
						html += '<li class="kofferLang"><div class="kofferWrap"><div class="txtOverImage"> <span class="bigFont">'+itemsList['subtitle']+'</span> <br>'+dispSubtitle+'<br>';
					}else{
						html += '<li class="kofferLang"><div class="kofferWrap"><div class="txtOverImage"><span>'+firstLine+'</span> <br> <span class="bigFont">'+itemsList['subtitle']+'</span> <br>'+dispSubtitle+'<br>';
						}
				}
				}
			html += '</div>';  
			var thumb= imageSrc(arg, designData,itemsList[item][j]);
			var thumbPlaceholder = "img/thumb_placeholder.jpg";
			var thumbId = "obj_thumb_" + itemsList[item][j];
			if (thumb) {
				thumbImgArr[numThumbImgs] = new Image();
				thumbImgArr[numThumbImgs].name = thumbId;
				thumbImgArr[numThumbImgs].onload = function() {
					var imageSrc = this.src;
					$("img[id="+this.name.replace("/","\\/")+"]").each(function(elem){
						this.src = imageSrc;
						$(this).css({'background':'url('+imageSrc+')'});
						//console.log("done loading image " + this.src + " for id " + this.name);
					});
				}
				thumbImgArr[numThumbImgs].onerror = function() {
					var lastTriedThumbSRC = this.src;
					var lastExtension = lastTriedThumbSRC.match(/\..{3,4}$/);
					if (lastExtension && lastExtension[0]){
						lastExtension = lastExtension[0];
						var lastTriedExtensionIndex = imgFileExtensions.indexOf(lastExtension);
						if ((lastTriedExtensionIndex+1)<imgFileExtensions.length){
							//try next extension, if there are any
							this.src = lastTriedThumbSRC.replace(/\..{3,4}$/,imgFileExtensions[lastTriedExtensionIndex+1]);
						} else {
							this.src = thumbPlaceholder;
						}
					} else {
						//RegEx fail
						this.src = thumbPlaceholder;
					}
				}
				thumbImgArr[numThumbImgs].src = thumb;
				if (thumbId.indexOf("BSDP0360") != -1){
					var a = 1;
				}
				console.log("will load image #" + numThumbImgs + " '" + thumb + "' for id " + thumbId);
				numThumbImgs++;
			}

		if (rep==='true'){
			
			var n = itemsList[item][j].indexOf('/');
			var  sciFile= itemsList[item][j].substring(0, n);
			
			var img= getImage(itemsList[item][j]);
			// ##############################################################################################
			console.log("itemsList[item][j]", itemsList[item][j], "sciFile",repositoryPathForSci+sciFile+"/" );
			// ###############################################################################################
			
			
			var sciPath = repositoryPathForSci+sciFile+"/"+sciFile+".sCi";
			var sciObjId = objectIdFromSciURL(sciPath);
			html += '<div  class="thumbFirstRow"><a href="/' + projectShort + '/single/' + sciObjId + '/' + j + '" target="_blank"><figure><img src="' + thumbPlaceholder + '" id="' + thumbId + '" class="thmb"><figcaption >'+thumbIDString+'<br>'+itemsList[item][j]+'</figcaption></figure></a> </div>';	
		}else {
				html+=  '<div  class="thumbFirstRow"><a href="/' + projectArg + '/object/' + itemsList[item][j] + '"><figure><img src="' + thumbPlaceholder + '" id="' + thumbId + '" class="thmb"><figcaption >'+thumbIDString+'<br>'+itemsList[item][j]+'</figcaption></figure></a> </div>';	
			}
			html += '</div>';
			html += '</div>  </li>';
		}
	}
	}
	return html;
}

//*******************************************************************
// 			htmlObjectsList()
//******************************************************************
function htmlObjectsList(html,firstElement, itemsList, noLines){

	var allDesignData = ''+itemsList["design"]+'_data?startkey="'+arg+'"';
	var designData= getKeyAndValue(viewURL+allDesignData);	
	console.log("htmlObjectsList designData", designData);
	var thumbIDString=  designData[0]._key[arg][BOX];

	if (firstElement !=="") firstElement += ", ";
	
	if (itemsList['subtitle'] ===null) itemsList['subtitle'] ="";
	if (noLines)  itemsList['title'] ="";
	for(var item in itemsList) {
		 if (item === reqObjID ){
				if (rep==='true'){
					html += '<li class="whiteKofferLang"><div class="kofferWrap"><div class="txtOverImage nowrap"><span>'+firstElement+'</span>'+itemsList['title']+' <br> <span class="bigFont">'+itemsList['subtitle']+'</span> <br>';	
				}
				else{
					html += '<li class="kofferLang"><div class="kofferWrap"><div class="txtOverImage"><span>'+firstElement+'</span>'+itemsList['title']+' <br> <span class="bigFont">'+itemsList['subtitle']+'</span> <br>';	
				}
			html += '</div>';  
		
	 			for ( j=0; j<itemsList[item].length; j++){
					var thumb= imageSrc(arg, designData,itemsList[item][j]);					 
					if(j === 0)	{
						
						if (rep==='true'){
							var n = itemsList[item][j].indexOf('/');
							var  sciFile= itemsList[item][j].substring(0, n);
							var img= getImage(itemsList[item][j]);
							var sciPath = repositoryPathForSci+sciFile+"/"+sciFile+".sCi";
							var sciObjId = objectIdFromSciURL(sciPath);
							html+=  '<div  class="thumbFirstRow"><a href="/' + projectShort + '/single/' + sciObjId + '/' + j + '" target="_blank"><figure><img src="'+thumb+'" class="thmb"><figcaption >'+thumbIDString+'<br>'+itemsList[item][j]+'</figcaption></figure></a> </div>';	
						}else{
							html+=  '<div  class="thumbFirstRow"><a href="/' + projectArg + '/object/' + itemsList[item][j] + '"><figure><img src="'+thumb+'" class="thmb"><figcaption >'+thumbIDString+'<br>'+itemsList[item][j]+'</figcaption></figure></a> </div>';				
						}
						
					}
					else {
					
					html+=  ' <div class="thumbs"><a href="/' + projectArg + '/object/' + itemsList[item][j] + '"><figure><img src="'+thumb+'" class="thmb"><figcaption >'+thumbIDString+'<br>'+itemsList[item][j]+'</figcaption> </figure></a> </div>';	
						 
					}
				}	
				
				
		}
		html += '</div>';
		html += '</div>  </li>';
	}
	return html;
}

//********************************************************************
//*****************************************************
// showAllObj 
//*****************************************************
function showAllObj(dataList,arg){
	
var htmlLi = '';
$('#path').empty();
 $('.navHorizontalList ul').remove();
// keine List of -> sonder filterBy-inhalt
//$('#path').append(groupBy);
 $('.alphabet').remove();
  $('ul#double').attr('id','single');
  
	 for (var i in dataList){
		 ////console.log(dataList[i]._value);
		  htmlLi=subHtmlObjectsList(htmlLi,"", dataList[i]._value);
	 }
			 
	$('#single').append(htmlLi);	
	
}	
//****************************************************************
// showAllGroupSort()
//*****************************************************
function showAllGroupSort(dataList, arg, alphabetList ){
	var noLines = false;
	var htmlLi = '';
	
	$('#double').remove();
	$('#lists').css({"overflow":"hidden"});
	////////////////
	var htmlLiA ='<ul class="specialList">';
	var liCounter=0; 
	////console.log(dataList);
	// Count listelement to make  Columnns
	for (var lvl_0 in dataList){ 
		if (dataList[lvl_0]._key === arg){
			for (var lvl_1 in dataList[lvl_0]._value ){
			  liCounter++;
			}
		}
	}
	var half=Math.round(liCounter/2);
	// @endof Count for Columnns
	
	// AlphabetList
	var abc=[];
	 
	for (var lvl_0 in dataList){ 
	  if (dataList[lvl_0]._key === arg){
	    //level1
		for (var lvl_1 in dataList[lvl_0]._value ){
			liCounter--;
			//////////////
			for (var alph in alphabetList ){
				if (alphabetList[alph]._value === lvl_1){
						var word =alphabetList[alph]._value;
						var wordTrimmed= word.replace(/[|.&;$%@"<>()+,]/g, '');
						var wordTrimmed = wordTrimmed.replace(/\s/g, '');						 
						abc.push(word);
						if(liCounter < half){
							$('.objectsLists').append('<ul id="col1"></ul>'); 
							$('#col1').append(htmlLi);
							$('.objectsLists').last().append('<ul id="col2"></ul>');
							htmlLi="";
							liCounter = half*2;
						}
						htmlLi+=' <li class="anchor"><a id="'+ wordTrimmed+'">'+word+'</a></li>   ' ;
					 
					break;
				}
			}  
			
			if (reqObjID in dataList[lvl_0]._value[lvl_1]) {
				noLines=true
				htmlLi = htmlObjectsList(htmlLi, "", dataList[lvl_0]._value[lvl_1], noLines);								
				}
			else{
				for (var m in dataList[lvl_0]._value[lvl_1 ] ){
					    
						if (reqObjID in dataList[lvl_0]._value[lvl_1 ][m])  	
								htmlLi=htmlObjectsList(htmlLi,"" , dataList[lvl_0]._value[lvl_1 ][m], noLines);					
				}				
			} ///if else
		}
	  }
	}
	 
	 for(var i in abc){
		 ////console.log(abc[i]);
		 var newAnchor= abc[i].replace(/[|.&;$%@"<>()+,]/g, '');
		 newAnchor = newAnchor.replace(/\s/g, '');
 		htmlLiA += '<li><a href="#'+newAnchor+'">'+abc[i]+'</a></li>';
	 }
	
	htmlLiA +='</ul>';	
	// @ endof AlphabetList
	 $('.alphabet').append(htmlLiA);
	$('#col2').append(htmlLi);
}

//****************************************************************
// showAllAlphabetSort()
//*****************************************************
function showAllAlphabetSort(dataList, arg, alphabetList ){
	var htmlLi = '';
	$('#double').remove();
	$('#lists').css({"overflow":"hidden"});
	////////////////
	var htmlLiA ='<ul>';
	var liCounter=0;
	// Count for Columnns
	for (var lvl_0 in dataList){ 
	
		if (dataList[lvl_0]._key === arg){ 
			for (var lvl_1 in dataList[lvl_0]._value ){
			  liCounter++;
			   
			}
		}
	}
	var half=Math.round(liCounter/2);
	// @endof Count for Columnns
	 
	// AlphabetList
	var abc=[];
	var temp="";
	for (var lvl_0 in dataList){ 
	  if (dataList[lvl_0]._key === arg){
	    //level1
		for (var lvl_1 in dataList[lvl_0]._value ){
			liCounter--;
			//////////////
			for (var alph in alphabetList ){
				if (alphabetList[alph]._value === lvl_1){
					if (temp != alphabetList[alph]._key){ 
						var letter =alphabetList[alph]._key.charAt(0).toUpperCase();
						if (alphabetList[alph]._value === "Unknown"){
							abc.push(alphabetList[alph]._value);
						}else{
							abc.push(alphabetList[alph]._key.charAt(0).toUpperCase());
						}
						if  (liCounter < half)  {
							$('.objectsLists').last().append('<ul id="col1"></ul>'); 
							$('#col1').append(htmlLi);
						 	if (liCounter >0){ 
								$('.objectsLists').last().append('<ul id="col2"></ul>');
								liCounter = half*2;
							}
							htmlLi="";						 
						} 
						if (alphabetList[alph]._value === "Unknown"){
							abc.push(alphabetList[alph]._value);
		 				htmlLi+=' <li class="anchor"><a id="'+ alphabetList[alph]._value+'">'+alphabetList[alph]._value+'</a></li>   ' ; 
						}else{
			 		htmlLi+=' <li class="anchor"><a id="'+ alphabetList[alph]._key.charAt(0).toUpperCase()+'">'+alphabetList[alph]._key.charAt(0).toUpperCase()+'</a></li>   ' ;
						 
						}					
					}
					temp = alphabetList[alph]._key;
					break;
				}
			} // for (var alph in alphabetList ) 
		
			
			if (reqObjID in dataList[lvl_0]._value[lvl_1]) {
				////console.log(lvl_0);
				htmlLi = htmlObjectsList(htmlLi, lvl_1, dataList[lvl_0]._value[lvl_1]);								 
				}
			else{
				for (var m in dataList[lvl_0]._value[lvl_1 ] ){
						if (reqObjID in dataList[lvl_0]._value[lvl_1 ][m])  	
								htmlLi=htmlObjectsList(htmlLi,lvl_1 , dataList[lvl_0]._value[lvl_1 ][m]);					
				}				
			} ///if else
			
			
		}
	  }
	}
	abc = abc.sort();
	var abcVor = abc[0];
	var unknown= false;
 	htmlLiA += '<li><a href="#'+abcVor+'">'+abcVor+'</a></li>';
	for (var i= 1 ; i< abc.length; i++){
	 if (abc[i] ===  'Unknown' ){
		 unknown = true;
	 }else{
	 	if (abcVor != abc[i]){
		 	abcVor = abc[i];
		 	htmlLiA += '<li><a href="#'+abcVor+'">'+abcVor+'</a></li>';		 
		}
	 }
	}
	if(unknown === true) htmlLiA += '<li><a href="#Unknown">Unknown</a></li>';
	
	htmlLiA +='</ul>';	
	// @ endof AlphabetList
	 $('.alphabet').append(htmlLiA);
	 
	 // only one col if liCounter == 0
	 if (liCounter === 0) $('#col1').append(htmlLi);
	 else $('#col2').append(htmlLi);
}
//*****************************************************
// showByGroupMultiLevel()
//*****************************************************
function showByGroupMultiLevel (dataList,arg, groupBy){
	
var htmlLi = '';
$('#path').empty();

// keine List of -> sonder filterBy-inhalt
$('#path').append(groupBy);
 $('.alphabet').remove();
 $('.navHorizontalList ul').remove();
  $('ul#double').attr('id','single');
  console.log("dataList: ", dataList);
	for (var lvl_0 in dataList){ 	     	
			if (dataList[lvl_0].key.replace(/\s/g, "") == arg.replace(/\s/g, "")){
				for (var lvl_1 in  dataList[lvl_0].value){
						if ( lvl_1.replace(/\s/g, "") === groupBy.replace(/\s/g, "")) {
					
						for (var m in dataList[lvl_0].value[lvl_1 ] ){
							if ( reqObjID  in dataList[lvl_0].value[lvl_1 ][m]) {
							 
									htmlLi=subHtmlObjectsList(htmlLi, m, dataList[lvl_0].value[lvl_1 ][m]);
									 
							}
	
						}
					}
				}
		    }
		 
	}
	 
	$('#single').append(htmlLi);	
	
}


//*****************************************************
// showByGroup()
//*****************************************************
function showByGroup (dataList,arg, groupBy){
	
var htmlLi = '';
$('#path').empty();

// keine List of -> sonder filterBy-inhalt
$('#path').append(groupBy);
 $('.alphabet').remove();
  $('ul#double').attr('id','single');
	for (var lvl_0 in dataList){ 	     	
			if (dataList[lvl_0]._key == arg){
				for (var lvl_1 in  dataList[lvl_0]._value){
						if ( lvl_1 === groupBy) {
					
						for (var m in dataList[lvl_0]._value[lvl_1 ] ){
							if ( reqObjID  in dataList[lvl_0]._value[lvl_1 ][m]) {
							 
									htmlLi=subHtmlObjectsList(htmlLi, m, dataList[lvl_0]._value[lvl_1 ][m]);
									 
							}
	
						}
					}
				}
		    }
		 
	}
	 
	$('#single').append(htmlLi);	
	
}

//*****************************************************
// showByGroupAndFilterMultiLevel()
//*****************************************************
function showByGroupAndFilterMultiLevel (dataList, groupBy, filter){
	$('.navHorizontal2').remove();
	var htmlLi = '';
	$('#path').empty();
	// keine List of -> sonder filterBy-inhalt
	
	$('.alphabet').remove();
	$('ul#double').attr('id','single');
	
	// special treatment for the case where filterBy = Obj
	if  (filterBy === reqObjID){
		for (var lvl_0 in dataList){ 	
		      

				for (var lvl_1 in dataList[lvl_0].value ){ 
						if(lvl_1 === groupBy){
							
							if (reqObjID in dataList[lvl_0].value[lvl_1 ])
								var htmlHeader = '<a href="#" class="active">'+data+'</a>';
							htmlLi=subHtmlObjectsList(htmlLi,"",  dataList[lvl_0].value[lvl_1 ]);
															
						}
				}
			}
	}
	else{
	
		for (var lvl_0 in dataList){ 
		     
				if (dataList[lvl_0].key === groupBy){ 
			        for (var lvl_1 in dataList[lvl_0].value ){
				    
				    if (lvl_1.replace(/\s/g, "")  === filterBy.replace(/\s/g, "") ){console.log(filterBy,lvl_1);
					//for (var m in dataList[lvl_0].value[lvl_1 ] ){
						//if(m === filterBy){
							if (reqObjID in dataList[lvl_0].value[lvl_1 ])
								htmlLi=subHtmlObjectsList(htmlLi,"", dataList[lvl_0].value[lvl_1 ]);								
						//}
					//}
					
				     }
				 }
			}
		
		}
	}
	$('#single').append(htmlLi);		
}
//*****************************************************
// showByGroupAndFilter()
//*****************************************************
function showByGroupAndFilter (dataList, groupBy, filter){
	$('.navHorizontal2').remove();
	
	var htmlLi = '';
	$('#path').empty();
	// keine List of -> sonder filterBy-inhalt
	
	$('.alphabet').remove();
	$('ul#double').attr('id','single');
	
	// special treatment for the case where filterBy = Obj
	if  (filterBy === reqObjID){
		for (var lvl_0 in dataList){ 
				for (var lvl_1 in dataList[lvl_0]._value ){ 
						if(lvl_1 === groupBy){
							
							if (reqObjID in dataList[lvl_0]._value[lvl_1 ])
								var htmlHeader = '<a href="#" class="active">'+data+'</a>';
							htmlLi=subHtmlObjectsList(htmlLi,"",  dataList[lvl_0]._value[lvl_1 ]);
															
						}
				}
			}
	}
	else{
	
		for (var lvl_0 in dataList){ 
			for (var lvl_1 in dataList[lvl_0]._value ){
				// check the groupBy first
				if (lvl_1 === groupBy){
					for (var m in dataList[lvl_0]._value[lvl_1 ] ){
						if(m === filterBy){
							if (reqObjID in dataList[lvl_0]._value[lvl_1 ][m])
								htmlLi=subHtmlObjectsList(htmlLi,"", dataList[lvl_0]._value[lvl_1 ][m] );								
						}
					}
				}
			}
		
		}
	}
	$('#single').append(htmlLi);		
}
//******************************************************************
// findElementInList
// search for 1.st occurence of element  in key
// element: string
// data: list of objects
// return value of key
//******************************************************************
function findElementInList(element, data){

	for(var i in data){ 
		if (i === element){	
				var returnValue = data[i];
				break;
		}
		if( (data[i] !== null) && (typeof(data[i])==='object' )){
			return findElementInList(element, data[i]);	
		}
	}
	return returnValue;
}




//*********************************************************
//*  scitableLink
//**********************************************************
function scitableLink( key, list,objId){
	 for (var ind1 in list){
		 for(var ind2 in list[ind1]){
			 for(var ind3 in list[ind1][ind2]){
				 
				//////////////console.log( ind3, designData[ind1][ind2][ind3]);
				if (ind3 === key ){
				 var srcLink; 
				  srcLink= list[ind1][ind2][ind3].scitable ;
				   if (!srcLink) 
				   
					 return false;
					else {
										 
				 		var sciLink = srcLink.replace("_BILDXXX",objId );
						 
						}
					
				  	return sciLink;
				 
				}
			 }
		 }
	}
	return false;
}
//*******************************************************************
// 			imageSrc()
//******************************************************************	
function imageSrc( key, list, objId ){
	for (var ind1 in list){
		for(var ind2 in list[ind1]){
			for(var ind3 in list[ind1][ind2]){
				if (ind3 === key ){
					var pathStr = list[ind1][ind2][ind3].thumb;
					var imageSrc = pathStr.replace("_BILDXXX",objId );
					if (imgFileExtensions && (imgFileExtensions.length > 0)){
						//use first extension
						imageSrc = imageSrc.replace(/\..{3,4}$/,imgFileExtensions[0]);
					}
					return imageSrc;
				}
			}
		}
	}
	return false;
}

/**
 * Return Object ID from SCI URL, e.g. "BSDP0123"
 */
function objectIdFromSciURL(sci, extractNumber) {
	extractNumber = typeof extractNumber !== 'undefined' ? extractNumber : true;

	var lastSlash = sci.toLowerCase().lastIndexOf("/") + 1;
	var lastDot = sci.toLowerCase().lastIndexOf(".sci");

	var id = sci.substr(lastSlash, lastDot - lastSlash);

	if (!extractNumber) {
		return id;
	} else {
		var elems = id.match(/^([a-zA-Z]+)([0-9]+)$/);
		return elems[2];
	}
}
