<?php 
/*
   Description: script to handle end user form submission.
   Version: 1.0.0
   Author: Henry jiang

   Requirements:
   1.) pear::Crypt_Blowfish
# pear install Crypt_Blowfish

 */

/***
  Copyright 2010 Department of Technology, City and County of San Francisco.
  Redistribution and use in source and binary forms, with or without
  modification, are permitted under certain conditions; see the file
  COPYING for details.
 */


require_once 'functions.php';


$fileid 	=  filter_input( INPUT_POST, 'fileid', FILTER_SANITIZE_STRING );
$pdfType 	=  filter_input( INPUT_POST, 'pdfType', FILTER_SANITIZE_STRING );
$XFDFData	=  urldecode( $_REQUEST['XFDFData'] );

// insert record into database with sensitive data encrypted.
$db_date =  mysql_real_escape_string( date('Y-m-d H:m:s') );
$db_sumitted =  mysql_real_escape_string( $XFDFData );
$db_sourceIP = mysql_real_escape_string( getIP() );
$db_transactionID = mysql_real_escape_string( getTransactionID() );
$db_fileID = mysql_real_escape_string( $fileid );

$sql = "INSERT into ". TABLE_NAME ."( TransactionID, FileID, SubmittedData, SourceIP, TimeStamp) VALUES ('$db_transactionID', '$db_fileID', AES_ENCRYPT('$db_sumitted', '$key'), '$db_sourceIP','$db_date');";


//if inserted successfully, result should have the number of records inserted.
$result = mysql_query( $sql );

$hasError = false;
if( ! $result ) {
	$hasError = true;
}

// create file upload form
$result = prepareFileAttachment( $db_transactionID, $db_fileID, $pdfType, $hasError);
print $result;

if( $conn )
	mysql_close( $conn );

?>
