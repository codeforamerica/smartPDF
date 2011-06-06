<?php
/*
Plugin Name: WP-Task-Manager
Plugin URI: http://thomas.lepetitmonde.net/en/index.php/projects/wordpress-task-manager
Description: Integrate in Wordpress, a task manager system. V2 IS COMING SOON !<a href="options-general.php?page=wp_task_manager_page_option">Options</a> | <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8475218">Donate</a> | <a href="http://thomas.lepetitmonde.net/en/projects/wordpress-task-manager" >Support</a> |  <a href="http://www.amazon.fr/gp/registry/registry.html/ref=wem-si-html_viewall?id=3CBHAPX24HDQ4" target="_blank" title="Amazon Wish List">Amazon Wishlist</a>
Author: Thomas Genin
Author URI: http://thomas.lepetitmonde.net/
Version: 1.62
*/
/** Copyright 2009  Thomas GENIN  (email : xt6@free.fr)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

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
add_filter('favorite_actions', 'my_favorite_actions_menu');
//---------------------------------------------------------------------------	


	function wp_task_manager_create_menu(){
		global $current_user,$rank;

		wp_enqueue_style( 'myStyleSheets',plugins_url('/css/custom.css', __FILE__));

		wp_enqueue_style( 'flexgridStyleSheets',plugins_url('/css/flexigrid.pack.css', __FILE__));
		wp_enqueue_script('flexgrid', plugins_url('/js/flexigrid.js', __FILE__), array('jquery'));


		add_menu_page( 'Inbox', 'Inbox', 1, __FILE__, 'wp_task_manager_page_dispatcher', IMG_DIRECTORY.'ico16.png');
		add_menu_page( 'Manage Forms', 'Manage Forms', 1, "pdf_form_manager", 'wp_task_manager_page_form_manager', IMG_DIRECTORY.'ico16.png');
	}

	//add Dashboard Widget via ajax_function wp_add_dashboard_widget()
	function wp_task_manager_init_dashboard_widget() {
		wp_add_dashboard_widget( 'wp_dashboard_my_task_manager', __( 'All Submissions' ), 'wp_task_manager_dashboard_widget' );
		wp_add_dashboard_widget( 'wp_dashboard_my_task1_manager', __( 'Submissions by Form Name' ), 'wp_task_manager_dashboard_widget_1' );
		example_remove_dashboard_widgets();
		
	}

	function remove_menus () {
		global $menu, $rank;
		if( '1' != $rank ){
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
	function my_favorite_actions_menu($actions) {
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
	}

	
	function wp_task_manager_page_dispatcher(){

		//if( isset( $_GET['transactionid'] ) ){
		//	$transactionid = filter_input( INPUT_GET, 'transactionid', FILTER_SANITIZE_STRING );
		//	getTransactionData( $transactionid );
		//}
		//else {
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
		//}
	} 

	
	function wp_task_manager_dashboard_widget_1() {
		global $wpdb,$current_user,$task_plugIn_base_url,$rank;

		$table1 = $wpdb->prefix."smartPDF";
		$table2 = $wpdb->prefix."smartPDF_data";
		$query = "SELECT count(a.FormName) as counts, a.FormName as formType, b.status as Status 
			     FROM $table1 a, $table2 b 
				WHERE a.fileid = b.fileid 
					GROUP BY a.formname, b.status;";

		$ListForms = $wpdb->get_results( $query, ARRAY_A);

		if( $ListForms ){
			echo "<div>";
			foreach( $ListForms as $forms){
				echo $forms['counts'] . " " .$forms['Status']. " <u>".$forms['formType']."</u><br><br> ";
			}
			echo "</div>";
		}
	
	}
	//Content of Dashboard-Widget
	function wp_task_manager_dashboard_widget() {

		global $wpdb,$current_user,$task_plugIn_base_url,$rank;
		
	 	$table = $wpdb->prefix."smartPDF_data";
		$query = "SELECT count(transactionid) as counts, status 
				FROM $table GROUP BY status;";

                $ListForms = $wpdb->get_results( $query, ARRAY_A);
	
		echo "<table><tr>";	
                if( $ListForms ){
			echo "<td>";
			echo "<label> Current Status </label><hr><br>";
                        foreach( $ListForms as $forms){
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
        $filterHtml .= "<option value=0> Form Name - All </option>";
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
                 <p><u><b>View:</b></u>&nbsp;&nbsp;<a href="#"><span id='wptm_view_form_active' style="color: none; text-decoration: none; font-weight: bold; font-size: 16px">Active</span></a>
		&nbsp;&nbsp; <a href="#"><span id='wptm_view_form_archived' >Archived</span></a>
		&nbsp;&nbsp; <span> <?php echo $filterHtml; ?> </span></p>

		<div id="wptm_form_manager_list">
                 </div>

	 <script>
        var formType = '0';
		var view = 'active';		
		var cssObj_active = {"color" : "", "text-decoration" : "none", "font-weight" : "bold", "font-size": "16px"};
        var cssObj_inactive = {"color" : "", "text-decoration" : "none", "font-weight" : "normal", "font-size": "12px"};

		
        jQuery(document).ready(function () {
             getForm();
        });

		function getForm(noAction) {
			if( typeof noAction == "undefined")
	        	jQuery("#wptm_form_manager_list").hide("slow");
            jQuery.get("../wp-content/plugins/wp-task-manager/ajax/ajax_view_form_manager.php", { formType: formType, view: view, url:"<?php echo $task_plugIn_base_url?>"},
            	function(data){
                	jQuery("#wptm_form_manager_list").html(data);
                });
			if( typeof noAction == "undefined")
	            jQuery("#wptm_form_manager_list").toggle("slow");

        }

        jQuery("#wptm_form_manager").change( function () {
        	formType = jQuery(this).val();
            getForm();
        });

		jQuery("#wptm_view_form_active").click( function () {
			jQuery("#wptm_view_form_active").css(cssObj_active);
			jQuery("#wptm_view_form_archived").css(cssObj_inactive);
			view = "active";
			getForm();
		});
		jQuery("#wptm_view_form_archived").click( function () {
			jQuery("#wptm_view_form_active").css(cssObj_inactive);
            jQuery("#wptm_view_form_archived").css(cssObj_active);
    	    view = "archived";
            getForm();
        });

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
		<a href="#"><span id='wptm_view_opened' style="color: none; text-decoration: none; font-weight: bold; font-size: 16px">Open</span></a>&nbsp;&nbsp;<a href="#"><span id='wptm_view_done'>Closed</span></a> &nbsp;&nbsp; <?php echo $filterHtml; ?> <span style="padding: 0 25px 0 25px;"><a href="#" id="wptm_view_print"><img src="<?php echo IMG_DIRECTORY."print_button.gif";?>"/></a></span><span> <input type=text id="wptm_view_search_input" /> <a href="#" id="wptm_view_search"><img src="<?php echo  IMG_DIRECTORY."search_button.png";?>" width=25px height=25px /></a></span>
		</p>
		<!--div id="wptm_all_task">

		</div-->
		<table id="wptm_table">
		</table>
		<div id="wptm_comment" style="display:none"></div>
	</div>
	<script>
		var view = "opened";
		var formType = '0';
		var currentPage = 0;
		var keyword = '';
		var cssObj_active = {"color" : "", "text-decoration" : "none", "font-weight" : "bold", "font-size": "16px"};
	    var cssObj_inactive = {"color" : "", "text-decoration" : "none", "font-weight" : "normal", "font-size": "12px"};
	
	
		jQuery(document).ready( function () {
			getTask();

		});

		function getTask(){
				jQuery('#wptm_table').flexigrid({
					url: '../wp-content/plugins/wp-task-manager/ajax/ajax_view_flexigrid.php',
					dataType: 'json',
					colModel : [
					{display: 'DATE', name : 'TimeStamp', width : 40, sortable : true, align: 'left'},
					{display: 'FORM NAME', name : 'FormName', width : 100, sortable : true, align: 'left'},
					{display: 'CONFIRMATION', name : 'TransactionID', width : 100, sortable : true, align: 'left'},
					{display: 'STATUS', name : 'Status', width : 250, sortable : true, align: 'left'},
					{display: 'ATTACHMENT', name : '', width: 40, sortable : true, align : 'left'},
					{display: 'NOTES', name : 'Notes', width: 150, sortable : true, align : 'left'},
					{display: 'ACTION', name : 'action', width: 40, sortable : true, align : 'left'}
					],
					searchitems : [
					{display: 'TRANSACTIONID', name : 'TransactionID'},
					{display: 'NOTES', name : 'Notes', isdefault: true},
					{display: 'STATUS', name : 'Status'}
					],
					sortname: 'TimeStamp',
					sortorder: 'desc',
					usepager: true,
					title: 'None',
					useRp: true,
					rp: 10,
					resizable: true,
					width: 700,
					height: 370,
					singleSelect: true
				});
		}

		jQuery("#wptm_view_search_input").bind("keyup", function(e) {
			var code = (e.keyCode ? e.keycode : e.which);
			if( code == 13 ) {
				keyword = jQuery(this).val();
				getTask(false);
			}
		});
			
		jQuery("#wptm_view_search").click( function () {
			if( jQuery("#wptm_view_search_input").val() != '' ){
				keyword = jQuery("#wptm_view_search_input").val();
				getTask(false);
			}		
		});		
	
		jQuery("#wptm_view_print").click( function () {

		});

		jQuery("#wptm_form_filter").change(function (){
			formType = jQuery("#wptm_form_filter").val();
			getTask();			
		});
		jQuery("#wptm_view_active").click(function (){
			if("active" != view){
				currentPage = 0;
				jQuery("#wptm_view_active").css(cssObj_active);
				jQuery("#wptm_view_all").css(cssObj_inactive);
				jQuery("#wptm_view_done").css(cssObj_inactive);
				jQuery("#wptm_view_opened").css(cssObj_inactive);
				view = "active";
				jQuery("#wptm_comment").hide("slow");
				getTask();
			}				
		});

		jQuery("#wptm_view_all").click(function (){
			if("all" != view){
				currentPage = 0;
				jQuery("#wptm_view_all").css(cssObj_active);
				jQuery("#wptm_view_done").css(cssObj_inactive);	
				jQuery("#wptm_view_active").css(cssObj_inactive);				
				jQuery("#wptm_view_opened").css(cssObj_inactive);
				view = "all";
				jQuery("#wptm_comment").hide("slow");
				getTask();
			}				
		});

		jQuery("#wptm_view_done").click(function (){
			if("done" != view){
				currentPage = 0;
				jQuery("#wptm_view_done").css(cssObj_active);
				jQuery("#wptm_view_active").css(cssObj_inactive);
				jQuery("#wptm_view_all").css(cssObj_inactive);
				jQuery("#wptm_view_opened").css(cssObj_inactive);
				view = "done";
				jQuery("#wptm_comment").hide("slow");
				getTask();
			}
		});

		jQuery("#wptm_view_opened").click(function (){
        	   if("closed" != view){
			currentPage = 0;
                jQuery("#wptm_view_done").css(cssObj_inactive);
                jQuery("#wptm_view_active").css(cssObj_inactive);
                jQuery("#wptm_view_all").css(cssObj_inactive);
				jQuery("#wptm_view_opened").css(cssObj_active);
                view = "opened";
                jQuery("#wptm_comment").hide("slow");
                getTask();
           	    }
        	});

		function getTransaction( id, base_url){
			var url = base_url + '?getTransactionData=true&transactionid=' + id;
        		window.open(url);
	        }	
	    	function downloadAttachment( id, base_url ) {
			var url = base_url + '?getAttachment=true&transactionid=' + id;
			window.open(url);
		}

	</script>
<?php
	}
?>
