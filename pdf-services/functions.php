<?php 
/***
  Requirements

  1. pear::Crypt_Blowfish 
  2  php ZipArchive

 **/

/***
  Copyright 2010 Department of Technology, City and County of San Francisco.
  Redistribution and use in source and binary forms, with or without
  modification, are permitted under certain conditions; see the file
  COPYING for details.
 ***/

include_once 'config.php';

function getTransactionData( $transactionid, $key, $format ){

	//prepare query
	$query = "SELECT aes_decrypt(SubmittedData, '$key') FROM ". TABLE_NAME ." WHERE TransactionID = '$transactionid';";
	//$output = $wpdb->get_var( $query );
	$result = mysql_query( $query );
	$output = mysql_fetch_row( $result );

	/**
	 * reconstruct the fillable pdf with the fdf to form a complete pdf form.
	 *
	 */
	if( $output[0] ) {
		if(  strtolower( $format == 'csv' ) ){
			//construct csv file from output
		}
		else if( strtolower( $format == 'pdf' ) ){
			//prepare query
			$query = sprintf( "SELECT FileName FROM %s A JOIN %s B ON A.FileID = B.FileID WHERE B.TransactionID = '%s';", META_TABLE_NAME, TABLE_NAME, mysql_real_escape_string($transactionid)) ;

			$result = mysql_query( $query );
			$row = mysql_fetch_row( $result );
			$fileName = $row[0];

			$tmpFileName = tempnam( "/tmp", $transactionid );
			$tmpOutFileName = $tmpFileName . mt_rand();
			$fp = @ fopen( $tmpFileName, "w" );
			if( $fp ) {
				if( @ fwrite( $fp, $output[0] ) ) {
					fclose( $fp );
					$shell_cmd = "pdftk $fileName fill_form $tmpFileName output $tmpOutFileName";
					if( ( $result = shell_exec( $shell_cmd." 2>&1" ) ) != '' )
						return TRANSACTION_ERROR;
					unlink( $tmpFileName );
					$handle = @ fopen( $tmpOutFileName, "r" );
					$htmlcontent = @ fread( $handle, filesize( $tmpOutFileName ) );
					//fpassthru( $handle );
					fclose( $handle );
					unlink( $tmpOutFileName ); 
					return $htmlcontent;
				}
			}
		}
	}
}


function getTransactionID( $length = 20 ) {
	$key = '';
	list($usec, $sec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	//digits + a-z lower and upper case
	$inputs = array_merge(range('z','a'),range(0,9),range('A','Z'));

	for($i=0; $i<$length; $i++)
	{
		$key .= $inputs{mt_rand(0,61)};
	}
	return $key;             
}

function handleFileUpload( $transactionID, $fileid, $filename, $key ){

	require_once 'Crypt/Blowfish.php';

	// create directory to store file attachments
	if ( $_FILES['file1']['error'] > 0 ){
		return FILEUPLOAD_ERROR;
	}
	// if directory already exist, end-user wants to upload more file.
	$rs = true;
	$fileAttachmentPath = STORAGE_PATH."/".$fileid."/".$transactionID;
	if ( ! file_exists( $fileAttachmentPath ) )
		$rs = mkdir( $fileAttachmentPath, 0755, true );

	if( $rs ){
		// encrypt the file with blowfish
		$blowfish = new Crypt_Blowfish( $key );
		$encrypted = $blowfish->encrypt( file_get_contents( $_FILES['file1']['tmp_name'] )  );

		$fp = @ fopen( $fileAttachmentPath."/".$filename, 'wb' );
		if ( $fp && @ fwrite( $fp, $encrypted ) ){	

			if( glob( $fileAttachmentPath."/*" ) != false)
				$fileAttachmentCount = count( glob( $fileAttachmentPath."/*" ) );			 
			$query = sprintf( "UPDATE %s SET FileAttachmentPath='%s', FileAttachmentCount=$fileAttachmentCount WHERE TransactionID='%s';", 
					TABLE_NAME, mysql_real_escape_string($fileAttachmentPath), mysql_real_escape_string($transactionID) );

			if( ! ($result = mysql_query( $query ) ) ){
				error_log( "smartPDF file upload error, failed to run: $query ");
				return FILEUPLOAD_ERROR;
			}
		}
		else	
			return FILEUPLOAD_ERROR;
		@ fclose($fp);
	}else {
		RETURN FILEUPLOAD_ERROR;
	}
}


function getAttachment( $transactionid, $key ) {

	require_once 'Crypt/Blowfish.php';

	// query to get the file attachment path
	$query = sprintf( "SELECT FileAttachmentPath FROM %s WHERE TransactionID='%s' ", TABLE_NAME, $transactionid );

	$result = mysql_query( $query );
	if( $result ){
		$row = mysql_fetch_row( $result );
		$dir = $row[0];
		$files = array();

		// read filenames from the directory
		if (is_dir( $dir )) {
			if ($dh = opendir( $dir )) {
				while (( $file = readdir( $dh )) !== false) {
					if( filetype( $dir ."/". $file ) != 'dir' )
						$files[] = $file;
				}
				closedir($dh);
			}
		}
		// if there are attachments, decode and zip them up for download.
		if( $files ){
			$zip = new ZipArchive;
			$zipfileName = tempnam("/tmp", $transactionid).".zip";
			$res = $zip->open( $zipfileName, ZipArchive::CREATE );
			if ($res === TRUE) {
				foreach( $files as $file ){
					$blowfish = new Crypt_Blowfish( $key );				
					$zip->addFromString( "$file", $blowfish->decrypt( file_get_contents("$dir/$file") ) );		
				}
				$zip->close();

				$contents = file_get_contents( $zipfileName );
				//clean up
				unlink( $zipfileName );
				return $contents;
			}
			else
			{
				error_log("error creating zip file $zipfileName ". $res);
				return FILEATTACHMENT_ERROR;
			}
		}
	}
}

function attachmentUploadForm( $fileid, $transactionid, $hasError = false ){
	$contents = "
		<div id='messageArea'></div>
		<table class=''><tr><td>
		<form id='fileupload' action='' method='post' enctype='multipart/form-data' >
		Company Name: <input  type='text' name='companyName' /><br>
		Upload File: <input type='file' name='file1' /><br>
		";

	$contents .= "<input type=hidden name='fileid' value='$fileid' /><br>
		<input type=hidden name='transactionid' value='$transactionid' /><br>
		<input type='submit' name='submit' value='Submit' />
		</form>
		</td></tr></table>
		";
	if( $hasError)
		$contents = "<div>". FORMSUBMIT_ERROR ." </div>";
	return $contents;
}


function prepareFileAttachment( $transactionID, $db_fileID, $pdfType, $hasError = false ) {

	$contents  = '';
	//for non adobe reader. TODO: take care of adobe professional
	if( $pdfType != 'Adobe Reader' ) {
		$contents = attachmentUploadForm( $db_fileID, $transactionID, $hasError );
	}
	// For adobe reader, please see the template file SubmitPage.pdf for the field names.
	else {
		if( ! $hasError ) {
			$contents .= "%FDF-1.2\n";
			$contents .= "1 0 obj <</FDF <</Fields[<</T(transactionID)/V($transactionID)>>]/ID[<2EAEA478BCD94C5590CC1899E67F193E><98A79EFD20018BAE956CB4428B755E3C>]>>/Type/Catalog>> endobj\n trailer\n";
			$contents .= "<</Root 1 0 R>>\n";
			$contents .= "%%EOF\n";
		}
		// If error submitting, notify user an error occured and do not give option to attach file.
		else {
			$contents .= "%FDF-1.2\n";
			$contents .= "1 0 obj <</FDF <</Fields[<</T(errorMessage)/V(".FORMSUBMIT_ERROR.")>>]/ID[<2EAEA478BCD94C5590CC1899E67F193E><98A79EFD20018BAE956CB4428B755E3C>]>>/Type/Catalog>> endobj\n trailer\n";
			$contents .= "<</Root 1 0 R>>\n";
			$contents .= "%%EOF\n";
		}
	}	
	return $contents;
}

function validIP( $ip ) { 
	if ( ! empty( $ip ) && ip2long( $ip ) != -1 ) {
		$reserved_ips = array (
				array('0.0.0.0','2.255.255.255'),
				array('10.0.0.0','10.255.255.255'),
				array('127.0.0.0','127.255.255.255'),
				array('169.254.0.0','169.254.255.255'),
				array('172.16.0.0','172.31.255.255'),
				array('192.0.2.0','192.0.2.255'),
				array('192.168.0.0','192.168.255.255'),
				array('255.255.255.0','255.255.255.255')
				);
		foreach ( $reserved_ips as $r ) {
			$min = ip2long( $r[0] );
			$max = ip2long( $r[1] );
			if ( ( ip2long( $ip ) >= $min) && (ip2long( $ip ) <= $max) ) return false;
		}
		return true;
	} else {
		return false;
	}
}

function getIP() { 
	if ( validIP( $_SERVER['HTTP_CLIENT_IP']) ) {
		return $_SERVER['HTTP_CLIENT_IP'];
	}	

	foreach ( explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']) as $ip ) {
		if ( validIP( trim( $ip) ) ) {
			return $ip;
		}
	}

	if ( validIP( $_SERVER['HTTP_X_FORWARDED'] ) ) {
		return $_SERVER['HTTP_X_FORWARDED']; 
	} elseif ( validIP( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
		return $_SERVER['HTTP_FORWARDED_FOR']; 
	} elseif ( validIP( $_SERVER['HTTP_FORWARDED'] ) ) {
		return $_SERVER['HTTP_FORWARDED'];
	} elseif ( validIP( $_SERVER['HTTP_X_FORWARDED'] ) ) {
		return $_SERVER['HTTP_X_FORWARDED'];
	} else {
		return $_SERVER['REMOTE_ADDR'];
	}
}

?>
