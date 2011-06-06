<?php
/*
Plugin Name: WP-Task-Manager
Description: smartPDF form management tool.
Author: Henry Jiang
Version: 1.0
*/

/***
  Copyright 2010 Department of Technology, City and County of San Francisco.
  Redistribution and use in source and binary forms, with or without
  modification, are permitted under certain conditions; see the file
  COPYING for details.
***/

require_once 'constant.php';

$docRoot = split("/", $_SERVER['PHP_SELF']);

$task_plugIn_base_url='http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?page='.plugin_basename (__FILE__);

$smartpdf_service_url='http://'.$_SERVER['HTTP_HOST']."/".$docRoot[1]."/smartPDF/";



//---------------------------------------------------------------------------
register_activation_hook(__FILE__, 'wp_task_manager_install');
register_deactivation_hook(__FILE__, 'wp_task_manager_uninstall');
//---------------------------------------------------------------------------
add_action('admin_menu', 'wp_task_manager_create_menu');
add_action('wp_dashboard_setup', 'wp_task_manager_init_dashboard_widget');
add_action('admin_menu', 'remove_menus');
/*** hide upgrade now message **/
add_action( 'admin_notices', 'hide_update_notice', 1 );

/*** hide "NEW POST" button on top right ***/
add_filter('favorite_actions', 'hide_favorite_actions_menu');
//---------------------------------------------------------------------------	


	function wp_task_manager_create_menu(){
		global $current_user;

		wp_enqueue_style( 'myStyleSheets',plugins_url('/css/custom.css', __FILE__));

		wp_enqueue_style( 'flexgridStyleSheets',plugins_url('/css/flexigrid.pack.css', __FILE__));
		wp_enqueue_script('flexgrid', plugins_url('/js/flexigrid.js', __FILE__), array('jquery'));


		add_menu_page( 'Inbox', 'Inbox', 1, __FILE__, 'wp_task_manager_page_dispatcher', IMG_DIRECTORY.'ico16.png');
		add_menu_page( 'Manage Forms', 'Manage Forms', 1, "pdf_form_manager", 'wp_task_manager_page_form_manager', IMG_DIRECTORY.'ico16.png');
	}

	//add Dashboard Widget via ajax_function wp_add_dashboard_widget()
	function wp_task_manager_init_dashboard_widget() {
		wp_add_dashboard_widget( 'wp_dashboard_my_task_manager', __( 'Inbox' ), 'wp_task_manager_dashboard_widget' );
		$widget_header_html = "<table class=\"widget_table\"><thead><tr><th>Form</th><th>New Forms</th><th>Recived Today</th><th>Most Recent</th><th>Oldest</th></tr></thead></table>";
		wp_add_dashboard_widget( 'wp_dashboard_my_task1_manager', __("$widget_header_html"), 'wp_task_manager_dashboard_widget_1' );
		example_remove_dashboard_widgets();
		
	}

	function remove_menus () {
		global $menu, $rank, $user_login;
		if( "admin" != $user_login ){
			$restricted = array(__('Posts'), __('Media'), __('Links'), __('Pages'), __('Appearance'), __('Tools'), __('Users'), __('Plugins'),  __('Settings'), __('Comments'),  __('Profile') );
			end ($menu);
			while (prev($menu)){
				$value = explode(' ',$menu[key($menu)][0]);
				if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
			}
		}
	}

	function hide_update_notice() {
		 global $user_login , $user_email;
		 get_currentuserinfo();
		 if ($user_login != "admin") {
			 remove_action( 'admin_notices', 'update_nag', 3 );
	 	}	
	}

	function hide_favorite_actions_menu($actions) {
    	global $user_login , $user_email;
        get_currentuserinfo();
        if ($user_login != "admin") {
			$actions = array();
		    return $actions;
		}
	}

	function example_remove_dashboard_widgets() {
		// Globalize the metaboxes array, this holds all the widgets for wp-admin
	 	global $wp_meta_boxes;

		// Remove the incomming links widget
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);	

		// Remove right now
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);

		// Remove side column
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_recent_drafts']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
	}

	
	function wp_task_manager_page_dispatcher(){
		if ( isset( $_POST['task'] ) ) 
			$_GET['task']=$_POST['task'];

		$task = filter_input( INPUT_GET,'task',FILTER_SANITIZE_STRING );	

		switch ($task) {
			default:
			case 'all':
				wp_task_manager_view_all_task();
				break;
	    		
			case 'new':
				wp_task_manager_page_new_task();
				break;
		}	
	} 

    // seconde section of the dashboard	
	function wp_task_manager_dashboard_widget_1() {
		global $wpdb,$current_user,$task_plugIn_base_url,$rank;

		$table1 = $wpdb->prefix."smartPDF";
		$table2 = $wpdb->prefix."smartPDF_data";
		$query = "SELECT count(a.FormName) as counts, a.FormName as formType, b.status as Status, b.FileID as fileid
			      FROM $table1 a, $table2 b 
				  WHERE a.fileid = b.fileid AND  locate('$current_user->user_email', email) > 0 
				  GROUP BY a.formname, b.status;";

		$ListForms = $wpdb->get_results( $query, ARRAY_A );

		if( $ListForms ){
			echo "<table class=\"widget_table\"><tbody>";
			foreach( $ListForms as $forms){
				$forms['Status'] = $forms['Status'] == 'NEW' ? $forms['Status'] : ($forms['Status'] == 'OPEN' ? 'IN PROGRESS' : 'COMPLETED');
				$today 	= date('Y-m-d');
				$query 	= "SELECT TimeStamp FROM $table2 t WHERE FileID = '". $forms['fileid']. "' ORDER BY TimeStamp DESC;";
				$ListDates 	= $wpdb->get_results( $query, ARRAY_A );
				$MostRecent = mysql2date( 'Y-m-d', $ListDates[0]['TimeStamp'] );
				$Oldest  	= mysql2date( 'Y-m-d', $ListDates[count($ListDates) - 1]['TimeStamp'] );
				$TodaysCount = 0;
				foreach( $ListDates as $Dates ){
					if( $today == mysql2date( 'Y-m-d', $Dates['TimeStamp'] ) )
						$TodaysCount++;
				}
				echo "<tr><td>".$forms['formType']."</td> <td>".$forms['counts']."</td><td>$TodaysCount</td><td>$MostRecent</td><td>$Oldest</td></tr>";
			}
			echo "</tbody></table>";
		}
	
	}
	//Content of Dashboard-Widget
	function wp_task_manager_dashboard_widget() {

		global $wpdb,$current_user,$task_plugIn_base_url,$rank;
		
		$table1 = $wpdb->prefix."smartPDF";
	 	$table2 = $wpdb->prefix."smartPDF_data";
		$query = "SELECT count(transactionid) as counts, status FROM $table1 a JOIN $table2 b ON a.fileid = b.fileid 
					WHERE locate('$current_user->user_email', email) > 0 GROUP BY status;";

        $ListForms = $wpdb->get_results( $query, ARRAY_A);
		echo "<table><tr>";	
                if( $ListForms ){
			echo "<td>";
			echo "<label> Current Status </label><hr><br>";
			foreach( $ListForms as $forms){
				$forms['status'] = $forms['status'] == 'NEW' ? $forms['status'] : ($forms['status'] == 'OPEN' ? 'IN PROGRESS' : 'COMPLETED');
				echo $forms['counts'] . " " .$forms['status']. " <br><br> ";
			}

			echo "</td><td width='30%'></td>";
                }
	

		$query1 = "SELECT COUNT(transactionid) as counts, status
                                FROM $table GROUP BY status;";

                $ListForms1 = $wpdb->get_results( $query1, ARRAY_A);

                if( $ListForms1 ){
			/*echo "<td>";
			echo "<label>Submissions by Time  </label><hr><br>";
                        foreach( $ListForms1 as $forms){
                                echo $forms['counts'] . " " .$forms['status']. " <br><br> ";
                        }
			echo "</td>";
			*/
                }
		echo "</tr></table>";
	
	}

	function wp_task_manager_page_form_manager(){
		global $rank,$task_plugIn_base_url,$current_user, $wpdb;

		$table = $wpdb->prefix."smartPDF";
                
        $query = "SELECT distinct FormName, Email FROM $table WHERE FormStatus = 'ACTIVE';";
 
		$formList = $wpdb->get_results( $query, ARRAY_A );

        $filterHtml = "<select id='wptm_form_manager'>";
        $filterHtml .= "<option value=0> Form - All </option>";
        if( $formList ){
        	foreach( $formList as $form ){
				$emails = preg_split( "/[\s,;]+/", $form['Email'] );
				if( in_array( $current_user->user_email, $emails ) ) 
	            	$filterHtml .= "<option value=" . $form['FormName']. ">Form ". $form['FormName']." </option>";
             }
        }
		
        $filterHtml .= "</select>";
		?>
		<script type="text/javascript" src="<?php echo JS_DIRECTORY; ?>jquery.tablesorter.js"></script>
		
		<h1>Form Management</h1>
                 <p><u><b>View:</b></u>&nbsp;&nbsp;<a href="#"><span id='wptm_view_form_active' style="color: none; text-decoration: none; font-weight: bold; font-size: 16px; background-color: lightblue">Active</span></a>
		&nbsp;&nbsp; <a href="#"><span id='wptm_view_form_archived' >Archived</span></a>
		&nbsp;&nbsp; <span> <?php echo $filterHtml; ?> </span></p>

		<table id="wptm_form_manager_table"> </table>
	 <script>
        var formType = '0';
		var view = 'active';		
		var cssObj_active = {"color" : "", "text-decoration" : "none", "font-weight" : "bold", "font-size": "16px", "background-color" : "lightblue"};
        var cssObj_inactive = {"color" : "", "text-decoration" : "none", "font-weight" : "normal", "font-size": "12px", "background-color" : ""};

		
        jQuery(document).ready(function () {
             getForm();
        });

		function getForm() {
			jQuery('#wptm_form_manager_table').flexigrid({
				url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_form_manager.php',
				dataType: 'json',
				colModel : [
				{display: 'DATE', name : 'DateEntered', width : 205, sortable : true, align: 'left'},
				{display: 'FORM', name : 'FormName', width : 70, sortable : true, align: 'left'},
				{display: 'FILE NAME', name : 'FileName', width : 100, sortable : true, align: 'left'},
				{display: '# NEW / OPEN', name : 'newCount', width : 50, sortable : true, align: 'left'},
				{display: 'EMAILS', name : 'Email', width: 300, sortable : true, align : 'left'},
				{display: 'ACTION', name : 'Notes', width: 320, sortable : true, align : 'left'} 
				],
				searchitems : [
				{display: 'EMAIL', name : 'Email'},
				{display: 'FORM NAME', name : 'FormName', isdefault: true}
				],
				view: view,
				formType: formType,
				sortname: 'DateEntered',
				sortorder: 'desc',
				usepager: true,
				title: ' ',
				useRp: true,
				rp: 10,
				resizable: true,
				width: 1100,
				height: 370,
				singleSelect: true
			});

        }

        jQuery("#wptm_form_manager").change( function () {
        	formType = jQuery(this).val();
			jQuery('#wptm_form_manager_table').flexReload({
				url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_form_manager.php',
				formType: formType,
				page: 1
			});

        });

		jQuery("#wptm_view_form_active").click( function () {
			jQuery("#wptm_view_form_active").css(cssObj_active);
			jQuery("#wptm_view_form_archived").css(cssObj_inactive);
			view = "active";
		 	jQuery('#wptm_form_manager_table').flexReload({
				url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_form_manager.php',
				view: view,
				page: 1
			});

		});
		jQuery("#wptm_view_form_archived").click( function () {
			jQuery("#wptm_view_form_active").css(cssObj_inactive);
            jQuery("#wptm_view_form_archived").css(cssObj_active);
    	    view = "archived";
			jQuery('#wptm_form_manager_table').flexReload({
				url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_form_manager.php',
				view: view,
				page: 1
	        });
		});

		function saveFormClick(thisField, id) {
			var value = jQuery.trim(thisField.value);
			var fileid = jQuery.trim(id);
			var fieldname = jQuery.trim(thisField.name);

			jQuery.get("../wp-content/plugins/wp-task-manager/ajax/ajax_save_form.php", { fileid: fileid, value: value, dbfield: fieldname, type: "form" },
					function(data){
					//getForm();
					 jQuery('#wptm_form_manager_table').flexReload({
		                 url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_form_manager.php',
		             });

					});
		}

		function saveFromText(thisField, id) {
			var isValidEmails = true;
			var value = jQuery.trim(thisField.value);
			var fileid = jQuery.trim(id);
			var fieldname = jQuery.trim(thisField.name);

			if( thisField.name.indexOf("emails") > -1 ){
				isValidEmails = validateEmails(thisField.value);
			}
			if( isValidEmails ){
				jQuery.get("../wp-content/plugins/wp-task-manager/ajax/ajax_save_form.php", { fileid: fileid, value: value, dbfield: fieldname, type: "form" },
						function(data){
						//getForm();
						 jQuery('#wptm_form_manager_table').flexReload({
						 	url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_form_manager.php',
						});
					});
			}
			else
				return false;
		}

		function validateEmails(emails){
			return true;
		}

		function setbg( id ){
			jQuery("#wptm_form_manager_table tr").css("background-color", "white");
			jQuery("#"+id).css("background-color", "lightgray");
		}


	</script>
	<?

	}
	
	function wp_task_manager_view_all_task(){
		global $rank,$task_plugIn_base_url,$current_user, $wpdb;
		//wp_enqueue_script('sortable', JS_DIRECTORY.'jquery.tablesorter.js', array('jquery'));
		
		$table = $wpdb->prefix."smartPDF";
		$query = "SELECT distinct FormName FROM $table;";

		$formList = $wpdb->get_results( $query, ARRAY_A );
		
		$filterHtml = "<select id='wptm_form_filter'>";
		$filterHtml .= "<option value=0> Form Name - All</option>";
		if( $formList ){
			foreach( $formList as $form ){
				$filterHtml .= "<option value=" . $form['FormName']. ">Form ". $form['FormName']." </option>";	
			} 
		}
		
		$filterHtml .= "</select>";

		?>		
	<style>#tooltip{position:absolute;border:1px solid #333;background:#f7f5d1;padding:2px 5px;color:#333;display:none;}</style>
	<script type="text/javascript" src="<?php echo JS_DIRECTORY; ?>jquery.tablesorter.js"></script>


	<div>
		<h1>Inbox</h1>
		<p id="wptm_msg"></p>
		<p><u><b>View:</b></u>&nbsp;&nbsp;<a href="#"><span id='wptm_view_all'>All</span><a/>&nbsp;&nbsp;<a href="#"><span id='wptm_view_active'>New</span></a>&nbsp;&nbsp;
		<a href="#"><span id='wptm_view_opened' style="color: none; text-decoration: none; font-weight: bold; font-size: 16px; background-color: lightblue;">In Progress</span></a>&nbsp;&nbsp;<a href="#"><span id='wptm_view_done'>Completed</span></a> &nbsp;&nbsp; <?php echo $filterHtml; ?>
		</p>
		<table id="wptm_table">
		</table>
		<div id="wptm_comment" style="display:none"></div>
	</div>
	<script>
		var view = 'opened';
		var formType = '0';
		var currentPage = 0;
		var keyword = '';
		var cssObj_active = {"color" : "", "text-decoration" : "none", "font-weight" : "bold", "font-size": "16px", "background-color" : "lightblue"};
	    var cssObj_inactive = {"color" : "", "text-decoration" : "none", "font-weight" : "normal", "font-size": "12px", "background-color" : ""};
	
	
		jQuery(document).ready( function () {
			getTask();
		});

		function getTask(){
				jQuery('#wptm_table').flexigrid({
					url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_inbox.php',
					dataType: 'json',
					colModel : [
						{display: 'DATE', name : 'TimeStamp', width : 205, sortable : true, align: 'left'},
						{display: 'FORM', name : 'FormName', width : 70, sortable : true, align: 'left'},
						{display: 'CONFIRMATION', name : 'TransactionID', width : 150, sortable : true, align: 'left'},
						{display: 'STATUS', name : 'Status', width : 80, sortable : true, align: 'left'},
						{display: 'ATTACHMENT', name : '', width: 70, sortable : true, align : 'left'},
						{display: 'NOTES', name : 'Notes', width: 320, sortable : true, align : 'left'},
						{display: 'ACTION', name : 'action', width: 70, sortable : true, align : 'left'}
					],
					searchitems : [
						{display: 'CONFIRMATION', name : 'TransactionID'},
						{display: 'NOTES', name : 'Notes', isdefault: true},
						{display: 'STATUS', name : 'Status'}
					],
					view: view,
					formType: formType,
					sortname: 'TimeStamp',
					sortorder: 'desc',
					usepager: true,
					title: ' ',
					useRp: true,
					rp: 25,
					resizable: true,
					width: 1100,
					height: 370,
					singleSelect: true
				});
		}


		jQuery("#wptm_form_filter").change(function (){
			formType = jQuery("#wptm_form_filter").val();
			//getTask();
			jQuery('#wptm_table').flexReload({
				url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_inbox.php',
				view: view,
				formType: formType,
				page: 1
			 });
		});
		jQuery("#wptm_view_active").click(function (){
			if("active" != view){
				jQuery("#wptm_view_active").css(cssObj_active);
				jQuery("#wptm_view_all").css(cssObj_inactive);
				jQuery("#wptm_view_done").css(cssObj_inactive);
				jQuery("#wptm_view_opened").css(cssObj_inactive);
				view = "active";
				jQuery("#wptm_comment").hide("slow");
				//getTask();
				 jQuery('#wptm_table').flexReload({
					url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_inbox.php',
					view: view,
					formType: formType,
					page: 1
					});
			}				
		});

		jQuery("#wptm_view_all").click(function (){
			if("all" != view){
				jQuery("#wptm_view_all").css(cssObj_active);
				jQuery("#wptm_view_done").css(cssObj_inactive);	
				jQuery("#wptm_view_active").css(cssObj_inactive);				
				jQuery("#wptm_view_opened").css(cssObj_inactive);
				view = "all";
				jQuery("#wptm_comment").hide("slow");
				//getTask();
				 jQuery('#wptm_table').flexReload({
					url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_inbox.php',
						view: view,
						formType: formType,
						page: 1
						});
			}				
		});

		jQuery("#wptm_view_done").click(function (){
			if("done" != view){
				jQuery("#wptm_view_done").css(cssObj_active);
				jQuery("#wptm_view_active").css(cssObj_inactive);
				jQuery("#wptm_view_all").css(cssObj_inactive);
				jQuery("#wptm_view_opened").css(cssObj_inactive);
				view = "done";
				jQuery("#wptm_comment").hide("slow");
				//getTask();
				 jQuery('#wptm_table').flexReload({
			           url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_inbox.php',
					   view: view,
					   formType: formType,
					   page: 1
				});
			}
		});

		jQuery("#wptm_view_opened").click(function (){
        	   if("closed" != view){
                jQuery("#wptm_view_done").css(cssObj_inactive);
                jQuery("#wptm_view_active").css(cssObj_inactive);
                jQuery("#wptm_view_all").css(cssObj_inactive);
				jQuery("#wptm_view_opened").css(cssObj_active);
                view = "opened";
                jQuery("#wptm_comment").hide("slow");
                //getTask();
				 jQuery('#wptm_table').flexReload({
			     	url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_inbox.php',
					view: view,
					formType: formType,
					page: 1
					});
           	    }
        	});

		function getTransaction( id, base_url, type){
			var url = base_url + '?getTransactionData=true&transactionid=' + id + '&format=' + type;
        	window.open(url);
	    }	

	    function downloadAttachment( id, base_url ) {
			var url = base_url + '?getAttachment=true&transactionid=' + id;
			window.open(url);
		}

		function saveText(thisField, id, currentStatus) {
			var value = jQuery.trim(thisField.value);
			var transactionid = jQuery.trim(id);
			var fieldname = jQuery.trim(thisField.name);

			if( currentStatus != '' ) {
				if( !confirm("Changing the status to " + value) ){
					jQuery("#" + fieldname).val(currentStatus);
					return false;
				}
			}
			jQuery.get("../wp-content/plugins/wp-task-manager/ajax/ajax_save_form.php", { fileid: transactionid, value: value, dbfield: fieldname, type: "transaction" },
				function(data){
					//getTask(false);
					jQuery('#wptm_table').flexReload({
						url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_inbox.php',
						view: view,
						formType: formType,
						page: 1
						});
				});
		}

		function setbg( id ){
			jQuery("#wptm_table tr").css("background-color", "white");
			jQuery("#"+id).css("background-color", "lightgray");
		}

	</script>
<?php
	}
?>
