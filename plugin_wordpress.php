<?php
/*
Plugin Name: Discord notification
Description: The plugin allows you to retrieve comments from a post to publish them on a specific Discord channel. The goal is to receive a Discord notification for each comment posted.
Author: Pader Joris
Version: 1.0
*/

//Plugin initialization
function init_plugin() {
    add_option( 'webhook' );
    add_option( 'bot_comment' );
    add_option( 'bot_name' );
    add_option( 'bot_message' );
    add_option( 'bot_author' );
    add_option( 'bot_mention' );
}

register_activation_hook( __FILE__, 'init_plugin' );

//Send to Discord
function discord_notif( $comment_ID, $comment_approved ) {
    if ( 1 === $comment_approved ) {
        $comment   = get_comment( $comment_ID );
        $post_id   = $comment->comment_post_ID;
        $timestamp = date( "c", strtotime( "now" ) );
        $author    = $comment->comment_author;

        $bot_name    = get_option( 'bot_name' ) == "" ? "Bot" : get_option( 'bot_name' );
        $bot_content = get_option( 'bot_message' ) == "" ? null : get_option( 'bot_message' );
        $bot_comment = get_option( 'bot_comment' ) == "" ? "Comment" : get_option( 'bot_comment' );
        $bot_author  = get_option( 'bot_author' ) == "" ? "Author" : get_option( 'bot_author' );

        switch ( get_option( 'bot_mention' ) ) {
            case "everyone":
                $bot_content = '@everyone ' . $bot_content;
                break;
            default:
                break;
        }

        $json_data = json_encode( [

            "username" => $bot_name,
            "tts"      => false,
            "content"  => $bot_content,

            "embeds" => [
                [
                    "title" => "Title of post: " . get_the_title( $post_id ),

                    "type" => "rich",

                    "description" => $bot_comment . ": " . $comment->comment_content,

                    "timestamp" => $timestamp,

                    "color" => hexdec( "3366ff" ),

                    "author" => [
                        "name" => $bot_author . ": " . ucfirst( $author ),
                    ],
                ]
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

        $ch = curl_init( get_option( 'webhook' ) );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

        $response = curl_exec( $ch );
        curl_close( $ch );
    }
}

add_action( 'comment_post', 'discord_notif', 10, 2 );

//Admin Dashboard Menu
function notification_admin_menu() {
    add_menu_page( 'Discord', 'Discord', 'manage_options', 'notifications-admin-menu-discord', 'notifications_admin_menu_discord', 'dashicons-bell', 2 );
}

add_action( 'admin_menu', 'notification_admin_menu' );

//Plugin settings and display in admin dashboard
function notifications_admin_menu_discord() {
    ?>
    <form action="admin.php?page=notifications-admin-menu-discord" method="post">
        <div class="wrapper">
            <div>
                <h1>
                    <?php esc_html_e( 'Discord WebHook', 'notif_discord' ); ?>
                </h1>
                <input type="text" name="webhook" minlength="32"
                       placeholder="<?php if ( get_option( 'webhook' ) != null ) {
                           echo get_option( 'webhook' );
                       } else echo "Entre webhook" ?>">
                <span style="font-size:16px">Webhook URL.</span>
            </div>
            <div>
                <h1>
                    <?php esc_html_e( 'Bot Username', 'bot_name' ); ?>
                </h1>
                <input type="text" name="bot_name" minlength="1"
                       placeholder="<?php if ( get_option( 'bot_name' ) != null ) {
                           echo get_option( 'bot_name' );
                       } else echo "Entre name" ?>">
                <span style="font-size:16px">The username that you want to use for the bot on your Discord server.</span>
            </div>
            <div>
                <h1>
                    <?php esc_html_e( 'Bot message', 'bot_message' ); ?>
                </h1>
                <input type="text" name="bot_message" minlength="1"
                       placeholder="<?php if ( get_option( 'bot_message' ) != null ) {
                           echo get_option( 'bot_message' );
                       } else echo "Entre message" ?>">
                <span style="font-size:16px">Text to introduce WordPress notification.</span>
            </div>
            <div>
                <h1>
                    <?php esc_html_e( 'Author', 'bot_author' ); ?>
                </h1>
                <input type="text" name="bot_author" minlength="1"
                       placeholder="<?php if ( get_option( 'bot_author' ) != null ) {
                           echo get_option( 'bot_author' );
                       } else echo "Entre text" ?>">
                <span style="font-size:16px">Name of the author of the comment on the post.</span>
            </div>
            <div>
                <h1>
                    <?php esc_html_e( 'Message for new comments', 'bot_comment' ); ?>
                </h1>
                <input type="text" name="bot_comment" minlength="1"
                       placeholder="<?php if ( get_option( 'bot_comment' ) != null ) {
                           echo get_option( 'bot_comment' );
                       } else echo "Entre text" ?>">
                <span style="font-size:16px">   Text to introduce the post comment.</span>
            </div>
            <div>
                <h1>
                    <?php esc_html_e( 'Mention Everyone', 'bot_everyone' ); ?>
                </h1>
                <select name="mention">
                    <option value="nothing">Nothing</option>
                    <option value="everyone">Everyone</option>
                </select>
                <span style="font-size:16px"> Mention @everyone when sending the message to Discord.</span>
            </div>
        </div>
        <br>
        <input type="submit" name="submit" value="Save Settings" class="button-primary">

        <?php
        $webhookurl  = "";
        $bot_comment = "";
        $bot_name    = "";
        $bot_message = "";
        $bot_author  = "";
        $bot_mention = "";

        if ( isset( $_POST['submit'] ) ) {
            $webhookurl  = $_POST['webhook'];
            $bot_comment = $_POST['bot_comment'];
            $bot_name    = $_POST['bot_name'];
            $bot_message = $_POST['bot_message'];
            $bot_author  = $_POST['bot_author'];
            $bot_mention = $_POST['mention'];
            echo "Settings save";
        }

        if ( $webhookurl != "" && $webhookurl != get_option( 'webhook' ) ) {
            $options = update_option( 'webhook', $webhookurl );
        }
        if ( $bot_comment != "" && $bot_comment != get_option( 'bot_comment' ) ) {
            $options = update_option( 'bot_comment', $bot_comment );
        }
        if ( $bot_name != "" && $bot_name != get_option( 'bot_name' ) ) {
            $options = update_option( 'bot_name', $bot_name );
        }
        if ( $bot_message != "" && $bot_message != get_option( 'bot_message' ) ) {
            $options = update_option( 'bot_message', $bot_message );
        }
        if ( $bot_author != "" && $bot_author != get_option( 'bot_author' ) ) {
            $options = update_option( 'bot_author', $bot_author );
        }
        if ( $bot_mention != "" && $bot_mention != get_option( 'bot_mention' ) ) {
            $options = update_option( 'bot_mention', $bot_mention );
        }
        ?>

        <br>
        <h1>Example:</h1>
        <img src="https://i.postimg.cc/ht9HN1cp/unknown.png">
    </form>
    <?php
}