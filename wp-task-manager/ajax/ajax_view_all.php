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
			$where .= " AND Status = 'NEW' ";
			break;
			
		case 'done':
			$where .= " AND Status = 'CLOSED' ";
			break;

		case 'opened':
			$where .= " AND Status = 'OPEN' ";
            break;
	
		case 'all':
			$where .= " ";				
			break;
	}
	// for pagination
	
	$query .= $select.$from.$where;
	$numRecords = $wpdb->get_var( "SELECT COUNT(*) $from $where;" );
	
	if( $numRecords > 0 ) {
//		

echo "<table id='wptm_table'>
</table>

<script type='text/javascript'>
		jQuery('#wptm_table').flexigrid({
			url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_flexigrid.php',
			dataType: 'json',
			colModel : [
			{display: 'DATE', name : 'TimeStamp', width : 40, sortable : true, align: 'left'},
			{display: 'FORM NAME', name : 'FormName', width : 100, sortable : true, align: 'left'},
			{display: 'CONFIRMATION', name : 'TransactionID', width : 100, sortable : true, align: 'left'},
			{display: 'STATUS', name : 'Status', width : 250, sortable : true, align: 'left'},
			{display: 'ATTACHMENT', name : '', width: 40, sortable : true, align : 'left'},
			{display: 'NOTES', name : 'Notes', width: 150, sortable : true, align : 'left'},
			{display: 'ACTION', name : '', width: 40, sortable : true, align : 'left'}
			],
			searchitems : [
			{display: 'TRANSACTIONID', name : 'TransactionID'},
			{display: 'NOTES', name : 'Notes', isdefault: true},
			{display: 'STATUS', name : 'Status'}
			],
			sortname: 'TimeStamp',
			sortorder: 'desc',
			usepager: true,
			title: 'None',
			useRp: true,
			rp: 10,
			query: '',
			sql: $query,
			//showTableToggleBtn: false,
			resizable: true,
			width: 700,
			height: 370,
			singleSelect: true
		});
	});


		</script>";
		}else{
			echo "<p>No forms of this type.</p>";
		}
?>

