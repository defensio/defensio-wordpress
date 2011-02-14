<?php 
/** This file will contain the functions Defensio for Wordpress override from 
 * plugabble.php make sure it gets included before Wordpress' own plugabble.php */

// Define wp_notify_postauthor so it does not send notifications when the status of a comment is defensio_pending
if ( !function_exists('wp_notify_postauthor') ):
    
    function wp_notify_postauthor($comment_id, $comment_type='') {

        $comment = get_comment($comment_id);
        $post    = get_post($comment->comment_post_ID);
        $user    = get_userdata($post->post_author);
        $current_user = wp_get_current_user();

        if ( $comment->user_id == $post->post_author ) return false; // The author moderated a comment on his own post
        if ( $comment->comment_approved == DefensioWP::DEFENSIO_PENDING_STATUS ) return false; // Do nothing unless defensio has cleared this comment

        if ('' == $user->user_email) return false; // If there's no email to send the comment to

        $comment_author_domain = @gethostbyaddr($comment->comment_author_IP);

        $blogname = get_option('blogname');

        if ( empty( $comment_type ) ) $comment_type = 'comment';

        if ('comment' == $comment_type) {
            /* translators: 1: post id, 2: post title */
            $notify_message  = sprintf( __('New comment on your post #%1$s "%2$s"'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
            /* translators: 1: comment author, 2: author IP, 3: author domain */
            $notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
            $notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
            $notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
            $notify_message .= sprintf( __('Whois  : http://ws.arin.net/cgi-bin/whois.pl?queryinput=%s'), $comment->comment_author_IP ) . "\r\n";
            $notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
            $notify_message .= __('You can see all comments on this post here: ') . "\r\n";
            /* translators: 1: blog name, 2: post title */
            $subject = sprintf( __('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title );
        } elseif ('trackback' == $comment_type) {
            /* translators: 1: post id, 2: post title */
            $notify_message  = sprintf( __('New trackback on your post #%1$s "%2$s"'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
            /* translators: 1: website name, 2: author IP, 3: author domain */
            $notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
            $notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
            $notify_message .= __('Excerpt: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
            $notify_message .= __('You can see all trackbacks on this post here: ') . "\r\n";
            /* translators: 1: blog name, 2: post title */		
            $subject = sprintf( __('[%1$s] Trackback: "%2$s"'), $blogname, $post->post_title );
        } elseif ('pingback' == $comment_type) {
            /* translators: 1: post id, 2: post title */
            $notify_message  = sprintf( __('New pingback on your post #%1$s "%2$s"'), $comment->comment_post_ID, $post->post_title ) . "\r\n";
            /* translators: 1: comment author, 2: author IP, 3: author domain */
            $notify_message .= sprintf( __('Website: %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
            $notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
            $notify_message .= __('Excerpt: ') . "\r\n" . sprintf('[...] %s [...]', $comment->comment_content ) . "\r\n\r\n";
            $notify_message .= __('You can see all pingbacks on this post here: ') . "\r\n";
            /* translators: 1: blog name, 2: post title */
            $subject = sprintf( __('[%1$s] Pingback: "%2$s"'), $blogname, $post->post_title );
        }
        $notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
        $notify_message .= sprintf( __('Delete it: %s'), admin_url("comment.php?action=cdc&c=$comment_id") ) . "\r\n";
        $notify_message .= sprintf( __('Spam it: %s'), admin_url("comment.php?action=cdc&dt=spam&c=$comment_id") ) . "\r\n";

        $wp_email = 'wordpress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));

        if ( '' == $comment->comment_author ) {
            $from = "From: \"$blogname\" <$wp_email>";
            if ( '' != $comment->comment_author_email )
                $reply_to = "Reply-To: $comment->comment_author_email";
        } else {
            $from = "From: \"$comment->comment_author\" <$wp_email>";
            if ( '' != $comment->comment_author_email )
                $reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
        }

        $message_headers = "$from\n"
            . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";

        if ( isset($reply_to) )
            $message_headers .= $reply_to . "\n";

        $notify_message = apply_filters('comment_notification_text', $notify_message, $comment_id);
        $subject = apply_filters('comment_notification_subject', $subject, $comment_id);
        $message_headers = apply_filters('comment_notification_headers', $message_headers, $comment_id);
        
        @wp_mail($user->user_email, $subject, $notify_message, $message_headers);

        return true;
    }

endif;

?>
