<?php
	require_once '../../../../wp-config.php';
	require_once '../constant.php';

	global $wpdb, $current_user;
	
	$view = 'opened';
	$formType = '0';
	$page = 1;
	$sortname = 'TimeStamp'; // Sort column
	$sortorder = 'desc'; // Sort order
	$qtype = ''; // Search column
	$query = '';
	// Get posted data

	if (isset($_REQUEST['view'])) {
		$view = mysql_real_escape_string($_REQUEST['view']);
	}
	if (isset($_REQUEST['formType'])) {
		$formType = mysql_real_escape_string($_REQUEST['formType']);
	}
	if (isset($_POST['page'])) {
		$page = mysql_real_escape_string($_POST['page']);
	}
	if (isset($_POST['sortname'])) {
		$sortname = mysql_real_escape_string($_POST['sortname']);
	}
	if (isset($_POST['sortorder'])) {
		$sortorder = mysql_real_escape_string($_POST['sortorder']);
	}
	if (isset($_POST['qtype'])) {
		$qtype = mysql_real_escape_string($_POST['qtype']);
	}
	if (isset($_POST['query'])) {
		$query = mysql_real_escape_string($_POST['query']);
	}
	if (isset($_POST['rp'])) {
		$rp = mysql_real_escape_string($_POST['rp']);
	}

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

	$sortSql = " order by $sortname $sortorder";
	$searchSql = ($qtype != '' && $query != '') ? " AND ( locate(trim('$query'),$qtype) > 0 )" : '';

	$numRecords = $wpdb->get_var( "SELECT COUNT(*) $from $where $searchSql;" );

	$pageStart = ($page-1)*$rp;
	$limitSql = " limit $pageStart, $rp";

	$sql .= $select.$from.$where.$searchSql.$sortSql.$limitSql.";";

	$data = array();
	$data['page'] = $page;
	$data['total'] = $numRecords;
	$data['rows'] = array();
	$list_task = $wpdb->get_results( $sql, ARRAY_A );
	$format_lang = "D m/d/Y h:m:s a";

	for($i=0;$i < $rp && ($i+$page) <= $numRecords;$i++){
		$formattedID = '';
		$transactionid = filter_var( $list_task[$i]['TransactionID'],FILTER_SANITIZE_STRING );
		for($k=0; $k < strlen( $transactionid ) - 4;$k += 4)
			$formattedID .= substr( $transactionid, $k, 4 ) ."-";
		$formattedID .= substr($transactionid, $k, 4);
		$formattedID = strtoupper( $formattedID );
		$status = filter_var( $list_task[$i]['Status'],FILTER_SANITIZE_STRING );
		$formName = filter_var( $list_task[$i]['FormName'],FILTER_SANITIZE_STRING );
		$emailList = filter_var( $list_task[$i]['Email'], FILTER_SANITIZE_STRING );
		$dateCreate = mysql2date( $format_lang, filter_var( $list_task[$i]['TimeStamp'], FILTER_SANITIZE_STRING ) );
		$nb_comment = filter_var( $list_task[$i]['Notes'], FILTER_SANITIZE_STRING );
		$fileAttachmentPath = filter_var( $list_task[$i]['FileAttachmentPath'], FILTER_SANITIZE_STRING );
		$fileAttachmentCount = filter_var( $list_task[$i]['FileAttachmentCount'], FILTER_SANITIZE_NUMBER_INT );
		$description = "<a href='#' onclick=\"getTransaction('$transactionid','$smartpdf_service_url', 'pdf')\" title=\"Click to pdf\"> pdf </a>&nbsp;&nbsp;";
		$description .= "<a href='#' onclick=\"getTransaction('$transactionid','$smartpdf_service_url', 'csv')\" title=\"Click to csv\"> csv </a>";
			
		//construct inline editing
		$selected_new = ($status == 'NEW') ? 'selected': '';
		$selected_open = ($status == 'OPEN') ? 'selected': '';
		$selected_closed = ($status == 'CLOSED') ? 'selected': '';
		$status =  "<select onfocus=\"setbg('<?php echo $transactionid?>')\"" .
					"onchange=\"return saveText(this, '$transactionid', '$status')\"" . 
					"name=\"select_$transactionid\" id=\"select_$transactionid\">" .
		            "<option value=\"NEW\" $selected_new>NEW</option>" .
					"<option value=\"OPEN\" $selected_open>OPEN</option>" .
					"<option value=\"CLOSED\" $selected_closed>CLOSED</option>" .
					"</select>";

		$nb_comment = "<textarea  cols=\"40\" onfocus=\"setbg('$transactionid') \" onchange=\"saveText(this, '$transactionid', '')\"  name=\"notes_$transactionid\" style=\"text-align: justify;height: 40px;\" >$nb_comment</textarea>";

		
		if( $fileAttachmentCount > 0 ){
			$fileAttachmentCount = "<a href=\"#\" onClick=\"downloadAttachment('$transactionid', '$smartpdf_service_url' )\" ><img src=\"". IMG_DIRECTORY."pdf-file-logo-icon.jpg\" width=25px height=25px align=center/></a>";
		}

		$data['rows'][] = array(
				'TimeStamp' => $dateCreate,
				'cell' => array($dateCreate, $formName, $formattedID, $status, $fileAttachmentCount, $nb_comment, $description)
		);
	}
	if( count( $data['rows']) < 1 ){
		$data['rows'][] = array(
				'TimeStamp' => date( $format_lang ),
				'cell' => array('No data available', '', '', '', '', '', '')
			);
	}
	echo json_encode($data);
?>

