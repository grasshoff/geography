function loadsCiItemJsonV2(sCiJson, div) {
    var $div = $(div);
    var sCiContainer = $("<div></div>");
    sCiContainer.height("100%");
    sCiContainer.width("100%");
    $div.append(sCiContainer);

    var loadToolsCi = function(sCi){
        //load sCi-tool-JSON
        if (typeof sCi.tool !== "undefined"){
            //strip protocol to allow https and http requests
            var toolURL = sCi.tool.replace(/^http:/,"");
            $.getJSON(toolURL,function(sCiTool){
                var iframe=$("<iframe style='width:100%; height:100%;'></iframe>");
                var iFrameSRC;
                if (typeof sCiTool.access !== "undefined"){
                    var access = sCiTool.access;
                    iFrameSRC = access.prefix;

                    var objects = [];
                    //always have objects in an array
                    if (sCi.url instanceof Array){
                        objects = sCi.url;
                    } else {
                        objects = [sCi.url];
                    }

                    var accessParamCnt = 0;
                    for (var i=0; i<objects.length; i++){
                        iFrameSRC += access.parameter[accessParamCnt]
                            .replace("%v%",objects[i])
                            .replace("%i%",i);
                        if (accessParamCnt < (access.parameter.length-1)){
                            accessParamCnt++;
                        }
                    }

                    iFrameSRC += access.suffix;

					//TODO: this should be incorporated in above "accessParam" loop
					//or loop above could be removed. Both serve the same purpose
                    if (sCi.additional_tool_attributes){
                    	//only add ampersand if we really add more data
                    	var alreadySetAmpersand = false;

                    	var additionalAttributes = sCi.additional_tool_attributes;
                    	for (attribute in additionalAttributes){
                    		var attributeContent = additionalAttributes[attribute];

                    		if (attributeContent.name && attributeContent.value){
	                    		var attributeName = attributeContent.name;
	                    		var attributeValue = attributeContent.value;

	                    		if (!alreadySetAmpersand){
	                    			alreadySetAmpersand = true;
	                    			iFrameSRC += "&";
	                    		}
	                    		iFrameSRC += attributeName + "=" + attributeValue;
                    		}
                    	}
                    }
                } else {
                    //"old" way of access definition
                    iFrameSRC = sCiTool.url;
                    if (sCi.url instanceof Array){
                        // Tool-JSON has to be extended for this
                    } else {
                        iFrameSRC += sCi.url;
                    }
                }
                //strip protocol to allow https and http requests
                //TODO: this is somewhat dangerous, might leed to problems
                var iFrameSRC = iFrameSRC.replace(/^http:/g,"");
                iframe.attr("src",iFrameSRC);
                iframe.attr("frameBorder","0");
                iframe.width("100%");
                iframe.height("100%");
                iframe[0].setAttribute('allowFullScreen', '')

                //TODO: this is hack, this has to be well defined behaviour
                var isFullscreen = false;
                if ((typeof sCi.fullscreen !== "undefined") && (sCi.fullscreen === true) && (document.referrer == "")){
                	isFullscreen = true;
                }
                
                if (window.location.hash){
                	var hash = window.location.hash;
                	if (hash.indexOf("tabMode") != -1){
                		isFullscreen = true;
                	}
                }

                if (isFullscreen){
                	window.location.replace(iFrameSRC);
                } else {
                	sCiContainer.empty().append(iframe);
                }

                if (typeof callback !== "undefined"){
                    callback(div);
                }
            });
        }
    };

    //strip protocol to allow https and http requests
    if (typeof sCiJsonScope === "string"){
        var sCiJsonScope = sCiJson.replace(/^http:/,"");
    }

    //async retrieval of sCi and DIV building
    setTimeout(function(sCiJsonScope){
        return function(){
            //check if we got a URL or JSON object
            if (typeof sCiJsonScope === "string"){
                //load sCi-JSON
                $.getJSON(sCiJsonScope, function(sCi){
                    loadToolsCi(sCi);
                });
            } else {
                loadToolsCi(sCiJsonScope);
            }
        };
    }(sCiJson),1);

    //return to get possible callback function
    return({then:function(callbackFct){
        callback = callbackFct;
    }});
}
