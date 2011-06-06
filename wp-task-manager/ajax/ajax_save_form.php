<?php
	require_once '../../../../wp-config.php';
	require_once '../constant.php';
	
	global $wpdb;
	
	$id = filter_input( INPUT_GET, 'fileid', FILTER_SANITIZE_STRING );
	$value = filter_input( INPUT_GET, 'value', FILTER_SANITIZE_STRING );
	$dbfield = filter_input( INPUT_GET, 'dbfield', FILTER_SANITIZE_STRING );
	$type = filter_input( INPUT_GET, 'type', FILTER_SANITIZE_STRING );

	
	$table = $wpdb->prefix."smartPDF";

	if(	$type == "form" ){
		if( strpos( $dbfield, 'emails' ) > -1 )
			$wpdb->update( $table, array( 'Email' => $value ), array( 'FileID' => $id ) );
		else if( strpos( $dbfield, 'formname' ) > -1 )
			$wpdb->update( $table, array( 'FormName' => $value ), array( 'FileID' => $id ) );
		else if( strpos( $dbfield, 'delete' ) > -1 )
			$wpdb->update( $table, array( 'FormStatus' => "DELETED" ), array( 'FileID' => $id ) );
	}
	else if( $type == "transaction" ) {
		$table .= "_data";
		if( strpos( $dbfield, 'select' ) > -1 )
                $wpdb->update( $table, array( 'Status' => $value), array( 'TransactionID' => $id ) );
        else if( strpos( $dbfield, 'notes' ) > -1 )
                $wpdb->update( $table, array( 'Notes' => $value), array( 'TransactionID' => $id ) );
	}
