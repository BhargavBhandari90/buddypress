<?php
/**
 * Group Avatar Controller Tests.
 *
 * @group groups
 * @group group-avatar
 * @group attachments
 */
class BP_Tests_Group_Avatar_REST_Controller extends BP_Test_REST_Controller_Testcase {
	protected $image_file;
	protected $group_id;
	protected $controller = 'BP_Groups_Avatar_REST_Controller';
	protected $handle     = 'groups';

	public function set_up() {
		parent::set_up();

		$this->image_file = BP_TESTS_DIR . 'assets/test-image-large.jpg';

		$this->group_id = $this->bp::factory()->group->create(
			array(
				'name'        => 'Group Test',
				'description' => 'Group Description',
				'creator_id'  => $this->user,
			)
		);
	}

	public function test_register_routes() {
		$routes   = $this->server->get_routes();
		$endpoint = $this->endpoint_url . '/(?P<group_id>[\d]+)/avatar';

		// Single.
		$this->assertArrayHasKey( $endpoint, $routes );
		$this->assertCount( 3, $routes[ $endpoint ] );
	}

	/**
	 * @group get_items
	 */
	public function test_get_items() {
		$this->markTestSkipped();
	}

	/**
	 * @group get_item
	 */
	public function test_get_item() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		$this->assertNotEmpty( $all_data );

		$this->assertTrue( isset( $all_data['full'] ) && isset( $all_data['thumb'] ) );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_support_for_the_community_visibility() {
		toggle_component_visibility();

		$request = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_invalid_group_id() {
		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group get_item
	 */
	public function test_get_item_with_no_image() {

		// Disable default url.
		add_filter( 'bp_core_fetch_avatar_url', '__return_false' );

		$request  = new WP_REST_Request( 'GET', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_attachments_group_avatar_no_image', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item() {
		if ( 4.9 > (float) $GLOBALS['wp_version'] ) {
			$this->markTestSkipped();
		}

		$reset_files = $_FILES;
		$reset_post  = $_POST;

		wp_set_current_user( $this->user );

		add_filter( 'pre_move_uploaded_file', array( $this, 'copy_file' ), 10, 3 );
		add_filter( 'bp_core_avatar_dimension', array( $this, 'return_100' ) );

		$_FILES['file'] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'test-image-large.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $this->image_file ),
		);

		$_POST['action'] = 'bp_avatar_upload';

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_file_params( $_FILES );
		$response = $this->server->dispatch( $request );

		remove_filter( 'pre_move_uploaded_file', array( $this, 'copy_file' ) );
		remove_filter( 'bp_core_avatar_dimension', array( $this, 'return_100' ) );

		$avatar = $response->get_data();

		$this->assertSame(
			$avatar,
			array(
				'full'  => bp_core_fetch_avatar(
					array(
						'object'  => 'group',
						'type'    => 'full',
						'item_id' => $this->group_id,
						'html'    => false,
					)
				),
				'thumb' => bp_core_fetch_avatar(
					array(
						'object'  => 'group',
						'type'    => 'thumb',
						'item_id' => $this->group_id,
						'html'    => false,
					)
				),
			)
		);

		$_FILES = $reset_files;
		$_POST  = $reset_post;
	}

	public function copy_file( $return, $file, $new_file ) {
		return @copy( $file['tmp_name'], $new_file );
	}

	public function return_100() {
		return 100;
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_with_image_upload_disabled() {
		if ( 4.9 > (float) $GLOBALS['wp_version'] ) {
			$this->markTestSkipped();
		}

		$reset_files = $_FILES;
		$reset_post  = $_POST;

		wp_set_current_user( $this->user );

		// Disabling group avatar upload.
		add_filter( 'bp_disable_group_avatar_uploads', '__return_true' );

		$_FILES['file'] = array(
			'tmp_name' => $this->image_file,
			'name'     => 'test-image-large.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $this->image_file ),
		);

		$_POST['action'] = 'bp_avatar_upload';

		$request = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_file_params( $_FILES );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_attachments_group_avatar_disabled', $response, 500 );

		remove_filter( 'bp_disable_group_avatar_uploads', '__return_true' );
		$_FILES = $reset_files;
		$_POST  = $reset_post;
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_empty_image() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_attachments_group_avatar_no_image_file', $response, 500 );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_user_not_logged_in() {
		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group create_item
	 */
	public function test_create_item_invalid_group() {
		$u1 = $this->bp::factory()->user->create();

		wp_set_current_user( $u1 );

		$request  = new WP_REST_Request( 'POST', sprintf( $this->endpoint_url . '/%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group update_item
	 */
	public function test_update_item() {
		$this->markTestSkipped();
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item() {
		$this->markTestSkipped();
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_failed() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'bp_rest_attachments_group_avatar_no_uploaded_avatar', $response, 404 );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_user_not_logged_in() {
		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_authorization_required', $response, rest_authorization_required_code() );
	}

	/**
	 * @group delete_item
	 */
	public function test_delete_item_invalid_group() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'DELETE', sprintf( $this->endpoint_url . '/%d/avatar', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'bp_rest_group_invalid_id', $response, 404 );
	}

	/**
	 * @group prepare_item
	 */
	public function test_prepare_item() {
		$this->markTestSkipped();
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 2, count( $properties ) );
		$this->assertArrayHasKey( 'full', $properties );
		$this->assertArrayHasKey( 'thumb', $properties );
	}

	public function test_context_param() {
		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', sprintf( $this->endpoint_url . '/%d/avatar', $this->group_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertNotEmpty( $data );
	}
}
