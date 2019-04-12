
function getKeyAndValue(url){
	var myCollection = $.parseJSON(
		$.ajax({
			url: url, 
			async: false,
			dataType: 'json',
			success : function(resp) {
				//console.log ("Data " + JSON.stringify(resp));
			},
			error: function (resp) {
				return false;
			}
		}).responseText
	);

	var dataList = new Array();
	for(var k=0; k<myCollection.rows.length; k++) {
		dataList.push({_key:myCollection.rows[k].key, _value:myCollection.rows[k].value}); 
	}
	///* return eliminateDuplicates(dataList);*/
	return dataList;
};

function getKey(url){ 
	var myCollection = $.parseJSON(
		$.ajax({
			url: url, 
			async: false,
			dataType: 'json',
			success : function(resp) {
				//console.log ("Data " + JSON.stringify(resp));
			},
			error: function (resp) {
				return false;
			}
		}).responseText
	);

	var dataList = new Array();
	for(var k=0; k<myCollection.rows.length; k++) {
		dataList.push(myCollection.rows[k].key);    
	}
	///* return eliminateDuplicates(dataList);*/
	return dataList;
};

  
function getSingleObjectByKey(url, key){
      var rows = getKeyAndValue(url + '?key=' + key);
      return rows[0]._value;
};
