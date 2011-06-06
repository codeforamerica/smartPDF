<?php
// change this to the encryption/decryption server
define( 'BASE_URL', 'http://10.250.60.134/wordpress/wp-content/plugins/wp-task-manager/create_pdf.php' );

// change this to point to the correct path
define( 'STORAGE_PATH', '/var/www/html/wordpress/smartPDF/uploads' );

// Database configs, change accordingly
define( 'DB_NAME', 'pdf' );
define( 'TABLE_NAME', 'wp_smartPDF_data' );
define( 'META_TABLE_NAME', 'wp_smartPDF' );
define( 'DB_HOST', 'localhost' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );

// Error code  definitions
define( 'TRANSACTION_ERROR', 	'Failed to complete request, please contact site administrator');
define( 'FILEATTACHMENT_ERROR', 'Failed to attach files to the form, please submit your files manually');
define( 'FILEUPLOAD_ERROR', 	'Failed to upload your form, please contact site administrator');
define( 'FORMSUBMIT_ERROR', 	'Failed to submit form, please submit your form manually');


if( ! $conn )
	$conn = mysql_connect( DB_HOST,DB_USER,DB_PASSWORD ) or die(' Service unavailable, please check back later ');

mysql_select_db( DB_NAME, $conn );

// super secret encryption key
$key = '';

?>
