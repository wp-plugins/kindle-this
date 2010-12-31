<?php
/*
Plugin Name: Kindle This Widget
Plugin URI: http://www.blogseye.com
Description: Sends a blog post or page to a user's kindle.
Author: Keith P. Graham
Version: 1.1
Requires at least: 2.8
Author URI: http://www.blogseye.com
Tested up to: 3.1

*/

class widget_kindle_this extends WP_Widget {

   /** constructor */
    function widget_kindle_this() {
        parent::WP_Widget(false, $name = 'Kindle This Widget');	
    }
    /** @see WP_Widget::form */
    function form($instance) {				
		// outputs the options form on admin
		// this is the html to display the options

		$title = esc_attr($instance['title']);
		$from = esc_attr($instance['from']);
       ?>
<fieldset style="border:thin black solid;padding:2px;"><legend>Title:</legend>	
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</fieldset>

<?PHP
		// end of the functional section
	}

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
		// processes widget options to be saved
		// have to update the new instance
		return $new_instance;
	}

    /** @see WP_Widget::widget */
    function widget($args, $instance) {	
		// start of the display section
		echo "\r\n<!-- Start of Kindle This Widget -->\r\n";
		// outputs the content of the widget
		extract( $args );
		$title = esc_attr($instance['title']);
		$from = esc_attr($instance['from']);
		//if (empty($from)) {
		//	echo "no from address specified for Kindle This widget";
		//	return;
		//}
		// the loop ids have been cached - get them back
		$p=wp_cache_get( 'kindle_this');

		
		echo $before_widget;
		if ( $title) {
			echo $before_title . $title . $after_title;
		}
		// out goes out here;
			// this is the form for sending the widget
			$path=home_url();
		?>
		<div style="font-style:italic;font-size:.85em;">
		<form name="kpgkindlethis" action="<?php echo $path; ?>" target="kindlethis" method="GET">
				<fieldset style="border:thin black solid;padding:2px;"><legend>Your kindle email address:</legend>
				<input style="font-size:.85em;" size="12" name="kindle_email" type="text" value="your-id"/>@free.kindle.com</fieldset>
				<fieldset style="border:thin black solid;padding:2px;"><legend>Valid &quot;From&quot; email address:</legend>
				<input style="font-size:.85em;" size="16" name="from_email" type="text" value="good@email"/><br/>(email that kindle will accept)
				</fieldset>
				<input type="submit" name="kpg_ksub" value="send to kindle"/>
				<input type="hidden" name="postarray" value="<?php echo $p;?>" />
				<input type="hidden" name="kindletitle" value="" />
				<input type="hidden" name="kindleloc" value="" />
				<?php wp_nonce_field('kpgkindlethis','kpgkindlethisnonce'); ?>

				<script language="javascript" type="text/javascript">
					document.kpgkindlethis.kindletitle.value=document.title;
					document.kpgkindlethis.kindleloc.value=document.location;
				</script>
		</form>
		</div>
		<iframe style="visibility:hidden;position:absolute;left:-2;width:1;height:1;" name="kindlethis">
		</iframe>
		
		<?PHP
		// close the ul or select or add a blank line
		echo $after_widget;
		
		echo "\r\n<!-- end of Kindle This Widget -->\r\n";
	}

}

// wp_get_archives(apply_filters('widget_archives_args', array('type' => 'monthly', 'show_post_count' => $c))); 
add_action('widgets_init', create_function('', 'return register_widget("widget_kindle_this");'));

// this is the plugins page stuff
// no unistall because I have not created any meta data to delete.
function kpg_kindle_this_init() {
   add_options_page('Kindle This', 'Kindle This', 'manage_options',__FILE__,'kpg_kindle_this_control');
}
  // Plugin added to Wordpress plugin architecture
	add_action('admin_menu', 'kpg_kindle_this_init');	
function kpg_kindle_this_control() {
	// this is the Kindle This functionality.
    echo "<h2>Kindel This</h2>";
?>
	<p>The Kindle-This widget is installed and working correctly.</p>
	
	<?php
	
	$options=get_option('kpg_kindlethis_options');
	if (empty($options)) $options=array();
	// cache bad cases
	$count=0;
	if (array_key_exists('count',$options)) {
		$count=$options['count'];
	}
	?>
	<p>Number of Kindle pages sent=<?php echo $count; ?></p>
	
	<?php

}
// also have to hook the loop in order to catch the ids
	add_action('loop_start', 'kpg_kindle_this_catchloop');	
function kpg_kindle_this_catchloop($huh) {
    global $wp_query;
	$posts = $wp_query->posts;
	// capture the posts
	$pids=array();
	foreach ($posts as $post) {
		$id=$post->ID;
		$pids[count($pids)]=$id;
	}
// cache the id array for latter	
    wp_cache_set( 'kindle_this', serialize($pids) );

	return $huh;
}

// establish the blog itself as the action target
	add_action('init', 'kpg_kindle_this_mailer');	
function kpg_kindle_this_mailer() {
    if (!array_key_exists('kpg_ksub',$_GET)) {
	    print_r($GET);
		return;
	}
    $p=$_GET['postarray'];
    $ke=$_GET['kindle_email'];
    $fe=$_GET['from_email'];

	
	if (!empty($p)||!empty($ke)||!empty($fe)) {
		// check the parameters
		if (empty($p)) { ?>
			<script language="javascript" type="text/javascript">alert("Problem retrieving the page");</script>
		<?php 
            flush();		
			exit(); 
		}
		if (empty($ke) || $ke=='your-id') { ?>
			<script language="javascript" type="text/javascript">alert("Please enter your Kindle email");</script>
		<?php 		
            flush();		
			exit(); 
		}
		if (empty($fe) || $fe=='good@email') { ?>
			<script language="javascript" type="text/javascript">alert("Please enter an email address that Free.Kindle.com will accept");</script>
		<?php 
            flush();		
			exit(); 
		}
	}
	// check the nonce
	if(!wp_verify_nonce($_GET['kpgkindlethisnonce'],'kpgkindlethis')) { ?>
			<script language="javascript" type="text/javascript">alert("Problem in checking that this came from this blog");</script>
     <?php
			flush();		
			exit(); 
	}
	
	$blog=get_bloginfo('name');
	$blogurl=home_url();
	$kt=$_GET['kindletitle'];
	$kt=stripslashes($kt);
    $kl=$_GET['kindleloc'];
    if (empty($kl)) $kl=$blogurl;
	if (empty($kt)) $kt=$blog;
	$filename=sanitize_title($kt).'.html';
	
	$ansa='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html>
<head><title>test</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
<body>';
	$ansa.="\r\n<h3>$kt</h3>\r\n";
	$ansa.="\r\n<h4><a href=\"$kl\">$kl</a></h4>\r\n";
	$posts=unserialize($p);
	if (!empty($p)&&!empty($ke)&&!empty($fe)&&!empty($posts)&&count($posts)>0) {
		$ddate='';
	    for ($j=0;$j<count($posts);$j++) {
			$id=$posts[$j];
			$post=get_post($id);
			$title=$post->post_title;
			$date=$post->post_date;
			if ($j==0) $ddate=$date;
			$content=$post->post_content;
			$content=do_shortcode($content);
			$ansa.="<h3>$title</h3>";
			$ansa.="<h4>$date</h4>";
			$ansa.="<div>$content</div>\r\n";
			$ansa.="\r\n<hr/>\r\n";
		}
		$ansa.='</body></html>';
		$to=$ke.'@free.kindle.com';
		
		$subject = "Web page from $kt $date";
		$headers = "From: $fe\r\nReply-To: $fe\r\nCc: $fe";
		
		$message = "
Attached is an extract from $kt at $kl 
created by the Kindle-This widget. 

If you did not ask for this then please remove $fe
from your allowed email addresses at http://kindle.com";


		$attachments=array(array($ansa,$filename,'base64','text/html'));
		$mail_sent=kpg_kindle_this_mail($to, $subject, $message, $headers,$attachments);
		//$mail_sent = @mail( $to, $subject, $message, $headers );
		//if the message is sent successfully print "Mail sent". Otherwise print "Mail failed"
		if ($mail_sent) {
			?> <script>alert("Web Page sent to free.kindle.com");</script> 	<?php	
		} else {		
			?> <script>alert("There was an error sending the email to kindle");</script> <?php
		}
	}
	// we need to record the count of pages sent
	$options=get_option('kpg_kindlethis_options');
	if (empty($options)) $options=array();
	// cache bad cases
	$count=0;
	if (array_key_exists('count',$options)) {
		$count=$options['count'];
	}
	$count++;
	$options['count']=$count;
	update_option('kpg_kindlethis_options',$options);
	
flush();
	exit();
}

// this is the clone of wp_mail that uses strings instead of files

function kpg_kindle_this_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
	// Compact the input, apply the filters, and extract them back out
	extract( compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

	if ( !is_array($attachments) )
		$attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );

	global $phpmailer;

	// (Re)create it, if it's gone missing
	if ( !is_object( $phpmailer ) || !is_a( $phpmailer, 'PHPMailer' ) ) {
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		require_once ABSPATH . WPINC . '/class-smtp.php';
		$phpmailer = new PHPMailer();
	}

	// Headers
	if ( empty( $headers ) ) {
		$headers = array();
	} else {
		if ( !is_array( $headers ) ) {
			// Explode the headers out, so this function can take both
			// string headers and an array of headers.
			$tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		} else {
			$tempheaders = $headers;
		}
		$headers = array();

		// If it's actually got contents
		if ( !empty( $tempheaders ) ) {
			// Iterate through the raw headers
			foreach ( (array) $tempheaders as $header ) {
				if ( strpos($header, ':') === false ) {
					if ( false !== stripos( $header, 'boundary=' ) ) {
						$parts = preg_split('/boundary=/i', trim( $header ) );
						$boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
					}
					continue;
				}
				// Explode them out
				list( $name, $content ) = explode( ':', trim( $header ), 2 );

				// Cleanup crew
				$name    = trim( $name    );
				$content = trim( $content );

				switch ( strtolower( $name ) ) {
					// Mainly for legacy -- process a From: header if it's there
					case 'from':
						if ( strpos($content, '<' ) !== false ) {
							// So... making my life hard again?
							$from_name = substr( $content, 0, strpos( $content, '<' ) - 1 );
							$from_name = str_replace( '"', '', $from_name );
							$from_name = trim( $from_name );

							$from_email = substr( $content, strpos( $content, '<' ) + 1 );
							$from_email = str_replace( '>', '', $from_email );
							$from_email = trim( $from_email );
						} else {
							$from_email = trim( $content );
						}
						break;
					case 'content-type':
						if ( strpos( $content, ';' ) !== false ) {
							list( $type, $charset ) = explode( ';', $content );
							$content_type = trim( $type );
							if ( false !== stripos( $charset, 'charset=' ) ) {
								$charset = trim( str_replace( array( 'charset=', '"' ), '', $charset ) );
							} elseif ( false !== stripos( $charset, 'boundary=' ) ) {
								$boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset ) );
								$charset = '';
							}
						} else {
							$content_type = trim( $content );
						}
						break;
					case 'cc':
						$cc = array_merge( (array) $cc, explode( ',', $content ) );
						break;
					case 'bcc':
						$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
						break;
					default:
						// Add it to our grand headers array
						$headers[trim( $name )] = trim( $content );
						break;
				}
			}
		}
	}

	// Empty out the values that may be set
	$phpmailer->ClearAddresses();
	$phpmailer->ClearAllRecipients();
	$phpmailer->ClearAttachments();
	$phpmailer->ClearBCCs();
	$phpmailer->ClearCCs();
	$phpmailer->ClearCustomHeaders();
	$phpmailer->ClearReplyTos();

	// From email and name
	// If we don't have a name from the input headers
	if ( !isset( $from_name ) )
		$from_name = 'WordPress';

	/* If we don't have an email from the input headers default to wordpress@$sitename
	 * Some hosts will block outgoing mail from this address if it doesn't exist but
	 * there's no easy alternative. Defaulting to admin_email might appear to be another
	 * option but some hosts may refuse to relay mail from an unknown domain. See
	 * http://trac.wordpress.org/ticket/5007.
	 */

	if ( !isset( $from_email ) ) {
		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_email = 'wordpress@' . $sitename;
	}

	// Plugin authors can override the potentially troublesome default
	$phpmailer->From     = apply_filters( 'wp_mail_from'     , $from_email );
	$phpmailer->FromName = apply_filters( 'wp_mail_from_name', $from_name  );

	// Set destination addresses
	if ( !is_array( $to ) )
		$to = explode( ',', $to );

	foreach ( (array) $to as $recipient ) {
		$phpmailer->AddAddress( trim( $recipient ) );
	}

	// Set mail's subject and body
	$phpmailer->Subject = $subject;
	$phpmailer->Body    = $message;

	// Add any CC and BCC recipients
	if ( !empty( $cc ) ) {
		foreach ( (array) $cc as $recipient ) {
			$phpmailer->AddCc( trim($recipient) );
		}
	}

	if ( !empty( $bcc ) ) {
		foreach ( (array) $bcc as $recipient) {
			$phpmailer->AddBcc( trim($recipient) );
		}
	}

	// Set to use PHP's mail()
	$phpmailer->IsMail();

	// Set Content-Type and charset
	// If we don't have a content-type from the input headers
	if ( !isset( $content_type ) )
		$content_type = 'text/plain';

	$content_type = apply_filters( 'wp_mail_content_type', $content_type );

	$phpmailer->ContentType = $content_type;

	// Set whether it's plaintext, depending on $content_type
	if ( 'text/html' == $content_type )
		$phpmailer->IsHTML( true );

	// If we don't have a charset from the input headers
	if ( !isset( $charset ) )
		$charset = get_bloginfo( 'charset' );

	// Set the content-type and charset
	$phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );

	// Set custom headers
	if ( !empty( $headers ) ) {
		foreach( (array) $headers as $name => $content ) {
			$phpmailer->AddCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
		}

		if ( false !== stripos( $content_type, 'multipart' ) && ! empty($boundary) )
			$phpmailer->AddCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
	}

// function AddStringAttachment($string, $filename, $encoding = 'base64', $type = 'application/octet-stream') {

	if ( !empty( $attachments ) ) {
		foreach ( $attachments as $attachment ) {
			$phpmailer->AddStringAttachment($attachment[0],$attachment[1],$attachment[2],$attachment[3]);
		}
	}

	//do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

	// Send!
	$result = @$phpmailer->Send();

	return $result;
}


