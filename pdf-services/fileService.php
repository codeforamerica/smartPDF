<?php 
/***
   Description: Script to handle file attachment uploads 
   Version: 1.0.0
   Author: Henry jiang

  Copyright 2010 Department of Technology, City and County of San Francisco.
  Redistribution and use in source and binary forms, with or without
  modification, are permitted under certain conditions; see the file
  COPYING for details.
 ***/

require_once 'functions.php';
$transactionid 	= filter_input( INPUT_POST, 'transactionid', FILTER_SANITIZE_STRING );
$fileid			= filter_input( INPUT_POST, 'fileid', FILTER_SANITIZE_STRING );

$result = '';
$error = '';
if ( $_FILES['file1'] && $_FILES['file1']['size'] > 0 ){
	$real_file_name = urldecode( $_REQUEST['real_file_name'] );
	$result = handleFileUpload( $transactionid, $fileid, $real_file_name, $key );
	
	if( strpos( $result, 'Failed' ) > -1 ) {
		$error =  "<script> document.getElementById('messageArea').innerHTML =  '$result'  </script>";
	}
}
$result = attachmentUploadForm( $fileid, $transactionid );

if( $conn )
	mysql_close( $conn );
print $result . $error;

?>
