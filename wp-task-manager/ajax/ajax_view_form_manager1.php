<?php
	require_once '../../../../wp-config.php';
	require_once '../constant.php';

	global $wpdb, $current_user;
	
	$formType = filter_input(INPUT_GET, 'formType', FILTER_SANITIZE_STRING);
	$url = filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
	$view = filter_input(INPUT_GET, 'view', FILTER_SANITIZE_STRING);

	
	$table = $wpdb->prefix."smartPDF";
	$table1 = $wpdb->prefix."smartPDF_data";	
	
	//prepare query
	$query = "SELECT * FROM $table WHERE locate('$current_user->user_email', email) > 0 AND ";		
	
	if( $view == 'active' )
		$query .= " FormStatus = 'ACTIVE' AND ";
	else
		$query .= " FormStatus = 'DELETED' AND ";

	if( $formType == '0' ) 
		$query .= " FormName != ''; ";	
	else
		$query .= " FormName = '$formType'; ";

	$list_task = $wpdb->get_results($query, ARRAY_A);
	//$format_lang = get_option(OPTION_DATE_FORMAT);
	$format_lang = "D m/d/Y h:m:s a";
	if($list_task) {
//		
?>
<script type="text/javascript">
function saveFormClick(thisField, id) {
	 var value = jQuery.trim(thisField.value);
     var fileid = jQuery.trim(id);
     var fieldname = jQuery.trim(thisField.name);

	 jQuery.get("../wp-content/plugins/wp-task-manager/ajax/ajax_save_form.php", { fileid: fileid, value: value, dbfield: fieldname, type: "form" },
	     function(data){
             getForm(false);
     });
}

function saveFromText(thisField, id) {
	var isValidEmails = true;
 	var value = jQuery.trim(thisField.value);
    var fileid = jQuery.trim(id);
    var fieldname = jQuery.trim(thisField.name);

    if( thisField.name.indexOf("emails") > -1 ){
		isValidEmails = validateEmails(thisField.value);
	}
	if( isValidEmails ){
      jQuery.get("../wp-content/plugins/wp-task-manager/ajax/ajax_save_form.php", { fileid: fileid, value: value, dbfield: fieldname, type: "form" },
          function(data){
			getForm(false);
      });
	}
	else
		return false;
}

function validateEmails(emails){
	return true;
}

function setbg( id ){
     jQuery("#wptm_form_manager_table tr").css("background-color", "white");
     jQuery("#"+id).css("background-color", "lightgray");
}

</script>
 <form name="saveForm1" method="post" action="<?php echo $pdf_form_manager_base_url.'&amp;form_manager=save' ?>">
	<table class="widefat tablesorter" id="wptm_form_manager_table">
		<thead>
		<tr>
			<th>Date</th>
			<th>Form Name</th>
			<th>File Name</th>
			<th># of NEW/OPEN</th>
			<th>Emails</th>
			<?php if( $view == 'active' ) { ?>
				<th>Action</th>
			<?php } ?>
		</tr>
		</thead>
		<tbody>
<?php
			foreach ( $list_task as $task ){
				$fileID = $task['FileID'];
				$query = "SELECT COUNT(*) FROM $table1 WHERE FileID = '$fileID' AND ( Status = 'NEW' OR Status = 'OPEN' );";
				$numNewOpened = intval( $wpdb->get_var( $query ) );				
				$isDisabled = ( $numNewOpened  != 0 ) ? "disabled" : " ";

				$formName = filter_var( $task['FormName'],FILTER_SANITIZE_STRING );
				$dateCreate = filter_var( $task['DateEntered'], FILTER_SANITIZE_STRING );
				$fileName = split( "/", filter_var($task['FileName'], FILTER_SANITIZE_STRING) );
				$downloadURL = filter_var( $task['DownloadURL'], FILTER_SANITIZE_URL );
				
				$description = 'Smart PDF';
?>
				<tr id="<?php echo $fileID?>">
					<td class="tooltip" title="<?php echo $description. " - Date uploaded";?>"><?php echo mysql2date( $format_lang, $dateCreate ); ?></td>
					<td class="tooltip" title="<?php echo $description. " - Form name";?>">
					<?php if( $view != 'active' ) { ?>
							<?php echo $formName;?>
					<? } else {?>
						<input type="text" onfocus="setbg('<?php echo $fileID?>')" onchange="saveText(this, '<?php echo $fileID ?>')" name="formname_<?php echo $fileID;?>" value="<?php echo $formName;?>" /> 
					<? } ?>
					</td>
					<td class="tooltip" title="<?php echo $description. " - Internal file name";?>"><?php echo $fileName[count( $fileName ) - 1]; ?></td>
					<td class="tooltip" title="<?php echo $description. " - Number of New or Opened forms";?>"> <?php echo $numNewOpened; ?></td>
					<td class="tooltip" title="<?php echo $description. " - List of emails" ;?>">
						<textarea rows="2" cols="35" onfocus="setbg('<?php echo $fileID?>')" onchange="return saveText(this, '<?php echo $fileID ?>')" name="<?php echo "emails_".$task['FileID'];?>" <?php if( $view == 'archived' ) echo "disabled"; ?> ><?php echo trim( $task['Email'] ); ?></textarea></td>

					<?php if( $view == 'active' ) { ?>
					<td class="tooltip" title="<?php echo $description. " - delete form"; ?>">
					<?php if( $isDisabled == "disabled" ) { ?>
						Cannot delete until all form submissions are processed.
					<?php } else {?>
						<a href="#" onclick="saveClick(this, '<?php echo $fileID;?>')" name="<?php echo "delete_".$fileID; ?>">delete</a>
					<?php }?>
					<br><a href="<?php echo $downloadURL;?>" >download </a>
					</td>
					<?php } ?>
				</tr>
<?php
			}
?>
				</tbody></table>
			 </form>

      			<script type="text/javascript">
					jQuery("#wptm_form_manager_table").tablesorter();
					tooltip();
				</script>
<?php 
		}else{
			echo "<p>No forms on record for $current_user->user_email!</p>";
		}
?>

