function init() {
	$("#form").submit(submit);
	$("#styles").loadSelect("styles").val("apa");
	$("#locales").loadSelect("locales").val("en-US");
	$("#citation").hide();
}

$.fn.loadSelect = function(url) {
	var url = "http://data.crossref.org/" + url;
	var select = $(this);
	$.getAsync(url, function(data) {
		data.sort();
		var options = arrayToSelectOptions(data);
		select.html(options);
	});
	return select;
};

$.getAsync = function(url, success) {
	$.ajax({
		url: url,
	}).done(function(data) {
		success(data.message.items);
	});
};

function arrayToSelectOptions(array) {
	var options = $.map(array, function(elem) {
		return '<option value="' + elem + '">' + elem + '</option>';
	});
	return options.join("");
}

function submit() {
	$("#citation").hide();
	var doi = $("#doi").val();

	$.ajax({
		url: "http://data.datacite.org/text/x-bibliography/"+doi,
		headers: {
			Accept: "text/x-bibliography; style="+ $("#styles").val()+"; locale="+$("#locales").val()
		},
		error : function(jqXHR, textStatus, errorThrown) {
			alert(jqXHR.responseText);
		}
	}).done(function(data) {
		$("#citation").text(data);
		$("#citation").show();
	});
	return false;
}

$(document).ready(init);
