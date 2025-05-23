<?php
/**
 * @group members
 * @group routing
 */
class BP_Tests_Routing_Members extends BP_UnitTestCase {
	protected $old_current_user = 0;
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();

		buddypress()->members->types = array();
		$this->old_current_user = get_current_user_id();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
		wp_set_current_user( self::factory()->user->create( array( 'user_login' => 'paulgibbs', 'role' => 'subscriber' ) ) );
	}

	public function tear_down() {
		wp_set_current_user( $this->old_current_user );
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	function test_members_directory() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( bp_get_members_directory_permalink() );

		$pages        = bp_core_get_directory_pages();
		$component_id = bp_current_component();

		$this->assertEquals( bp_get_members_root_slug(), $pages->{$component_id}->slug );
	}

	function test_member_permalink() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( bp_members_get_user_url( bp_loggedin_user_id() ) );
		$this->assertTrue( bp_is_my_profile() );
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_with_member_type() {
		$this->set_permalink_structure( '/%postname%/' );
		bp_register_member_type( 'foo' );
		$url = bp_get_member_type_directory_permalink( 'foo' );
		$this->go_to( $url );
		$this->assertTrue( bp_is_members_component() );
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_with_member_type_should_obey_filtered_type_slug() {
		$this->set_permalink_structure( '/%postname%/' );
		bp_register_member_type( 'foo' );

		add_filter( 'bp_members_member_type_base', array( $this, 'filter_member_type_base' ) );

		$url = bp_get_member_type_directory_permalink( 'foo' );

		remove_filter( 'bp_members_member_type_base', array( $this, 'filter_member_type_base' ) );

		$this->assertSame( $url, 'http://' . trailingslashit( WP_TESTS_DOMAIN ) . 'members/bp-member-type/foo/' );
	}

	public function filter_member_type_base( $base ) {
		return 'bp-member-type';
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_with_member_type_that_has_custom_directory_slug() {
		$this->set_permalink_structure( '/%postname%/' );
		bp_register_member_type( 'foo', array( 'has_directory' => 'foos' ) );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foos/' );
		$this->assertTrue( bp_is_members_component() );
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_with_member_type_should_be_overridden_by_member_with_same_nicename() {
		$this->set_permalink_structure( '/%postname%/' );
		$u = self::factory()->user->create( array( 'user_nicename' => 'foo' ) );
		bp_register_member_type( 'foo' );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foo/' );

		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @ticket BP6286
	 * @group member_types
	 */
	public function test_member_directory_should_404_for_member_types_that_have_no_directory() {
		$this->set_permalink_structure( '/%postname%/' );
		bp_register_member_type( 'foo', array( 'has_directory' => false ) );
		$this->go_to( bp_get_members_directory_permalink() . 'type/foo/' );
		$this->assertTrue( is_404() );
	}

	/**
	 * @ticket BP6325
	 */
	function test_members_shortlink_redirector() {
		$this->set_permalink_structure( '/%postname%/' );
		$shortlink_member_slug = 'me';

		$this->go_to( bp_get_members_directory_permalink() . $shortlink_member_slug );

		$this->assertSame( get_current_user_id(), bp_displayed_user_id() );
	}

	public function filter_root_slug( $root_slug ) {
		return 'community/' . $root_slug;
	}

	/**
	 * @ticket BP9063
	 */
	public function test_members_registration_page_filtered() {
		$this->set_permalink_structure( '/%postname%/' );

		add_filter( 'bp_members_register_root_slug', array( $this, 'filter_root_slug' ) );
		bp_delete_rewrite_rules();

		// Regenerate rewrite rules.
		$this->go_to( home_url() );
		$bp_registration_url = bp_get_signup_page();

		remove_filter( 'bp_members_register_root_slug', array( $this, 'filter_root_slug' ) );

		$this->go_to( $bp_registration_url );

		$this->assertTrue( false !== strpos( $bp_registration_url, 'community' ) );
		$this->assertTrue( bp_is_register_page() );
	}

	/**
	 * @ticket BP9063
	 */
	public function test_members_registration_page() {
		$this->set_permalink_structure( '/%postname%/' );
		$bp_registration_url = bp_get_signup_page();

		$this->go_to( $bp_registration_url );

		$this->assertTrue( false === strpos( $bp_registration_url, 'community' ) );
		$this->assertTrue( bp_is_register_page() );
	}

	/**
	 * @ticket BP9063
	 */
	public function test_members_activation_page_filtered() {
		$this->set_permalink_structure( '/%postname%/' );

		add_filter( 'bp_members_activate_root_slug', array( $this, 'filter_root_slug' ) );
		bp_delete_rewrite_rules();

		// Regenerate rewrite rules.
		$this->go_to( home_url() );
		$bp_activation_url = bp_get_activation_page();

		remove_filter( 'bp_members_activate_root_slug', array( $this, 'filter_root_slug' ) );

		$this->go_to( $bp_activation_url );

		$this->assertTrue( false !== strpos( $bp_activation_url, 'community' ) );
		$this->assertTrue( bp_is_activation_page() );
	}

	/**
	 * @ticket BP9063
	 */
	public function test_members_activation_page() {
		$this->set_permalink_structure( '/%postname%/' );
		$bp_activation_url = bp_get_activation_page();

		$this->go_to( $bp_activation_url );

		$this->assertTrue( false === strpos( $bp_activation_url, 'community' ) );
		$this->assertTrue( bp_is_activation_page() );
	}
}
