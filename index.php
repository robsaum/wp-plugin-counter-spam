<?php
/*
Plugin Name: WP Counter-Spam
Version: 0.10
Plugin URI: http://code.google.com/p/wp-counter-spam/
Description: This implements some basic comment protection such as moving the wp-post-comments.php, obfuscating the new location via JavaScript, performing some human/bot checks, logging all bot access to the old file, etc.  All of this is performed without having to edit any files.
Author: Rob Saum
Author URI: N/A
Date: 2011/05/11

---------------------------------------------------------------------
*/

error_reporting(E_ALL);

/****************************************************************
*                           My Functions                        *
*****************************************************************/

function build_counter_install() {
	global $wpdb;
	define("PLUGINTABLE",$wpdb->prefix."plugin_logger");
	// Check for plugin table
	if($wpdb->get_var("show tables like '".PLUGINTABLE."';") != PLUGINTABLE) {
		/* Table doesn't exist, create it */
		$wpdb->query(
			"CREATE TABLE ".PLUGINTABLE." (".
			"id int(11) NOT NULL auto_increment,".
			"tm int(11) default NULL,".
			"ip int(12) default NULL,".
			"agent mediumtext,".
			"PRIMARY KEY  (id)".
			");"
		);
	}
}

build_counter_install();
  
function mv_wp_post_comments() {   
	// future add file_exists;  
	global $wpdb;
	$blog_root		= ABSPATH; // get_bloginfo('wpurl');
	$src	 		= "wp-comments-post.php"; 
	$new 			= "wp-comments-post-bak.php"; 
	$replace		= "ip_logger.php"; 
	$content		= '';
	$plugin_directory =  ABSPATH . 'wp-content/plugins/wp-counter-spam/';
	$imgFail		= 'http://upload.wikimedia.org/wikipedia/commons/thumb/5/51/Attention_niels_epting.svg/18px-Attention_niels_epting.svg.png';
	$imgPass		= 'http://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Yes_check.svg/20px-Yes_check.svg.png';
	
	// Check permissions and if 'wp-comments-post.php' exists.
	if (!current_user_can('manage_options')) { wp_die( __('You do not have sufficient permissions to access this page.') ); }
	if (rename($blog_root.$src,$blog_root.$new)) { 
		rename($plugin_directory.$replace,$blog_root.$src);
		$fileexist = "<img src='$imgPass'>&nbsp; The <em>wp-comments-post.php</em> was moved successfully and the logging file was installed!"; 
	} else {
		if (file_exists($blog_root.$new)) { 
			if (file_exists($blog_root.$src)) { 
			$fileexist = "<img src='$imgPass'>&nbsp; The <em>wp-comments-post.php</em> file has already been renamed and the logging file has been installed."; 
			} else { rename($plugin_directory.$replace,$blog_root.$src); $fileexist = "<p><img src='$imgPass'>&nbsp; The <em>wp-comments-post.php</em> file has already been renamed and the logging file has been installed."; }
		} else {
			$fileexist = "<img src='$imgFail'>&nbsp; The <em>wp-comments-post.php</em> could not be moved. Please try again."; }
		}

	// Visits to wp-comments-post.php
	$BotRecords = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) FROM ". $wpdb->prefix ."plugin_logger") );
	
	// Display status message
	echo '<br>';
	echo '<div class="wrap">';
	echo '<h2>Spam Protection Status</h2>';
	echo '<ul>';
	echo '<li>'.$fileexist.'</li>';
	echo '<li><strong>'.$BotRecords.'</strong>  Bots have visited the <em>wp-comments-post.php</em> page.</li>';
	echo '</ul>';
	echo '</div>';

	echo '<br>';
	echo '<div class="wrap">';
	echo "<h2>If You Find this Useful, Please Support Us</h2>
			<p>Thank you for checking out the WP-Counter-Spam plugin.  If like our work, let us know.  No donation is too small.</p>
			<form action='https://www.paypal.com/cgi-bin/webscr' method='post'>
			<input type='hidden' name='cmd' value='_s-xclick'>
			<input type='hidden' name='hosted_button_id' value='MKWL8W5AWKK2N'>
			<input type='image' src='https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/btn/btn_donateCC_LG.gif' border='0' name='submit' alt='PayPal - The safer, easier way to pay online!'>
			<img alt='' border='0' src='https://www.paypalobjects.com/WEBSCR-640-20110429-1/en_US/i/scr/pixel.gif' width='1' height='1'>
			</form>";
	echo '</div>';
}

/****************************************************************
*                           Admin Stuff                         *
*****************************************************************/
function wp_counter_spam_menu() { add_options_page('WP Counter Spam Options', 'WP_Counter_Spam', 'manage_options', 'WP_Counter_Spam_ID', 'mv_wp_post_comments'); }
add_action('admin_menu', 'wp_counter_spam_menu');


// http://codex.wordpress.org/Function_Reference/wp_enqueue_script
// http://wp-counter-spam.googlecode.com/svn/trunk/commentfilter.js

function Add_WP_Counter_Spam_head() {
    if (!is_admin()) {
        wp_deregister_script( 'wp-counter-spam' );
	    wp_register_script( 'wp-counter-spam', 'http://wp-counter-spam.googlecode.com/svn/commentfilter.js');
        wp_enqueue_script( 'wp-counter-spam' );
    }
}     
add_action('init', 'Add_WP_Counter_Spam_head');


// This removes the default form fields and replaces them
function remove_default_form($arg) { 
	$arg['url'] = '';  
	$arg['email'] = '';  
	$arg['author'] = ''; 
	return $arg; 
	}
add_filter('comment_form_default_fields', 'remove_default_form');


// TODO: pass the hidden field information to the form
function addCounter_Form($content) {
	global $post;
	$counterIP		= get_bloginfo('url');
	$comment_ids	= get_comment_id_fields();
	$counter_form .= $content.<<<EOF
	<h3 id="counter-reply-title">Leave your Comment</h3>
	<p class="counter-comment-notes"><noscript><span='color:red'>Please enable JavaScript to use this form.</span><br /></noscript>
	Your email address will not be published. Required fields are marked <span class="required">*</span></p>
	<form action="#" method="post" id="CounterForm" name="CounterForm" onsubmit="return formProtect('$counterIP');">
		<p class="counter-form-author"><label for="author">Name</label> <span class="required">*</span><input id="author" name="author" type="text" value="" size="30" aria-required="true" /></p>
		<p class="counter-form-email"><label for="email">Email</label> <span class="required">*</span><input id="email" name="email" type="text" value="" size="30" aria-required="true" /></p>
		<p class="counter-form-url"><label for="url">Website</label><input id="url" name="url" type="text" value="" size="30"/></p>
		<p class="counter-form-comment"><label for="comment">Comment</label><textarea id="counter-comment" name="comment" cols="45" rows="8" aria-required="true""></textarea></p>
		<p><input type="hidden" name="is_real" class="umbert"></p>
		<p>$comment_ids</p>
		<p class="counter-form-submit"><input name="submit" type="submit" id="submit" value="Submit" /></p>
	</form>
EOF;
	
	if ('open' == $post->comment_status) { return $counter_form; } else { return $content; }
}
add_filter('the_content','addCounter_Form');

function wp_counter_head() { /* add css to header */ $plugin_directory 	= get_bloginfo('url') . '/wp-content/plugins/wp_counter_spam/'; echo "<link href='". $plugin_directory ."style.css' rel='stylesheet' type='text/css' />"; }
add_action('wp_head', 'wp_counter_head');

function add_real($postID) { echo "<input type='hidden' name='is_real' class='umbert'>"; }
add_action('comment_form', 'add_real');

function check_human($approved) { if (!empty($_POST['is_real'])) { $approved = 'spam'; } return $approved; }
add_filter('pre_comment_approved', 'check_human');

?>