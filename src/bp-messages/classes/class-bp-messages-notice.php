<?php
/**
 * BuddyPress Messages Classes.
 *
 * @package BuddyPress
 * @subpackage Messages
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Notices class.
 *
 * Use this class to create, activate, deactivate or delete notices.
 *
 * @since 1.0.0
 */
#[AllowDynamicProperties]
class BP_Messages_Notice {

	/**
	 * The notice ID.
	 *
	 * @var int|null
	 */
	public $id = null;

	/**
	 * The subject line for the notice.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * The content of the notice.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * The date the notice was created.
	 *
	 * @var string
	 */
	public $date_sent;

	/**
	 * Whether the notice is active or not.
	 *
	 * @var int
	 */
	public $is_active;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $id Optional. The ID of the current notice.
	 */
	public function __construct( $id = null ) {
		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate method.
	 *
	 * Runs during constructor.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 */
	public function populate() {
		global $wpdb;

		$bp = buddypress();

		$notice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_notices} WHERE id = %d", $this->id ) );

		if ( $notice ) {
			$this->subject   = $notice->subject;
			$this->message   = $notice->message;
			$this->date_sent = $notice->date_sent;
			$this->is_active = (int) $notice->is_active;
		}
	}

	/**
	 * Saves a notice.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		$this->subject = apply_filters( 'messages_notice_subject_before_save', $this->subject, $this->id );
		$this->message = apply_filters( 'messages_notice_message_before_save', $this->message, $this->id );

		/**
		 * Fires before the current message notice item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Messages_Notice $notice Current instance of the message notice item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_notice_before_save', array( &$this ) );

		if ( empty( $this->id ) ) {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_notices} (subject, message, date_sent, is_active) VALUES (%s, %s, %s, %d)", $this->subject, $this->message, $this->date_sent, $this->is_active );
		} else {
			$sql = $wpdb->prepare( "UPDATE {$bp->messages->table_name_notices} SET subject = %s, message = %s, is_active = %d WHERE id = %d", $this->subject, $this->message, $this->is_active, $this->id );
		}

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		if ( ! $id = $this->id ) {
			$id = $wpdb->insert_id;
		}

		// Now deactivate all notices apart from the new one.
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_notices} SET is_active = 0 WHERE id != %d", $id ) );

		bp_update_user_last_activity( bp_loggedin_user_id(), bp_core_current_time() );

		/**
		 * Fires after the current message notice item has been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Messages_Notice $notice Current instance of the message item being saved. Passed by reference.
		 */
		do_action_ref_array( 'messages_notice_after_save', array( &$this ) );

		return true;
	}

	/**
	 * Activates a notice.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function activate() {
		$this->is_active = 1;
		return (bool) $this->save();
	}

	/**
	 * Deactivates a notice.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function deactivate() {
		$this->is_active = 0;
		return (bool) $this->save();
	}

	/**
	 * Deletes a notice.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return bool
	 */
	public function delete() {
		global $wpdb;

		/**
		 * Fires before the current message item has been deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param BP_Messages_Notice $notice Current instance of the message notice item being deleted.
		 */
		do_action( 'messages_notice_before_delete', $this );

		$bp  = buddypress();
		$sql = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_notices} WHERE id = %d", $this->id );

		if ( ! $wpdb->query( $sql ) ) {
			return false;
		}

		/**
		 * Fires after the current message item has been deleted.
		 *
		 * @since 2.8.0
		 *
		 * @param BP_Messages_Notice $notice Current instance of the message notice item being deleted.
		 */
		do_action( 'messages_notice_after_delete', $this );

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * Pulls up a list of notices.
	 *
	 * To get all notices, pass a value of -1 to pag_num.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @param array $args {
	 *     Array of parameters.
	 *     @type int $pag_num  Number of notices per page. Defaults to 20.
	 *     @type int $pag_page The page number.  Defaults to 1.
	 * }
	 * @return array List of notices to display.
	 */
	public static function get_notices( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'pag_num'  => 20, // Number of notices per page.
				'pag_page' => 1 , // Page number.
			)
		);

		$limit_sql = '';
		if ( (int) $r['pag_num'] >= 0 ) {
			$limit_sql = $wpdb->prepare( "LIMIT %d, %d", (int) ( ( $r['pag_page'] - 1 ) * $r['pag_num'] ), (int) $r['pag_num'] );
		}

		$bp = buddypress();

		$notices = $wpdb->get_results( "SELECT * FROM {$bp->messages->table_name_notices} ORDER BY date_sent DESC {$limit_sql}" );

		// Integer casting.
		foreach ( (array) $notices as $key => $data ) {
			$notices[ $key ]->id        = (int) $notices[ $key ]->id;
			$notices[ $key ]->is_active = (int) $notices[ $key ]->is_active;
		}

		/**
		 * Filters the array of notices, sorted by date and paginated.
		 *
		 * @since 2.8.0
		 *
		 * @param array $notices List of notices sorted by date and paginated.
		 * @param array $r       Array of parameters.
		 */
		return apply_filters( 'messages_notice_get_notices', $notices, $r );
	}

	/**
	 * Returns the total number of recorded notices.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return int
	 */
	public static function get_total_notice_count() {
		global $wpdb;

		$bp = buddypress();

		$notice_count = $wpdb->get_var( "SELECT COUNT(id) FROM {$bp->messages->table_name_notices}" );

		/**
		 * Filters the total number of notices.
		 *
		 * @since 2.8.0
		 *
		 * @param int $notice_count Total number of recorded notices.
		 */
		return apply_filters( 'messages_notice_get_total_notice_count', (int) $notice_count );
	}

	/**
	 * Returns the active notice that should be displayed on the front end.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database object.
	 *
	 * @return BP_Messages_Notice
	 */
	public static function get_active() {
		$notice = wp_cache_get( 'active_notice', 'bp_messages' );

		if ( false === $notice ) {
			global $wpdb;

			$bp = buddypress();

			$notice_id = $wpdb->get_var( "SELECT id FROM {$bp->messages->table_name_notices} WHERE is_active = 1" );
			$notice    = new BP_Messages_Notice( $notice_id );

			wp_cache_set( 'active_notice', $notice, 'bp_messages' );
		}

		/**
		 * Gives ability to filter the active notice that should be displayed on the front end.
		 *
		 * @since 2.8.0
		 *
		 * @param BP_Messages_Notice $notice The notice object.
		 */
		return apply_filters( 'messages_notice_get_active', $notice );
	}
}
