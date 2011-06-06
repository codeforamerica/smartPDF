<?php
    if (!function_exists('add_action'))
    {
        require_once("../../../../wp-config.php");
    }
?>
Event.observe(window, 'load', clickInit, false);    
function clickInit() {
        //$('getKey').onclick = onClickEvent;
	;
}
function onClickEvent(evt) {
        var url =  "<?php bloginfo('wpurl') ?>/wp-content/plugins/smartpdf-upload/ajax.php";
        var success = function(t){onClickComplete(t);}
        var myAjax = new Ajax.Request(url, {method:'post', onSuccess:success});
        return false;
}
function onClickComplete(t) {
        $('smartPDF-uploadKEY').value = t.responseText; 
	$('hidden-uploadKEY').value = t.responseText;
}

function showAdditionalFields(thisField) {
	jQuery("#customFields").toggle();

	if( thisField.checked )	{
		var htmlstring = '<table>';
		for(i=1; i < 11; i++)
			htmlstring = htmlstring + '<tr><td>Field '+ i + ':</td><td><input type=text id=custom_field' + i + ' name=custom_field'+ i + ' /></td></tr>';
		htmlstring = htmlstring + "</table>";
		jQuery("#customFields").html(htmlstring);
	}
}
