<?php
	require_once '../../../../wp-config.php';
	require_once '../constant.php';

	global $wpdb, $current_user;

	// number of records per page
	define(PAGE_OFFSET, 5);
	
	$view 		= filter_input(INPUT_GET, 'view', FILTER_SANITIZE_STRING);
	$formType 	= filter_input(INPUT_GET, 'formType', FILTER_SANITIZE_STRING);
	$url 		= filter_input(INPUT_GET, 'url', FILTER_SANITIZE_URL);
	$currentPage 	= filter_input(INPUT_GET, 'currentPage', FILTER_SANITIZE_NUMBER_INT);	
	$keyword 	= filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING);

	$table = $wpdb->prefix."smartPDF";		
	//prepare query
	$select = "SELECT TransactionID, TimeStamp, Status, Notes, FormName, Email, FileAttachmentPath, FileAttachmentCount ";
	$from =   " FROM ".$table."_data A JOIN $table B ON A.FileID = B.FileID ";
	$where =  " WHERE locate('$current_user->user_email', Email) > 0 ";		
	
	if( $formType == '0' ) 
		$where .= " AND FormName != '' ";	
	else
		$where .= sprintf( " AND FormName = '%s' ", $formType);

	switch( $view ){
		default:
		case 'active':
			$where .= " AND Status = 'NEW' ORDER BY TimeStamp ";
			break;
			
		case 'done':
			$where .= " AND Status = 'CLOSED' ORDER BY TimeStamp ";
			break;

		case 'opened':
			$where .= " AND Status = 'OPEN' ORDER BY TimeStamp ";
                         break;
	
		case 'all':
			$where .= " ORDER BY TimeStamp ";				
			break;
	}
	// for pagination
	
	$query .= $select.$from.$where." LIMIT $currentPage, ".PAGE_OFFSET .";";
	$numRecords = $wpdb->get_var( "SELECT count(*) $from $where;" );
	$list_task = $wpdb->get_results($query, ARRAY_A);
	$format_lang = "D m/d/Y h:m:s a";
	if($list_task) {
//		
?>
<script type="text/javascript">

function saveText(thisField, id, currentStatus) {
	  var value = jQuery.trim(thisField.value);
	  var transactionid = jQuery.trim(id);
	  var fieldname = jQuery.trim(thisField.name);
	  
	if( currentStatus != '' ) {		
	  	if( !confirm("Changing the status to " + value) ){
			jQuery("#" + fieldname).val(currentStatus);			
			return false;
		}
	  }
      jQuery.get("../wp-content/plugins/wp-task-manager/ajax/ajax_save_form.php", { fileid: transactionid, value: value, dbfield: fieldname, type: "transaction" },
          function(data){
            getTask(false);
      });
}

function sendEmail( emailList, formName, transactionid ){
	if( emailList != '' )
		jQuery.get("../wp-content/plugins/wp-task-manager/ajax/ajax_send_email.php", {emailList: emailList, formName: formName, transactionid: transactionid}, 
			function(data){
				if(jQuery.trim(data) == '' )
					jQuery("#emailMessage").html("Email sent successfully!").fadeIn(1000).delay(2000).fadeOut(2000);
				else
					jQuery("#emailMessage").html("Email failed to send " + data).fadeIn(2000);
		});
}

function nextPrevious( page ) {
	currentPage = page;	
	getTask();
}

function setbg( id ){
	jQuery("#wptm_table tr").css("background-color", "white");
	jQuery("#"+id).css("background-color", "lightgray");
}

</script>

<div id="emailMessage" style="text-align: center"> </div>
<br>
 <form name="saveForm" method="post" action="<?php echo $wp_admin_base_url.'&amp;task=save' ?>">
	<table class="draggable widefat sortable" id="wptm_table">
		<thead>
		<tr>
			<th>Date</th>
			<th>Form Name</th>
			<th>Confirmation #</th>
			<th>Status</th>
			<th>Attachment</th>
			<th>Notes</th>
			<th>Action</th>
		</tr>
		</thead>
		<tbody>
<?php
			// for pagination
			for($i = 0; $i < PAGE_OFFSET && ($i + $currentPage) < $numRecords; $i++ ){
				$formattedID = '';
				$transactionid = filter_var( $list_task[$i]['TransactionID'],FILTER_SANITIZE_STRING );
				for($k=0; $k < strlen( $transactionid ) - 4;$k += 4)
					$formattedID .= substr( $transactionid, $k, 4 ) ."-";
				$formattedID .= substr($transactionid, $k, 4);
				$formattedID = strtoupper( $formattedID );
				$status = filter_var( $list_task[$i]['Status'],FILTER_SANITIZE_STRING );
				$formName = filter_var( $list_task[$i]['FormName'],FILTER_SANITIZE_STRING );
				$emailList = filter_var( $list_task[$i]['Email'], FILTER_SANITIZE_STRING );
				$dateCreate = filter_var( $list_task[$i]['TimeStamp'], FILTER_SANITIZE_STRING );
				$nb_comment = filter_var( $list_task[$i]['Notes'], FILTER_SANITIZE_STRING );
				$fileAttachmentPath = filter_var( $list_task[$i]['FileAttachmentPath'], FILTER_SANITIZE_STRING );
				$fileAttachmentCount = filter_var( $list_task[$i]['FileAttachmentCount'], FILTER_SANITIZE_NUMBER_INT );
				$description = 'Smart PDF';
?>
				<tr id="<?php echo $transactionid?>" style="
<?php 				
				if( ('NEW' == $status) )
					echo 'color:red;';
				else
					echo 'color:none;';
?>
				 ">
					<td width="15%" class="tooltip" title="<?php echo $description." - Creation date";?>"><?php echo mysql2date( $format_lang, $dateCreate ); ?></td>
					<td width="10%" class="tooltip" title="<?php echo $description." - Form name";?>"><?php echo $formName; ?></td>
					<td width="15%" class="tooltip" title="<?php echo $description." - Confirmation number";?>"><?php echo $formattedID; ?></td>
					<td width="8%" class="tooltip" title="<?php echo $description." - Status";?>">
						<select onfocus="setbg('<?php echo $transactionid?>')" onchange="return saveText(this, '<?php echo $transactionid ?>', '<?php echo $status?>')" name="select_<?php echo $transactionid;?>" id="select_<?php echo $transactionid?>">
							<option value="NEW" <?php if($status == 'NEW') echo 'selected'; ?> >NEW</option>
							<option value="OPEN" <?php if($status == 'OPEN') echo 'selected'; ?> >OPEN</option>
							<option value="CLOSED" <?php if($status == 'CLOSED') echo 'selected'; ?> >CLOSED</option>
						</select>	
					</td>
					<td width="5%" class="tooltip" title="<?php echo $description. " - file attachments";?>" >
						<?php if( $fileAttachmentCount > 0 ) {?>
						<a href="#" onClick="downloadAttachment('<?php echo $transactionid ?>', '<?php echo $smartpdf_service_url?>' )" >
							<img src="<?php echo IMG_DIRECTORY;?>pdf-file-logo-icon.jpg" width=35px height=35px/></a>
						<? } ?>
					</td>
					<td width="25%" class="tooltip" title="<?php echo $description." - Notes";?>">
						<textarea rows="2" cols="40" onfocus="setbg('<? echo $transactionid ?>') "onchange="saveText(this, '<?php echo $transactionid ?>', '')"  name="<?php echo "notes_".$transactionid;?>" style="text-align: justify;" ><?php echo $nb_comment;?></textarea>
					</td>
					<td width="10%"> <a href="#" onclick="getTransaction('<?php echo $transactionid?>', '<?php echo $smartpdf_service_url?>')" class="tooltip" title="Click to open">open </a><br><a href="#" onclick="sendEmail('<?php echo $emailList ?>', '<?php echo $formName ?>', '<?php echo $transactionid ?>');" class="tooltip" title="Send email" >email</a></td>
				</tr>
<?php
			}
?>
				</tbody></table>
			 </form>
<br><table>
<?
		 $previous = $currentPage - PAGE_OFFSET;
         $next = $currentPage + PAGE_OFFSET;
         if( $currentPage == 0 && $numRecords > PAGE_OFFSET )
             echo "<tr>	<td width='100px'>&nbsp; </td><td width='200px'>Page ".($currentPage / PAGE_OFFSET + 1)." of ".ceil($numRecords / PAGE_OFFSET) ."</td><td width='150px' title=\"Next ".PAGE_OFFSET."\" onclick=\"nextPrevious($next)\"> NEXT </td></tr>";
         else if( $currentPage > 0 && $numRecords > PAGE_OFFSET + $currentPage)
             echo "<tr><td width='150px' class='tooltip' title=\"Previous ". PAGE_OFFSET."\" onclick=\"nextPrevious($previous)\"> PREVIOUS </td><td width='200px'>Page ".($currentPage / PAGE_OFFSET + 1)." of ".ceil($numRecords / PAGE_OFFSET)."</td> <td class='tooltip' title=\"Next ".PAGE_OFFSET."\" onclick=\"nextPrevious($next)\"> NEXT </td> </tr>";
         else if( $currentPage > 0 && $numRecords < PAGE_OFFSET + $currentPage )
             echo "<tr><td width='150px' class='tooltip' title=\"Previous" . PAGE_OFFSET ."\" onclick=\"nextPrevious($previous)\">PREVIOUS</td><td width='200px'>Page ".($currentPage / PAGE_OFFSET + 1)." of ".ceil($numRecords / PAGE_OFFSET)."</td><td width='100px'>&nbsp;</td> </tr>";
?>
</table>
      			<script type="text/javascript">
					jQuery("#wptm_table").tablesorter({sortList: [[0,0], [1,0]] } );
					tooltip();
			</script>
<?php 
		}else{
			echo "<p>No forms of this type.</p>";
		}
?>

