<?php
/**
 * @group members
 */
#[\AllowDynamicProperties]
class BP_Tests_Members_Functions extends BP_UnitTestCase {
	protected $permalink_structure = '';
	protected $filter_fired        = '';

	public function set_up() {
		parent::set_up();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	/**
	 * @ticket BP4915
	 * @group bp_core_delete_account
	 */
	public function test_bp_core_delete_account() {
		// Stash
		$current_user      = get_current_user_id();
		$deletion_disabled = bp_disable_account_deletion();

		// Create an admin for testing
		$admin_user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $admin_user );

		// 1. Admin can delete user account
		$this->set_current_user( $admin_user );
		$user1 = self::factory()->user->create();
		bp_core_delete_account( $user1 );
		$maybe_user = new WP_User( $user1 );
		$this->assertEquals( 0, $maybe_user->ID );
		unset( $maybe_user );
		$this->restore_admins();

		// 2. Admin cannot delete superadmin account
		$user2 = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $user2 );
		bp_core_delete_account( $user2 );
		$maybe_user = new WP_User( $user2 );
		$this->assertNotEquals( 0, $maybe_user->ID );
		unset( $maybe_user );

		// User cannot delete other's account
		$user3 = self::factory()->user->create();
		$user4 = self::factory()->user->create();
		$this->set_current_user( $user3 );
		bp_core_delete_account( $user4 );
		$maybe_user = new WP_User( $user4 );
		$this->assertNotEquals( 0, $maybe_user->ID );
		unset( $maybe_user );

		// Cleanup
		$this->set_current_user( $current_user );
		bp_update_option( 'bp-disable-account-deletion', $deletion_disabled );
	}

	/**
	 * @ticket BP8175
	 */
	public function test_last_activity_data_should_be_deleted_on_user_delete_non_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires non-multisite.' );
		}

		$u1 = self::factory()->user->create();

		$now = time();
		bp_update_user_last_activity( $u1, $now );

		$this->assertEquals( $now, bp_get_user_last_activity( $u1 ) );

		wp_delete_user( $u1 );

		$this->assertEquals( '', bp_get_user_last_activity( $u1 ) );
	}

	/**
	 * @ticket BP8175
	 */
	public function test_last_activity_data_should_be_deleted_on_user_delete_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$u1 = self::factory()->user->create();

		$now = time();
		bp_update_user_last_activity( $u1, $now );

		$this->assertEquals( $now, bp_get_user_last_activity( $u1 ) );

		wpmu_delete_user( $u1 );

		$this->assertEquals( '', bp_get_user_last_activity( $u1 ) );
	}

	/**
	 * @ticket BP8175
	 */
	public function test_last_activity_data_should_not_be_deleted_on_wp_delete_user_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( __METHOD__ . ' requires multisite.' );
		}

		$u1 = self::factory()->user->create();

		$now = time();
		bp_update_user_last_activity( $u1, $now );

		$this->assertEquals( $now, bp_get_user_last_activity( $u1 ) );

		wp_delete_user( $u1 );

		$this->assertEquals( $now, bp_get_user_last_activity( $u1 ) );
	}

	/**
	 * @group object_cache
	 * @group bp_core_get_directory_pages
	 */
	public function test_bp_members_get_user_url_after_directory_page_update() {
		// Generate user
		$user_id = self::factory()->user->create();
		$this->set_permalink_structure( '/%postname%/' );

		// Set object cache first for user domain
		$user_domain = bp_members_get_user_url( $user_id );

		// Now change the members directory slug
		$pages                   = bp_core_get_directory_pages();
		$members_page            = get_post( $pages->members->id );
		$new_members_slug        = 'new-members-slug';
		$members_page->post_name = $new_members_slug;
		$p                       = wp_update_post( $members_page );

		// Weird!
		if ( is_multisite() ) {
			$new_members_slug = get_post_field( 'post_name', $p );
		}

		// Go back to members directory page and recheck user domain
		$this->go_to( trailingslashit( home_url( $new_members_slug ) ) );
		$user = new WP_User( $user_id );

		$this->assertSame( home_url( $new_members_slug ) . '/' . $user->user_nicename . '/', bp_members_get_user_url( $user_id ) );
	}

	/**
	 * @group bp_core_get_user_displayname
	 */
	public function test_bp_core_get_user_displayname_empty_username() {
		$this->assertFalse( bp_core_get_user_displayname( '' ) );
	}

	/**
	 * @group bp_core_get_user_displayname
	 */
	public function test_bp_core_get_user_displayname_translate_username() {
		$u = self::factory()->user->create();

		$user = new WP_User( $u );

		$found = bp_core_get_user_displayname( $u );
		$this->assertNotEmpty( $found );
		$this->assertSame( $found, bp_core_get_user_displayname( $user->user_login ) );
	}

	/**
	 * @group bp_core_get_user_displayname
	 */
	public function test_bp_core_get_user_displayname_bad_username() {
		$this->assertFalse( bp_core_get_user_displayname( 'i_dont_exist' ) );
	}

	/**
	 * @group bp_core_get_user_displayname
	 */
	public function test_bp_core_get_user_displayname_xprofile_exists() {
		$xprofile_is_active                         = bp_is_active( 'xprofile' );
		buddypress()->active_components['xprofile'] = '1';

		$u = self::factory()->user->create();
		xprofile_set_field_data( 1, $u, 'Foo Foo' );

		$this->assertSame( 'Foo Foo', bp_core_get_user_displayname( $u ) );

		if ( ! $xprofile_is_active ) {
			unset( buddypress()->active_components['xprofile'] );
		}
	}

	/**
	 * @group bp_core_get_user_displaynames
	 */
	public function test_bp_core_get_user_displayname_arrays_all_bad_entries() {
		$this->assertSame( array(), bp_core_get_user_displaynames( array( 0, 'foo' ) ) );
	}

	/**
	 * @group bp_core_get_user_displaynames
	 */
	public function test_bp_core_get_user_displaynames_all_uncached() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		xprofile_set_field_data( 1, $u1, 'Foo' );
		xprofile_set_field_data( 1, $u2, 'Bar' );

		$expected = array(
			$u1 => 'Foo',
			$u2 => 'Bar',
		);

		$this->assertSame( $expected, bp_core_get_user_displaynames( array( $u1, $u2 ) ) );
	}

	/**
	 * @group bp_core_get_user_displaynames
	 */
	public function test_bp_core_get_user_displaynames_one_not_in_xprofile() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create(
			array(
				'display_name' => 'Bar',
			)
		);
		xprofile_set_field_data( 1, $u1, 'Foo' );

		$expected = array(
			$u1 => 'Foo',
			$u2 => 'Bar',
		);

		$this->assertSame( $expected, bp_core_get_user_displaynames( array( $u1, $u2 ) ) );
	}

	/**
	 * @group bp_members_migrate_signups
	 */
	public function test_bp_members_migrate_signups_standard() {
		$u     = self::factory()->user->create();
		$u_obj = new WP_User( $u );

		// Fake an old-style registration
		$key = wp_generate_password( 32, false );
		update_user_meta( $u, 'activation_key', $key );

		global $wpdb;
		$wpdb->update(
			$wpdb->users,
			array( 'user_status' => '2' ),
			array( 'ID' => $u ),
			array( '%d' ),
			array( '%d' )
		);
		clean_user_cache( $u );

		bp_members_migrate_signups();

		$found = BP_Signup::get();

		// Use email address as a sanity check
		$found_email = isset( $found['signups'][0]->user_email ) ? $found['signups'][0]->user_email : '';
		$this->assertSame( $u_obj->user_email, $found_email );

		// Check that activation keys match
		$found_key = isset( $found['signups'][0]->activation_key ) ? $found['signups'][0]->activation_key : '';
		$this->assertSame( $key, $found_key );
	}

	/**
	 * @group bp_members_migrate_signups
	 */
	public function test_bp_members_migrate_signups_activation_key_but_user_status_0() {
		$u     = self::factory()->user->create();
		$u_obj = new WP_User( $u );

		// Fake an old-style registration
		$key = wp_generate_password( 32, false );
		update_user_meta( $u, 'activation_key', $key );

		// ...but ensure that user_status is 0. This mimics the
		// behavior of certain plugins that disrupt the BP registration
		// flow
		global $wpdb;
		$wpdb->update(
			$wpdb->users,
			array( 'user_status' => '0' ),
			array( 'ID' => $u ),
			array( '%d' ),
			array( '%d' )
		);
		clean_user_cache( $u );

		bp_members_migrate_signups();

		// No migrations should have taken place
		$found = BP_Signup::get();
		$this->assertEmpty( $found['total'] );
	}

	/**
	 * @group bp_members_migrate_signups
	 */
	public function test_bp_members_migrate_signups_no_activation_key_but_user_status_2() {
		$u     = self::factory()->user->create();
		$u_obj = new WP_User( $u );

		// Fake an old-style registration but without an activation key
		global $wpdb;
		$wpdb->update(
			$wpdb->users,
			array( 'user_status' => '2' ),
			array( 'ID' => $u ),
			array( '%d' ),
			array( '%d' )
		);
		clean_user_cache( $u );

		bp_members_migrate_signups();

		// Use email address as a sanity check
		$found       = BP_Signup::get();
		$found_email = isset( $found['signups'][0]->user_email ) ? $found['signups'][0]->user_email : '';
		$this->assertSame( $u_obj->user_email, $found_email );
	}

	/**
	 * @group bp_last_activity_migrate
	 * @expectedIncorrectUsage update_user_meta( $user_id, 'last_activity' )
	 * @expectedIncorrectUsage get_user_meta( $user_id, 'last_activity' )
	 */
	public function test_bp_last_activity_migrate() {
		// We explicitly do not want last_activity created, so use the
		// WP factory methods
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$time = time();
		$t1   = date( 'Y-m-d H:i:s', $time - 50 );
		$t2   = date( 'Y-m-d H:i:s', $time - 500 );
		$t3   = date( 'Y-m-d H:i:s', $time - 5000 );

		update_user_meta( $u1, 'last_activity', $t1 );
		update_user_meta( $u2, 'last_activity', $t2 );
		update_user_meta( $u3, 'last_activity', $t3 );

		// Create an existing entry in last_activity to test no dupes
		global $wpdb;
		$bp = buddypress();
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$bp->members->table_name_last_activity}
				(`user_id`, `component`, `type`, `action`, `content`, `primary_link`, `item_id`, `date_recorded` ) VALUES
				( %d, %s, %s, %s, %s, %s, %d, %s )",
				$u2,
				$bp->members->id,
				'last_activity',
				'',
				'',
				'',
				0,
				$t1
			)
		);

		bp_last_activity_migrate();

		$expected = array(
			$u1 => $t1,
			$u2 => $t2,
			$u3 => $t3,
		);

		$found = array(
			$u1 => '',
			$u2 => '',
			$u3 => '',
		);

		foreach ( $found as $uid => $v ) {
			$found[ $uid ] = bp_get_user_last_activity( $uid );
		}

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group bp_core_get_userid_from_nicename
	 */
	public function test_bp_core_get_userid_from_nicename_failure() {
		$this->assertSame( null, bp_core_get_userid_from_nicename( 'non_existent_user' ) );
	}

	/**
	 * @group bp_update_user_last_activity
	 */
	public function test_bp_last_activity_multi_network() {

		// Filter the usermeta key
		add_filter( 'bp_get_user_meta_key', array( $this, 'filter_usermeta_key' ) );

		// We explicitly do not want last_activity created, so use the
		// WP factory methods
		$user = self::factory()->user->create();
		$time = date( 'Y-m-d H:i:s', time() - 50 );

		// Update last user activity
		bp_update_user_last_activity( $user, $time );

		// Setup parameters to assert to be the same
		$expected = $time;
		$found    = bp_get_user_last_activity( $user );

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group bp_update_user_last_activity
	 * @global wpdb $wpdb WordPress database object.
	 * @param  string $key
	 * @return string
	 */
	public function filter_usermeta_key( $key ) {
		global $wpdb;
		return $wpdb->prefix . $key;
	}

	/**
	 * @group bp_core_process_spammer_status
	 */
	public function test_bp_core_process_spammer_status() {
		if ( is_multisite() ) {
			$this->markTestSkipped();
		}

		$bp             = buddypress();
		$displayed_user = $bp->displayed_user;

		$u1                     = self::factory()->user->create();
		$bp->displayed_user->id = $u1;

		// Spam the user
		bp_core_process_spammer_status( $u1, 'spam' );

		$this->assertTrue( bp_is_user_spammer( $u1 ) );

		// Unspam the user
		bp_core_process_spammer_status( $u1, 'ham' );

		$this->assertFalse( bp_is_user_spammer( $u1 ) );

		// Reset displayed user
		$bp->displayed_user = $displayed_user;
	}

	/**
	 * @group bp_core_process_spammer_status
	 */
	public function test_bp_core_process_spammer_status_ms_bulk_spam() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped();
		}

		$bp             = buddypress();
		$displayed_user = $bp->displayed_user;

		$u1                     = self::factory()->user->create();
		$bp->displayed_user->id = $u1;

		// Bulk spam in network admin uses update_user_status
		bp_core_update_member_status( $u1, '1' );

		$this->assertTrue( bp_is_user_spammer( $u1 ) );

		// Unspam the user
		bp_core_process_spammer_status( $u1, 'ham' );

		$this->assertFalse( bp_is_user_spammer( $u1 ) );

		// Reset displayed user
		$bp->displayed_user = $displayed_user;
	}

	/**
	 * @group bp_core_process_spammer_status
	 */
	public function test_bp_core_process_spammer_status_ms_bulk_ham() {
		$this->skipWithoutMultisite();

		$bp             = buddypress();
		$displayed_user = $bp->displayed_user;

		$u1                     = self::factory()->user->create();
		$bp->displayed_user->id = $u1;

		// Spam the user
		bp_core_process_spammer_status( $u1, 'spam' );

		$this->assertTrue( bp_is_user_spammer( $u1 ) );

		// Bulk unspam in network admin uses update_user_status
		bp_core_update_member_status( $u1, '0' );

		$this->assertFalse( bp_is_user_spammer( $u1 ) );

		// Reset displayed user
		$bp->displayed_user = $displayed_user;
	}

	public function notification_filter_callback() {
		$this->filter_fired = current_action();
	}

	/**
	 * @group bp_core_process_spammer_status
	 */
	public function test_bp_core_process_spammer_status_make_spam_user_filter() {
		$u1 = self::factory()->user->create();

		add_action( 'make_spam_user', array( $this, 'notification_filter_callback' ) );

		bp_core_process_spammer_status( $u1, 'spam' );

		remove_action( 'make_spam_user', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'make_spam_user', $this->filter_fired );
	}

	public function test_bp_core_process_spammer_status_make_ham_user_filter() {
		$u1 = self::factory()->user->create();

		bp_core_process_spammer_status( $u1, 'spam' );

		add_action( 'make_ham_user', array( $this, 'notification_filter_callback' ) );

		bp_core_process_spammer_status( $u1, 'ham' );

		remove_action( 'make_ham_user', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'make_ham_user', $this->filter_fired );
	}

	public function test_bp_core_process_spammer_status_bp_make_spam_user_filter() {
		add_action( 'bp_make_spam_user', array( $this, 'notification_filter_callback' ) );

		$u1 = self::factory()->user->create();

		bp_core_process_spammer_status( $u1, 'spam' );

		remove_action( 'bp_make_spam_user', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_make_spam_user', $this->filter_fired );
	}

	public function test_bp_core_process_spammer_status_bp_make_ham_user_filter() {
		add_action( 'bp_make_ham_user', array( $this, 'notification_filter_callback' ) );

		$u1 = self::factory()->user->create();
		$n  = bp_core_process_spammer_status( $u1, 'ham' );

		remove_action( 'bp_make_ham_user', array( $this, 'notification_filter_callback' ) );

		$this->assertSame( 'bp_make_ham_user', $this->filter_fired );
	}

	/**
	 * @group bp_core_process_spammer_status
	 * @ticket BP8316
	 */
	public function test_bp_core_process_spammer_status_ms_should_only_spam_sites_with_one_admin() {
		$this->skipWithoutMultisite();

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$b1 = self::factory()->blog->create( array( 'user_id' => $u1 ) );

		// Add user 2 to site as administrator.
		add_user_to_blog( $b1, $u2, 'administrator' );

		// Mark user 2 as a spammer.
		bp_core_process_spammer_status( $u2, 'spam' );

		// Ensure site isn't marked as spam because there is more than one admin.
		$site = get_site( $b1 );
		$this->assertEmpty( $site->spam );
	}

	/**
	 * @ticket BP6208
	 *
	 * Note - it's not possible to test this when registration is not configured properly,
	 * because `bp_has_custom_signup_page()` stores its value in a static variable that cannot
	 * be toggled.
	 */
	public function test_wp_registration_url_should_return_bp_register_page_when_register_page_is_configured_properly() {
		$this->assertSame( bp_get_signup_page(), wp_registration_url() );
	}

	/**
	 * @group bp_members_validate_user_password
	 */
	public function test_bp_members_validate_user_password() {
		$validate = bp_members_validate_user_password( 'foobar', 'foobar' );

		$this->assertEmpty( $validate->get_error_message() );
	}

	/**
	 * @group bp_members_validate_user_password
	 */
	public function test_bp_members_validate_user_password_missing() {
		$validate = bp_members_validate_user_password( '', '' );

		$this->assertEquals( 'missing_user_password', $validate->get_error_code() );

		$validate = bp_members_validate_user_password( 'foobar', '' );

		$this->assertEquals( 'missing_user_password', $validate->get_error_code() );

		$validate = bp_members_validate_user_password( '', 'foobar' );

		$this->assertEquals( 'missing_user_password', $validate->get_error_code() );
	}

	/**
	 * @group bp_members_validate_user_password
	 */
	public function test_bp_members_validate_user_password_mismatching() {
		$validate = bp_members_validate_user_password( 'foobar', 'barfoo' );

		$this->assertEquals( 'mismatching_user_password', $validate->get_error_code() );
	}

	/**
	 * @group bp_members_validate_user_password
	 */
	public function test_bp_members_validate_user_password_too_short() {
		add_filter( 'bp_members_validate_user_password', array( $this, 'filter_bp_members_validate_user_password' ), 10, 2 );

		$validate = bp_members_validate_user_password( 'one', 'one' );

		remove_filter( 'bp_members_validate_user_password', array( $this, 'filter_bp_members_validate_user_password' ), 10, 2 );

		$this->assertEquals( 'too_short_user_password', $validate->get_error_code() );
	}

	function filter_bp_members_validate_user_password( $errors, $pass ) {
		if ( 4 > strlen( $pass ) ) {
			$errors->add( 'too_short_user_password', __( 'Your password is too short.', 'buddypress' ) );
		}

		return $errors;
	}

	/**
	 * @group bp_core_activate_signup
	 */
	public function test_bp_core_activate_signup_password() {
		global $wpdb;

		$signups = array(
			'no-blog' =>
			array(
				'signup_id' => self::factory()->signup->create(
					array(
						'user_login'     => 'noblog',
						'user_email'     => 'noblog@example.com',
						'activation_key' => 'no-blog',
						'meta'           => array(
							'field_1'  => 'Foo Bar',
							'password' => 'foobar',
						),
					)
				),
				'password'  => 'foobar',
			),
		);

		if ( is_multisite() ) {
			$signups['ms-blog'] = array(
				'signup_id' => self::factory()->signup->create(
					array(
						'user_login'     => 'msblog',
						'user_email'     => 'msblog@example.com',
						'domain'         => get_current_site()->domain,
						'path'           => get_current_site()->path . 'ms-blog',
						'title'          => 'Ding Dang',
						'activation_key' => 'ms-blog',
						'meta'           => array(
							'field_1'  => 'Ding Dang',
							'password' => 'dingdang',
						),
					)
				),
				'password'  => 'dingdang',
			);
		}

		// Neutralize db errors
		$suppress = $wpdb->suppress_errors();

		foreach ( $signups as $key => $data ) {
			$u = bp_core_activate_signup( $key );

			$this->assertEquals( get_userdata( $u )->user_pass, $data['password'] );
		}

		$wpdb->suppress_errors( $suppress );
	}

	/**
	 * @ticket BP7461
	 *
	 * Test function before and after adding custom illegal names from WordPress.
	 */
	public function test_bp_core_get_illegal_names() {

		// Making sure BP custom illegals are in the array.
		$this->assertTrue( in_array( 'profile', bp_core_get_illegal_names(), true ) );
		$this->assertTrue( in_array( 'forums', bp_core_get_illegal_names(), true ) );

		add_filter( 'illegal_user_logins', array( $this, '_illegal_user_logins' ) );

		// Testing fake custom illegal names.
		$this->assertTrue( in_array( 'testuser', bp_core_get_illegal_names(), true ) );
		$this->assertTrue( in_array( 'admins', bp_core_get_illegal_names(), true ) );
		$this->assertFalse( in_array( 'buddypresss', bp_core_get_illegal_names(), true ) );

		// Making sure BP custom illegals are in the array after including the custom ones.
		$this->assertTrue( in_array( 'profile', bp_core_get_illegal_names(), true ) );
		$this->assertTrue( in_array( 'forums', bp_core_get_illegal_names(), true ) );

		remove_filter( 'illegal_user_logins', array( $this, '_illegal_user_logins' ) );
	}

	public function _illegal_user_logins() {
		return array(
			'testuser',
			'admins',
			'buddypress',
		);
	}

	/**
	 * @group bp_core_activate_signup
	 */
	public function test_bp_core_activate_signup_should_add_user_role() {
		$key = 'test';

		// Create the signup.
		self::factory()->signup->create(
			array(
				'user_login'     => 'test',
				'user_email'     => 'test@example.com',
				'activation_key' => $key,
				'meta'           => array(
					'field_1'  => 'Foo Bar',
					'password' => 'foobar',
				),
			)
		);

		// Activate user.
		$user_id = bp_core_activate_signup( $key );

		// Assert that user has a role.
		$user = get_userdata( $user_id );
		$this->assertNotEmpty( $user->roles );
	}

	/**
	 * @ticket BP6155
	 */
	public function test_bp_core_get_active_member_count_excludes_spammed_users() {
		$this->assertSame( 0, bp_core_get_active_member_count() );
		$this->assertSame( 0, bp_get_total_member_count() );

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// The two users were created, but they are not "active" yet.
		$this->assertSame( 0, bp_core_get_active_member_count() );
		$this->assertSame( 0, bp_get_total_member_count() );

		// Fake their first activity to make them "active".
		do_action( 'bp_first_activity_for_member', $u1 );
		do_action( 'bp_first_activity_for_member', $u2 );

		$this->assertSame( 2, bp_core_get_active_member_count() );
		$this->assertSame( 2, bp_get_total_member_count() );

		// Spam user 2.
		if ( is_multisite() ) {
			wp_update_user(
				array(
					'ID'   => $u2,
					'spam' => '1',
				)
			);
		} else {
			bp_core_process_spammer_status( $u2, 'spam' );
		}

		// Check if user 2 is a spammer.
		$this->assertTrue( bp_is_user_spammer( $u2 ) );

		// Recount the active member count.
		$this->assertSame( 1, bp_core_get_active_member_count() );
		$this->assertSame( 1, bp_get_total_member_count() );

		// Delete user 1.
		if ( is_multisite() ) {
			wpmu_delete_user( $u1 );
		} else {
			wp_delete_user( $u1 );
		}

		$this->assertSame( 0, bp_core_get_active_member_count() );
		$this->assertSame( 0, bp_get_total_member_count() );
	}

	/**
	 * @dataProvider provider_bp_core_validate_user_signup_errors
	 *
	 * @param string   $user_name User name to validate.
	 * @param string   $user_email User email to validate.
	 * @param WP_Error $expected_error Expected error message.
	 */
	public function test_bp_core_validate_user_signup_errors( $user_name, $user_email, $expected_error ) {
		$this->skipWithMultisite();

		$validate = bp_core_validate_user_signup( $user_name, $user_email );

		$this->assertSame( $user_email, $validate['user_email'] );
		$this->assertSame( $user_name, $validate['user_name'] );
		$this->assertSame( $expected_error, $validate['errors']->get_error_message() );
	}

	/**
	 * Provider for the test_bp_core_validate_user_signup_errors() test.
	 *
	 * @return array[]
	 */
	public function provider_bp_core_validate_user_signup_errors() {
		return array(
			array( '', 'test@example.com', 'Please enter a username' ),
			array( '@-t', 'test@example.com', 'Username must be at least 4 characters.' ),
			array( '@-', 'test@example.com', 'Username must be at least 4 characters.' ),
			array( '@-@-', 'test@example.com', 'Sorry, usernames must have letters too!' ),
			array( '4343543', 'test@example.com', 'Sorry, usernames must have letters too!' ),
			array( 'cool-test', 'example.com', 'Please check your email address.' ),
			array( 'test&', 'test@example.com', 'Usernames can contain only letters, numbers, ., -, and @' ),
			array( 'test_', 'test@example.com', 'Sorry, usernames may not contain the character "_"!' ),
			array( '4te343543', 'test@example.com', '' ),
			array( 'g4te343543', 'test@example.com', '' ),
		);
	}
}
