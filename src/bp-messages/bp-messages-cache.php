<?php
/**
 * BuddyPress Messages Caching.
 *
 * @package BuddyPress
 * @subpackage Messages
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Slurp up metadata for a set of messages.
 *
 * It grabs all message meta associated with all of the messages passed in
 * $message_ids and adds it to WP cache. This improves efficiency when using
 * message meta within a loop context.
 *
 * @since 2.2.0
 *
 * @param int|string|array|bool $message_ids Accepts a single message_id, or a
 *                                           comma-separated list or array of message ids.
 */
function bp_messages_update_meta_cache( $message_ids = false ) {
	bp_update_meta_cache(
		array(
			'object_ids'       => $message_ids,
			'object_type'      => buddypress()->messages->id,
			'cache_group'      => 'message_meta',
			'object_column'    => 'message_id',
			'meta_table'       => buddypress()->messages->table_name_meta,
			'cache_key_prefix' => 'bp_messages_meta',
		)
	);
}

// List actions to clear super cached pages on, if super cache is installed.
add_action( 'messages_delete_thread', 'bp_core_clear_cache' );
add_action( 'messages_send_notice', 'bp_core_clear_cache' );
add_action( 'messages_message_sent', 'bp_core_clear_cache' );

// Don't cache message inbox/sentbox/compose as it's too problematic.
add_action( 'messages_screen_compose', 'bp_core_clear_cache' );
add_action( 'messages_screen_sentbox', 'bp_core_clear_cache' );
add_action( 'messages_screen_inbox', 'bp_core_clear_cache' );

/**
 * Clear message cache after a message is saved.
 *
 * @since 2.0.0
 *
 * @param BP_Messages_Message $message Message being saved.
 */
function bp_messages_clear_cache_on_message_save( $message ) {
	// Delete thread cache.
	wp_cache_delete( $message->thread_id, 'bp_messages_threads' );

	// Delete thread messages count.
	wp_cache_delete( "{$message->thread_id}_bp_messages_thread_total_count", 'bp_messages_threads' );

	// Delete unread count for each recipient.
	foreach ( (array) $message->recipients as $recipient ) {
		wp_cache_delete( $recipient->user_id, 'bp_messages_unread_count' );
	}

	// Delete thread latest message cached data.
	wp_cache_delete( "{$message->thread_id}_bp_messages_thread_latest_message", 'bp_messages_threads' );

	// Delete thread recipient cache.
	wp_cache_delete( 'thread_recipients_' . $message->thread_id, 'bp_messages' );
}
add_action( 'messages_message_after_save', 'bp_messages_clear_cache_on_message_save' );

/**
 * Clear message cache after a message thread is deleted.
 *
 * @since 2.0.0
 *
 * @param int|array $thread_ids If single thread, the thread ID.
 *                              Otherwise, an array of thread IDs.
 * @param int       $user_id    ID of the user that the threads were deleted for.
 */
function bp_messages_clear_cache_on_message_delete( $thread_ids, $user_id ) {
	// Delete thread and thread recipient cache.
	foreach ( (array) $thread_ids as $thread_id ) {
		wp_cache_delete( $thread_id, 'bp_messages_threads' );
		wp_cache_delete( "thread_recipients_{$thread_id}", 'bp_messages' );

		// Delete thread latest message cached data.
		wp_cache_delete( "{$thread_id}_bp_messages_thread_latest_message", 'bp_messages_threads' );

		// Delete thread messages count.
		wp_cache_delete( "{$thread_id}_bp_messages_thread_total_count", 'bp_messages_threads' );
	}

	// Delete unread count for logged-in user.
	wp_cache_delete( $user_id, 'bp_messages_unread_count' );
}
add_action( 'messages_delete_thread', 'bp_messages_clear_cache_on_message_delete', 10, 2 );
add_action( 'bp_messages_exit_thread', 'bp_messages_clear_cache_on_message_delete', 10, 2 );

/**
 * Invalidate cache for notices.
 *
 * @since 2.0.0
 */
function bp_notices_clear_cache() {
	wp_cache_delete( 'active_notice', 'bp_messages' );
}
add_action( 'messages_notice_after_save', 'bp_notices_clear_cache' );
add_action( 'messages_notice_before_delete', 'bp_notices_clear_cache' );
