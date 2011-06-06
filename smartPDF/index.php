<?php

/***
  Copyright 2010 Department of Technology, City and County of San Francisco.
  Redistribution and use in source and binary forms, with or without
  modification, are permitted under certain conditions; see the file
  COPYING for details.
 */


include_once("../wp-config.php");

// change this to the encryption/decryption server
if ( ! defined( BASE_URL ) ){
	define( BASE_URL, "http://10.250.60.134/wordpress/pdf-services/");
}

$transactionid = $_REQUEST['transactionid'] != '' ? $_REQUEST['transactionid'] : '' ;

//-----------------------------------------------------------------------------
/***
 *  Transaction data service, returns user submitted data 
 *
 **/
if( isset( $_GET['getTransactionData'] ) ){
	/**
	 * call the data encrypt/decrypt service to get the fdf data
	 * merge fdf data with pdf file to form completed pdf for user download.
	 */
	$format = $_REQUEST['format'] == '' ? 'pdf' : $_REQUEST['format'];
	$fields_string = array ( 'transactionid' => $transactionid, 'format' => $format );
	$url = BASE_URL."transactionService.php";
	$result = pdfService( $url, $fields_string );

	$hasError =  strpos( $result, 'Failed');
	if( $hasError < 0 || $hasError == '' ){
		// Set the appropriate header for this type of request
		switch ( strtolower( $format ) ) {
			case 'pdf':
				header('Cache-Control: public'); // needed for i.e.
				header('Content-type: application/pdf');
				header('Content-Disposition: attachment; filename="downloaded.pdf"');
				break;
			case 'csv':
				header("Content-type: application/csv");
				header("Content-Disposition: attachment; filename=downloaded.csv");
				header("Pragma: no-cache");
				header("Expires: 0");
				break;
			default:
				header('Cache-Control: public'); 
                header('Content-type: application/pdf');
                header('Content-Disposition: attachment; filename="downloaded.pdf"');
                break;
		}
		print $result;
	}
	else{
		showErrorMessage( $result );
	}
}


//------------------------------------------------------------------------------
/***
 * File attachment service, returns all the files in a .zip file
 *
 **/
else if( isset( $_GET['getAttachment'] ) ){

	$fields_string = array ( 'transactionid' => $transactionid );
	$url = BASE_URL."attachmentService.php";
	$result = pdfService( $url, $fields_string );

	$hasError =  strpos( $result, 'Failed');
	if( $hasError < 0 || $hasError == '' ){
		//header for file attachment
		header('Cache-Control: public'); // needed for i.e.
		header("Content-type: application/octet-stream");
		header("Content-disposition: attachment; filename=\"$transactionid.zip\"");
		print $result;
	}
	else {
		showErrorMessage( $result );
	}
}

//------------------------------------------------------------------------------
/***
 *  End user form submit and file attachment upload handling
 *
 **/
else {
	if( ! isset( $_REQUEST['id'] )){
		error_log( 'smartPDF ERROR: invalid file id - submit url malformed.' );
		die;
	}

	// user submitted form data, proccess them.
	if( isset( $_REQUEST['id'] ) && ! isset( $_REQUEST['transactionid'] ) && ! isset( $_POST['transactionid'] )){

		$fields_string = array ( 'fileid' => $_REQUEST['id']);
		// set content type to fdf, this is because adobe reader cannot process content type of html/text.
		// a work-around to pop up a browser for attachment upload.
		$userAgent = $_SERVER['HTTP_USER_AGENT'];

		if( ! substr_count( $userAgent, 'AcroForms' ) < 1 ) {
			header('Cache-Control: public'); // needed for i.e.
			header("Content-type: application/vnd.fdf");
			$fields_string['pdfType'] = 'Adobe Reader';
		}
		// get the fdf data from pdf form submit
		$XFDFData = file_get_contents('php://input');

		if( strlen($XFDFData) < 1 ){
			showErrorMessage( 'smartPDF ERROR: file content size = 0.' ); exit;
		}
		$fields_string['XFDFData'] = urlencode( $XFDFData );
		$url = BASE_URL."formSubmitService.php";
		$result = pdfService( $url, $fields_string );

		print $result;
	}

	// use upload file attachments, process them.
	else if( $_REQUEST['transactionid'] != '' ){
		$fileid = $_REQUEST['id'] != '' ? $_REQUEST['id'] : $_REQUEST['fileid'];
		$fields_string = array( 'transactionid' => $transactionid, 'fileid' => $fileid );
		if ( $_FILES['file1']['size'] > 0 ){
			$fields_string['file1'] = '@'. $_FILES['file1']['tmp_name'];
			$fields_string['real_file_name'] = urlencode( $_FILES['file1']['name'] );
		}
		$url = BASE_URL."fileService.php";
		$result = pdfService( $url, $fields_string );

		print $result;

	}

}


/**
 * cURL to encrytion/decryption server ...
 *
 * @url 			//url to call
 * @fields_string	//POST vars
 * @return  		//print to stdout
 */
function pdfService( $url, $fields_string){
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );

	$output = curl_exec( $ch );
	$error = curl_error( $ch );
	curl_close( $ch );

	return $output;
}

/**
 * Display error message
 *
 * @message   		// message to print
 * @return          // null
 */
function showErrorMessage( $message ){
	echo <<< END
		<html> <body>
		<div id="errors"> 
END;

	echo $message;

	echo <<< END
		</div>
		</body></html>

END;

}
?>
