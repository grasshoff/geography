/**
 * Functions for collection display
 */

/** BODY ONLOAD **/
$(function () {
    // onclick listener for whole button
    $('#collection_top .top_nav ul li').click(function() {
        var link = $(this).find('a').attr('href');
        window.location.href = link;
    });
});