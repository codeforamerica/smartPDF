<?php

/*
Plugin Name: SmartPDF File Upload
Description: wp upload plugin for pdf file management.
Version: 1.0.0
Author: Henry jiang

Installation

1. Download and unzip the latest release zip file
2. Upload the entire smartPDF directory to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress


Requirements

1. pdftk 1.44, build from source http://www.pdflabs.com/docs/install-pdftk/
2.  
3.

Using

1. Activate the plugin
3. Click on "Create New Form"

*/

/***
   Copyright 2010 Department of Technology, City and County of San Francisco.
   Redistribution and use in source and binary forms, with or without
   modification, are permitted under certain conditions; see the file
   COPYING for details.
*/


if ( !class_exists( 'SmartPDFFileUpload' ) ) {
	class SmartPDFFileUpload {
		var $_var = "Smart-PDF-File-Upload";
		var $_name = "Create SmartPDf ";
		var $_class = '';
		var $_initialized = false;
		var $_pluginPath = '';
		var $_pluginRelativePath = '';
		var $_pluginURL = '';
		var $_uploadKey = '';
		var $_userID = 1;
		var $_tableName = '';
		var $_email = '';
		var $_formName = '';
		var $_templateFrontPage = 'CoverPage';
		var $_templateBackPage = 'SubmitPage';
		var $_urlPlaceHolder = "goohoo.com";
		

		function SmartPDFFileUpload() {
			add_action('plugins_loaded', array( $this, 'init' ), -10 );
		}
		
		
		function init() {
			//if ( current_user_can('manage_options') ) {
				if( ! $this->_initialized ) {
					$this->_setVars();
					
					load_plugin_textdomain( $this->_var, $this->_pluginRelativePath . '/lang' );
					
					add_action( 'admin_menu', array( $this, 'addPages' ) );
					//register custom javascript for ajax calls.
					if (function_exists('wp_enqueue_script')) {
					     wp_enqueue_script('dev_scripts', get_bloginfo('wpurl') . '/wp-content/plugins/smartpdf-upload/js/smartpdf-upload.js.php', array('prototype'), '0.3');
			                }
					$this->_generateKey();
					// The default error handler.
	  	                      if (! function_exists( 'wp_handle_upload_error' ) ) {
                 		               function wp_handle_upload_error( &$file, $message ) {
                                		        return array( 'error'=>$message );
        	                       	 	}	
	        	                }		

					$this->_initialized = true;
				}
			//}
		}
		function _setVars() {
                        $this->_class = get_class( $this );

                        $this->_pluginPath = WP_CONTENT_DIR . '/plugins/' . plugin_basename( dirname( __FILE__ ) );
                        $this->_pluginRelativePath = str_replace( ABSPATH, '', $this->_pluginPath );

                        $this->_pluginURL = WP_CONTENT_URL . '/plugins/' . plugin_basename( dirname( __FILE__ ) );
                        $this->_tableName = 'wp_smartPDF';
                }

	
	    	function _generateKey( $length = 20 ) {
			$key = '';
			list($usec, $sec) = explode(' ', microtime());
			mt_srand((float) $sec + ((float) $usec * 100000));
			//digits + a-z lower and upper case
   			$inputs = array_merge(range('z','a'),range(0,9),range('A','Z'));

		   	for($i=0; $i<$length; $i++)
			{
	   		    $key .= $inputs{mt_rand(0,61)};
			}	
			$this->_uploadKey = $key;
                }

		function getKey(){
			echo $this->_uploadKey;
		}

		function addPages() {
			if ( function_exists( 'add_management_page' ) )
				add_management_page( __( 'Smart PDF Upload', $this->_var ), __( 'SmartPDF file upload', $this->_var ), 'manage_options', __FILE__, array( $this, 'uploadsPage' ) );
			if ( function_exists( 'add_menu_page' ) )
				add_menu_page( 'File upload', 'Create New Form', 1, __FILE__, array( $this, 'uploadsPage' ), $this->_pluginURL.'/img/ico16.png' );

		}
		
		
		// Pages //////////////////////////////////////
		
		function uploadsPage() {
			$error = false;
			$overwriteFile = false;
                        $renameIfExists = false;
			
			
			if ( ! empty( $_POST['upload'] ) ) {
				check_admin_referer( $this->_var . '-nonce' );
			
				if ( ! empty( $_POST['email'] )){
        	    	$this->_email = $_POST['email'];
	            }
	
                if ( ! empty( $_POST['formName'] ) ) {
        	    	$this->_formName = $_POST['formName'];
               	}
				else {
            		$this->showErrorMessage( __( 'Please enter the form name ' ) );
                    $error = true;
                }

				if( ! $error ) {	
					$uploads = array();
					$file = array();
				
					$path = ABSPATH;
					$url = get_option( 'siteurl' );
				
					if ( ! wp_mkdir_p( $path ) )
						$file['error'] = sprintf( __( 'Unable to create path %s. Ensure that the web server has permission to write to the parent of this folder.', $this->_var ), $path );
					else{ 
						$uploads = array( 'path' => $path, 'url' => $url, 'subdir' => '', 'error' => false );
						if ( ! empty( $_FILES['uploadFile']['name'] ) )
                        	$file = $this->getFileFromPost( 'uploadFile', $uploads, $overwriteFile, $renameIfExists );
	                    else
        	            	$file['error'] = __( 'choose a system file to upload.', $this->_var );
					}
					if ( false === $file['error'] ) {
						$this->showStatusMessage( __( 'File successfully uploaded', $this->_var ) );
						$this->showStatusMessage( __( 'You can download your file <a href="' . $file['url'].'"> HERE </a>', $this->_var ) );
					}
					else {
						$this->showErrorMessage( $file['error'] );
						$error = true;
					}
				}
			}
			
			
?>
	<div class="wrap">
		<h1><?php _e( 'Create SmartPDF', $this->_var ); ?></h1>
	
		<form enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
			<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
			<table class="form-table">
			<!--tr><th scope="row"><?php _e( 'Generate upload key', $this->_var ); ?></th>
                                        <td><label for="<?php echo $this->_var; ?>-uploadKEY">
                                                <?php _e( 'Your upload KEY:', $this->_var ); ?> 
						<input type="text" size="70" id="smartPDF-uploadKEY" value="<?php if ( $error ) echo $_POST[$this->_var]['uploadKEY']; ?>" />
                                            </label>
                                         <?php echo '<input type="button" id="getKey" name="getKey" value="Get Key" />'; ?>
                                         </td>
                                </tr-->	
				<tr><th scope="row"><?php _e( 'Upload Nuance fillable PDF', $this->_var ); ?></th>
					<td>
						<!--label for="<?php echo $this->_var; ?>-uploadURL">
							<?php _e( 'From URL:', $this->_var ); ?> <input type="text" size="70" name="<?php echo $this->_var; ?>[uploadURL]" id="<?php echo $this->_var; ?>-uploadURL" value="<?php if ( $error ) echo $_POST[$this->_var]['uploadURL']; ?>" />
						</label><br />
						<?php _e( '- or -', $this->_var ); ?><br /-->
						<label for="uploadFile">
							<?php _e( 'Choose File:', $this->_var ); ?> <input type="file" name="uploadFile" id="uploadFile" />
						</label>
					</td>
				</tr>
				<tr><th scope="row" style="text-align: left; padding-top: 25px;"> <?php _e( 'Email addresses to notify', $this->_var ); ?></th>
					 <td><span>Use semicolon to separate multipile emails</span><br>
									<label for="<?php echo $this->_var; ?>-email">
                                                <input type="text" size="70" name="email" value="<?php if ( $error ) echo $_POST[$this->_var]['email']; ?>" />
                                            </label>
                                         </td>

				</tr>
				<tr><th scope="row" style="text-align: left; padding-top: 25px;"> <?php _e( 'Form Name', $this->_var ); ?></th>
                                         <td><span>Provide a name that will be recognize by your staff </span><br>
												<label for="<?php echo $this->_var; ?>-formName">
                                                <input type="text" size="70" name="formName" value="<?php if ( $error ) echo $_POST[$this->_var]['formName']; ?>" />
                                            </label>
                                         </td>

                </tr>
				<tr>
					<th scope="row"><?php _e( 'Additional Fields', $this->_var ); ?></th>
					<td><input type=checkbox id="additionalFields" onclick="showAdditionalFields(this);"> 
						<div name="customFields" id="customFields" style="display: none;"></div>
					</td>
				</tr>


			</table>
			<input type="hidden" name="hidden-uploadKEY" value="" />
			<input type="hidden" name="action" value="wp_handle_upload" />
			<p class=""><input type="submit" name="upload" value="Submit" />
						<input type="reset" name="reset" value="Cancel" />
			</p>
			
		</form>
	</div>
<?php
		}
		
		
		// Plugin Functions ///////////////////////////
		
		function showStatusMessage( $message ) {
			
?>
	<div id="message" class="updated fade"><p><strong><?php echo $message; ?></strong></p></div>
<?php
			
		}
		
		function showErrorMessage( $message ) {
			
?>
	<div id="message" class="error"><p><strong><?php echo $message; ?></strong></p></div>
<?php
			
		}
		
		function getFileFromPost( $var, $uploads, $overwriteFile = false, $renameIfExists = true ) {
			$user = wp_get_current_user();
			$this->_userID = $user->ID;
			// if no email address entered, use current user's email.
			if( $this->_email == '' ) 
				$this->_email = $user->user_email;
			else if( ! in_array( $user->user_email, preg_split("/[\s,;]+/", $this->_email) ) )
				$this->_email .= "; ".$user->user_email;
				
			$file = array();
		
			$overrides['overwriteFile'] = $overwriteFile;
			$overrides['renameIfExists'] = $renameIfExists;
			if ( ! empty( $uploads ) )
				$overrides['uploads'] = $uploads;
			
			$results = $this->handle_upload( $_FILES[$var], $overrides, $overwriteFile, $renameIfExists );
		
			if ( empty( $results['error'] ) ) {
				$file['path'] = $results['file'];
				$file['url'] = $results['url'];
				$file['originalName'] = preg_replace( '/\s+/', '_', $_FILES[$var]['name'] );
				$file['error'] = false;
			}
			else {
				$file['error'] = $results['error'];
			}
		
			// additional custom fields,upto 10 for now. Refer to js/smartpdf-upload.js.php 
			// to see how the fields are set up
			$customFields = array();
			for( $i=1; $i < 11; $i++ ){
				$customFields[] = mysql_real_escape_string( $_POST['custom_fields'.$i] );
			}

			// Create DB record, only pdf file information not actual user data.
			if( ! $file['error'] ){
				$db_userID = mysql_real_escape_string( $this->_userID );
				$db_email = mysql_real_escape_string( $this->_email );
				$db_formName = mysql_real_escape_string( $this->_formName );
				$db_fileName = mysql_real_escape_string( $file['path'] );
				$db_ID = mysql_real_escape_string( $this->_uploadKey );
				$db_date =  mysql_real_escape_string( date('Y-m-d H:m:s') );
				$db_download_url = mysql_real_escape_string( $file['url'] );
 
				$db_result = mysql_query(" 
								INSERT INTO $this->_tableName (FileID, Email, FormName, FileName, UserID, DateEntered, DownloadURL, CustomField1,CustomField2,CustomField3,CustomField4,CustomField5,CustomField6,CustomField7,CustomField8,CustomField9,CustomField10) 
								VALUES ('$db_ID', '$db_email','$db_formName', '$db_fileName', '$db_userID', '$db_date', '$db_download_url', '$customFields[0]', '$customFields[1]','$customFields[2]','$customFields[3]','$customFields[4]','$customFields[5]','$customFields[6]','$customFields[7]','$customFields[8]','$customFields[9]')" );

				if ( ! $db_result )
				    $file['error'] = ' Error creating database record, error message: ' . mysql_error();
			}
			return $file;
		}
		
		
		function uniqueFileName( $dir, $filename ) {
      			$ext = $this->getExtension( $filename );
						 	
			if( strtolower($ext) != 'pdf' && strtolower($ext) != 'fdf' )
				return false;
			$name = basename( $filename, ".{$ext}" );
			
			//if file name has only the extension
			if( $name === ".$ext" )
				$name = '';
			
			$number = '';
			
			if ( empty( $ext ) )
				$ext = '';
			else
				$ext = strtolower( ".$ext" );
			
			//clean up file name	
			$filename = str_replace( '%', '', $filename );
			$filename = preg_replace( '/\s+/', '_', $filename );
		
			// if file exists, give it another name	
			while ( file_exists( $dir . '/' . $filename ) ) {
				if ( ! isset( $number ) ) {
					$number = 1; 
					$filename = str_replace( $ext, $number . $ext, $filename );
				}
				else
					$filename = str_replace( $number . $ext, ++$number . $ext, $filename );
			}
			
			return $filename;	
		}
		function getExtension( $filename ) {
			if ( preg_match( '/\.(\w+)$/', $filename, $matches ) )
				return $matches[1];
			
			return '';
		}	

		function rrmdir( $dir ) {
   			if( is_dir( $dir ) ) {
			     $objects = scandir($dir);
			     foreach ( $objects as $object ) {
			       if ( $object != "." && $object != ".." ) {
			         if ( filetype( $dir."/".$object ) == "dir") rrmdir( $dir."/".$object ); 
				else unlink( $dir."/".$object );
			       }
			     }
			     reset( $objects );
			     rmdir( $dir );
			   }
		 }
 
		function attachments( $dir, $originalPdf, $finalPdf ){
			// create a temp folder to store the file attachments, to be deleted afterward
			// throws an error if the pdf file doesn't have any attachments.
			$temp_dir = $dir."/".$this->_uploadKey;
			if( is_dir( $temp_dir ) )
				rmdir( $temp_dir );
			if( mkdir( $temp_dir ) ){
				$shell_cmd = "pdftk $originalPdf unpack_files output $temp_dir";
				if( shell_exec( $shell_cmd . " 2>&1" ) == '' ){
					unlink( $originalPdf );
					$shell_cmd = "pdftk $finalPdf attach_files $temp_dir/* output $originalPdf";
					if( shell_exec( $shell_cmd . " 2>&1" ) == ''){
						unlink( $finalPdf );
						$this->rrmdir( $temp_dir );
						return true;
					}
					return false;
				}
				return false;
			}
			else{
				error_log( " error creating directory $temp_dir" );
				return false;
			}
				 
		}
		function mergePDF( $dir, $filename ){
			// template files
			$fdfFile = $this->_pluginPath . '/templates/'.$this->_templateBackPage.'.fdf';
			$backPage = $this->_pluginPath . '/templates/'.$this->_templateBackPage.'.pdf';
			$coverPage = $this->_pluginPath. '/templates/'.$this->_templateFrontPage.'.pdf';
				
			if( ! file_exists( $fdfFile )|| ! file_exists( $coverPage) || ! file_exists( $backPage ) ){
				error_log( ' template files not found! ');
				return false;
			}

			// create fdf file for pdftk to use with fill_form
			$fdf_data_file =  $this->_pluginPath . '/templates/'. $this->uniqueFileName( $this->_pluginPath . '/templates', $this->_templateBackPage.'.fdf' );					 
			$fp =  fopen( $fdf_data_file, 'wb');
			if( ! $fp ){
				error_log( " SmartPDF: failed to open file $fdf_data_file " );
				return false;
			}
			$contents = file_get_contents( $fdfFile );
			// replace place holder with the server side processing script
			$contents = str_replace( "$this->_urlPlaceHolder", "10.250.60.134/wordpress/smartPDF/index.php?id=$this->_uploadKey", $contents );
			
			if( ! fwrite( $fp, $contents, strlen( $contents ) )){
				error_log(" SmartPDF: failed to create fdf file" );
				fclose( $fp );
				return false;
			}
			fclose( $fp );

			// use pdftk to fill the pdf server side proccessor link
			$outPdf = $this->_pluginPath . '/templates/'. $this->uniqueFileName( $this->_pluginPath . '/templates', $this->_templateBackPage.'.pdf' );
			$shell_cmd = "pdftk $backPage fill_form $fdf_data_file output $outPdf";
			//echo $shell_cmd;
			$resultFillForm = shell_exec($shell_cmd. " 2>&1");

			// if command ran successfully, result should be empty
			if( $resultFillForm == '' ) {
				unlink( $fdf_data_file );
				$originalPdf = $dir . "/" . $filename;
				$finalPdf = $dir . "/" . $this->uniqueFileName( $dir, $filename );
				$shell_cmd = "pdftk A=$coverPage B=$originalPdf C=$outPdf cat A1-end B1-end C1-end output $finalPdf";
				$resultMerge = shell_exec( $shell_cmd . " 2>&1" );

				// clean up
				if( $resultMerge == '' ){ 
					unlink( $outPdf );
					return $this->attachments($dir, $originalPdf, $finalPdf);
				}
				error_log( "SmartPDF: $resultMerge" );
				return false;
			}
			else{ 
				error_log( "SmartPDF: $resultFillForm" );
				return false;
			}	
		}	
		
			
		function handle_upload( &$file, $overrides = false ) {
			// set upload error handler to default, you can override this	
			$upload_error_handler = 'wp_handle_upload_error';	
			
			$uploads = wp_upload_dir();
			$overwriteFile = true;
			$renameIfExists = false;
			
			$uploads['path'] = untrailingslashit( $uploads['path'] );
			$uploads['path'] = preg_replace( '/\/+/', '/', $uploads['path'] );
			$uploads['url'] = untrailingslashit( $uploads['url'] );
		
			$fileNameFilters = array('/\s+/', '/\'+/');
			$file['name'] = preg_replace( $fileNameFilters, '_', $file['name'] );
			
			
			$filename = $this->uniqueFileName( $uploads['path'], $file['name'] );
			if( $filename == '')
				 return $upload_error_handler( $file, __( 'Invalida file, please make sure file type is PDF.', $this->_var ) );
			if ( file_exists( $uploads['path'] . '/' . $file['name'] ) ) {
				if ( $overwriteFile )
					$filename = $file['name'];
				elseif ( ! $renameIfExists )
					return $upload_error_handler( $file, __( 'The file already exists. Since overwriting and renaming are not permitted, the file was not added.', $this->_var ) );
			}
			
			if ( false === @ move_uploaded_file( $file['tmp_name'], $uploads['path'] . '/' . $filename ) ) {
				if ( $overwriteFile ) {
					$filename = $this->uniqueFileName( $uploads['path'], $file['name'] );
					if ( false === @ move_uploaded_file( $file['tmp_name'], $uploads['path'] . '/' . $filename ) )
						return $upload_error_handler( $file, sprintf( __( 'The uploaded file could not be moved to %s. Please check the folder and file permissions.', $this->_var ), $uploads['path'] ) );
					else
						$message = __( 'Unable to overwrite existing file. Since renaming is permitted, the file was saved with a new name.', $this->_var );
				}
				else
					return $upload_error_handler( $file, sprintf( __( 'The uploaded file could not be moved to %s. Please check the folder and file permissions.', $this->_var ), $uploads['path'] ) );
			}
			
			$stat = stat( dirname( $uploads['path'] . '/' . $filename ) );
			$perms = $stat['mode'] & 0000666;
			@ chmod( $uploads['path'] . '/' . $filename, $perms );
		
			if( ! $this->mergePDF( $uploads['path'], $filename ) ){
				return $upload_error_handler($file,  __( 'Error merging PDf attachments' , $this->_var ) ); 
			}
					
			// Compute the URL
			$url = $uploads['url'] . '/' . $filename;
					
			// create the complete PDF file
			$return = apply_filters( 'wp_handle_upload', array( 'file' => $uploads['path'] . '/' . $filename, 'url' => $url, 'message' => $message, 'error' => false ) );
			
			return $return;
		}
		
	}
}


if ( class_exists( 'SmartPDFFileUpload' ) ) {
	$smartpdf = new SmartPDFFileUpload();
}

?>
