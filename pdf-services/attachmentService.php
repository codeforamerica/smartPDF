<?php

/***
   Description: Script to handle attachment download requests from form admins.
   Version: 1.0.0
   Author: Henry jiang


  Copyright 2010 Department of Technology, City and County of San Francisco.
  Redistribution and use in source and binary forms, with or without
  modification, are permitted under certain conditions; see the file
  COPYING for details.
 ***/


require_once 'functions.php';

if( isset( $_POST['transactionid'] ) ){
	$transactionid = filter_input( INPUT_POST, 'transactionid', FILTER_SANITIZE_STRING );
	$output = getAttachment( $transactionid, $key );
	if( $conn )
		mysql_close( $conn );
	print $output;
}
?>

