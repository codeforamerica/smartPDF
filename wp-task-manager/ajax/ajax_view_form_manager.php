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
	$table1 = $wpdb->prefix."smartPDF_data";	
	
	//prepare query
	$select = "SELECT FileID, FileName, DateEntered, FormName, DownloadURL, Email FROM $table ";
	$where = " WHERE locate('$current_user->user_email', email) > 0 AND ";		
	
	if( $view == 'active' )
		$where .= " FormStatus = 'ACTIVE' AND ";
	else
		$where .= " FormStatus = 'DELETED' AND ";

	if( $formType == '0' ) 
		$where .= " FormName != '' ";	
	else
		$where .= " FormName = '$formType' ";

	$sortSql = " order by $sortname $sortorder";
    $searchSql = ($qtype != '' && $query != '') ? " AND ( locate(trim('$query'),$qtype) > 0 )" : '';

	$numRecords = $wpdb->get_var( "SELECT COUNT(*) FROM $table $where $searchSql;" );

	$pageStart = ($page-1)*$rp;
	$limitSql = " limit $pageStart, $rp";
	$sql = $select.$where.$searchSql.$sortSql.$limitSql.";";

	$data = array();
	$data['page'] = $page;
	$data['total'] = $numRecords;
	$data['rows'] = array();
	$task = $wpdb->get_results($sql, ARRAY_A);
	//$format_lang = get_option(OPTION_DATE_FORMAT);
	$format_lang = "D m/d/Y h:m:s a";

	for($i=0;$i < $rp && ($i+$page) <= $numRecords;$i++){
		$fileID = $task[$i]['FileID'];
		$query = "SELECT COUNT(*) FROM $table1 WHERE FileID = '$fileID' AND ( Status = 'NEW' OR Status = 'OPEN' );";
		$numNewOpenedDisplay = intval( $wpdb->get_var( $query ) );				
		$isDisabled = ( $numNewOpenedDisplay  != 0 ) ? "disabled" : " ";

		$formName = filter_var( $task[$i]['FormName'],FILTER_SANITIZE_STRING );
		$dateCreateDisplay = mysql2date( $format_lang, filter_var( $task[$i]['DateEntered'], FILTER_SANITIZE_STRING ) );
		$fileName = split( "/", filter_var($task[$i]['FileName'], FILTER_SANITIZE_STRING) );
		$downloadURL = filter_var( $task[$i]['DownloadURL'], FILTER_SANITIZE_URL );
		$emails =  trim( filter_var( $task[$i]['Email'], FILTER_SANITIZE_STRING ) );

		$formNameDisplay = "<input type=text name='formname_$fileID' value='$formName' onfocus=\"setbg('$fileID')\" onchange=\"saveFormText(this, '$fileID')\" >";
		$emailsStrDisplay = "<textarea rows='2' cols='35' style=\"height: 40px;\"onfocus=\"setbg('$fileID')\" onchange=\"return saveFormText(this, '$fileID')\" name='emails_$fileID' ";
							
	    if( $isDisabled == "disabled" )
			$actionDisplay = "Cannot delete until all forms are processed.";
		else
			$actionDisplay = "<a href=# onclick=\"saveFormClick(this, '$fileID')\" name=\"delete_$fileID\"> delete </a>";
		$actionDisplay .= "<br><a href='$downloadURL'> download </a>";

		if($view == 'archived'){
			$emailsStrDisplay .= " disabled \>$emails</textarea>";
			$actionDisplay = '';
			$formNameDisplay= "<input type=text name='formname_$fileID' value='$formName' disabled />";
		}
		else{
			$emailsStrDisplay .= "\>$emails</textarea>";
		}


		$data['rows'][] = array(
				'DateEntered' => $dateCreateDisplay,
				'cell' => array($dateCreateDisplay, $formNameDisplay, $fileName[count($fileName) - 1], $numNewOpenedDisplay, $emailsStrDisplay, $actionDisplay)
		);
	}	
	echo json_encode($data);

?>

