<?php
/**
 * @group groups
 * @group BP_Groups_Group
 */
#[AllowDynamicProperties]
class BP_Tests_BP_Groups_Group_TestCases extends BP_UnitTestCase {

	/**
	 * @group __construct
	 */
	public function test_non_existent_group() {
		$group = new BP_Groups_Group( 123456789 );
		$this->assertSame( 0, $group->id );
	}

	/**
	 * @group __construct
	 * @expectedDeprecated BP_Groups_Group::__construct
	 */
	public function test_deprecated_arg() {
		$group = new BP_Groups_Group( 123456789, array( 'populate_extras' => true ) );
		$this->assertSame( 0, $group->id );
	}

	/** get() ************************************************************/

	/**
	 * @group get
	 */
	public function test_get_group_id_with_slug() {
		$slug     = 'group-test';
		$g1       = self::factory()->group->create( array( 'slug' => $slug ) );
		$group_id = BP_Groups_Group::group_exists( $slug );

		$this->assertSame( $g1, $group_id );
	}

	/**
	 * @group get
	 */
	public function test_get_group_id_with_empty_slug() {
		$this->assertFalse( BP_Groups_Group::group_exists( '' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_group_id_from_slug_with_empty_slug() {
		$this->assertFalse( BP_Groups_Group::get_id_from_slug( '' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_group_id_from_slug() {
		$slug     = 'group-test';
		$g1       = self::factory()->group->create( array( 'slug' => $slug ) );
		$group_id = BP_Groups_Group::get_id_from_slug( $slug );

		$this->assertSame( $g1, $group_id );
	}

	/**
	 * @group get
	 * @expectedDeprecated BP_Groups_Group::group_exists
	 */
	public function test_get_group_with_slug_with_deprecated_args() {
		$slug     = 'group-test';
		$g1       = self::factory()->group->create( array( 'slug' => $slug ) );
		$group_id = BP_Groups_Group::group_exists( $slug, 'random-name' );

		$this->assertSame( $g1, $group_id );
	}

	/**
	 * @group get
	 */
	public function test_get_with_exclude() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'exclude' => array(
				$g1,
				'foobar',
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g2 ) );
	}

	/**
	 * @group get
	 */
	public function test_get_with_include() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'include' => array(
				$g1,
				'foobar',
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1 ) );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 */
	public function test_get_with_meta_query() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1 ) );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 */
	public function test_get_empty_meta_query() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		groups_update_groupmeta( $g1, 'foo', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1, $g2, ) );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 */
	public function test_get_with_meta_query_multiple_clauses() {
		$now = time();
		$g1 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60 ),
		) );
		$g2 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*2 ),
		) );
		$g3 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*3 ),
		) );
		groups_update_groupmeta( $g1, 'foo', 'bar' );
		groups_update_groupmeta( $g2, 'foo', 'bar' );
		groups_update_groupmeta( $g1, 'bar', 'barry' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
				array(
					'key' => 'bar',
					'value' => 'barry',
				),
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1 ) );
		$this->assertEquals( 1, $groups['total'] );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 */
	public function test_get_with_meta_query_multiple_clauses_relation_or() {
		$now = time();
		$g1 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60 ),
		) );
		$g2 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*2 ),
		) );
		$g3 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*3 ),
		) );
		groups_update_groupmeta( $g1, 'foo', 'bar' );
		groups_update_groupmeta( $g2, 'foo', 'baz' );
		groups_update_groupmeta( $g3, 'bar', 'barry' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
				array(
					'key' => 'bar',
					'value' => 'barry',
				),
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g3 ), $ids );
		$this->assertEquals( 2, $groups['total'] );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 * @ticket BP5874
	 */
	public function test_get_with_meta_query_multiple_clauses_relation_or_shared_meta_key() {
		$now = time();
		$g1 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60 ),
		) );
		$g2 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*2 ),
		) );
		$g3 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*3 ),
		) );
		groups_update_groupmeta( $g1, 'foo', 'bar' );
		groups_update_groupmeta( $g2, 'foo', 'baz' );
		groups_update_groupmeta( $g3, 'foo', 'barry' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
				array(
					'key' => 'foo',
					'value' => 'baz',
				),
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g2 ), $ids );
		$this->assertEquals( 2, $groups['total'] );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 * @ticket BP5824
	 */
	public function test_get_with_meta_query_multiple_keys_with_same_value() {
		$now = time();
		$g1 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60 ),
		) );
		$g2 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*2 ),
		) );
		groups_update_groupmeta( $g1, 'foo', 'bar' );
		groups_update_groupmeta( $g2, 'foo2', 'bar' );

		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
					'compare' => '=',
				),
			),
		) );
		$ids = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( $ids, array( $g1 ) );
		$this->assertEquals( 1, $groups['total'] );
	}

	/**
	 * @group get
	 * @group date_query
	 */
	public function test_get_with_date_query_before() {
		$u1 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', time() ),
		) );
		$u2 = self::factory()->group->create( array(
			'last_activity' => '2008-03-25 17:13:55',
		) );
		$u3 = self::factory()->group->create( array(
			'last_activity' => '2010-01-01 12:00',
		) );

		// 'date_query' before test
		$groups = BP_Groups_Group::get( array(
			'type' => 'active',
			'date_query' => array( array(
				'before' => array(
					'year'  => 2010,
					'month' => 1,
					'day'   => 1,
				),
			) )
		) );

		$this->assertEquals( [ $u2 ], wp_list_pluck( $groups['groups'], 'id' ) );
	}

	/**
	 * @group get
	 * @group date_query
	 */
	public function test_get_with_date_query_range() {
		$u1 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', time() ),
		) );
		$u2 = self::factory()->group->create( array(
			'last_activity' => '2008-03-25 17:13:55',
		) );
		$u3 = self::factory()->group->create( array(
			'last_activity' => '2001-01-01 12:00',
		) );

		// 'date_query' range test
		$groups = BP_Groups_Group::get( array(
			'type' => 'active',
			'date_query' => array( array(
				'after'  => 'January 2nd, 2001',
				'before' => array(
					'year'  => 2010,
					'month' => 1,
					'day'   => 1,
				),
				'inclusive' => true,
			) )
		) );

		$this->assertEquals( [ $u2 ], wp_list_pluck( $groups['groups'], 'id' ) );
	}

	/**
	 * @group get
	 * @group date_query
	 */
	public function test_get_with_date_query_after() {
		$u1 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', time() ),
		) );
		$u2 = self::factory()->group->create( array(
			'last_activity' => '2008-03-25 17:13:55',
		) );
		$u3 = self::factory()->group->create( array(
			'last_activity' => '2001-01-01 12:00',
		) );

		// 'date_query' after and relative test
		$groups = BP_Groups_Group::get( array(
			'type' => 'active',
			'date_query' => array( array(
				'after' => '1 day ago'
			) )
		) );

		$this->assertEquals( [ $u1 ], wp_list_pluck( $groups['groups'], 'id' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_normal_search() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => 'This is one cool group',
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => 'Cool',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_with_underscores() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => '_cool_ dude',
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => '_cool_',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_with_percent_sign() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => '100% awesome',
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => '100%',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_with_quotes() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => "'tis sweet",
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => "'tis ",
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_with_left_wildcard() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Ye Lads',
			'description' => "My Bonnie lies over the ocean",
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => "*ads",
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_with_left_wildcard_should_miss() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Ye Lads',
			'description' => "My Bonnie lies over the ocean",
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => "*la",
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array(), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_with_right_wildcard() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Ye Lads',
			'description' => "My Bonnie lies over the ocean",
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => "Ye*",
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_with_right_wildcard_should_miss() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Ye Lads',
			'description' => "My Bonnie lies over the ocean",
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => "la*",
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array(), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_with_both_wildcard() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Ye Lads',
			'description' => "My Bonnie lies over the ocean",
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'search_terms' => "*la*",
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_limited_to_name_column() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Ye Lads',
			'description' => "My Bonnie lies over the ocean",
		) );
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create( array(
			'name' => 'Bonnie Lasses',
			'description' => "That lad is unknown to me",
		) );

		$groups = BP_Groups_Group::get( array(
			'search_terms'   => "lad",
			'search_columns' => array( 'name' ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g1 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_search_limited_to_description_column() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Ye Lads',
			'description' => "My Bonnie lies over the ocean",
		) );
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create( array(
			'name' => 'Bonnie Lasses',
			'description' => "That lad is unknown to me",
		) );

		$groups = BP_Groups_Group::get( array(
			'search_terms'   => "lad",
			'search_columns' => array( 'description' ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g3 ), $found );
	}

	/**
	 * BP 1.8 will change the default 'type' param in favor of default
	 * 'order' and 'orderby'. This is to make sure that existing plugins
	 * will work appropriately
	 *
	 * @group get
	 */
	public function test_get_with_default_type_value_should_be_newest() {
		$g1 = self::factory()->group->create( array(
			'name' => 'A Group',
			'date_created' => bp_core_current_time(),
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'D Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', time() - 100 ),
		) );
		$g3 = self::factory()->group->create( array(
			'name' => 'B Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', time() - 100000 ),
		) );
		$g4 = self::factory()->group->create( array(
			'name' => 'C Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );

		$found = BP_Groups_Group::get();

		$this->assertEquals( BP_Groups_Group::get( array( 'type' => 'newest' ) ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_with_type_newest() {
		$time = time();
		$g1 = self::factory()->group->create( array(
			'name' => 'A Group',
			'date_created' => bp_core_current_time(),
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'D Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );
		$g3 = self::factory()->group->create( array(
			'name' => 'B Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100000 ),
		) );
		$g4 = self::factory()->group->create( array(
			'name' => 'C Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 1000 ),
		) );

		$groups = BP_Groups_Group::get( array( 'type' => 'newest' ) );
		$found = wp_parse_id_list( wp_list_pluck( $groups['groups'], 'id' ) );
		$this->assertEquals( array( $g1, $g2, $g4, $g3 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_with_type_popular() {
		$time = time();
		$g1 = self::factory()->group->create( array(
			'name' => 'A Group',
			'date_created' => bp_core_current_time(),
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'D Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );
		$g3 = self::factory()->group->create( array(
			'name' => 'B Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100000 ),
		) );
		$g4 = self::factory()->group->create( array(
			'name' => 'C Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 1000 ),
		) );

		groups_update_groupmeta( $g1, 'total_member_count', 1 );
		groups_update_groupmeta( $g2, 'total_member_count', 4 );
		groups_update_groupmeta( $g3, 'total_member_count', 2 );
		groups_update_groupmeta( $g4, 'total_member_count', 3 );

		$groups = BP_Groups_Group::get( array( 'type' => 'popular' ) );
		$found = wp_parse_id_list( wp_list_pluck( $groups['groups'], 'id' ) );
		$this->assertEquals( array( $g2, $g4, $g3, $g1 ), $found );
	}

	/**
	 * @group get
	 */
	public function test_get_with_type_alphabetical() {
		$time = time();
		$g1 = self::factory()->group->create( array(
			'name' => 'A Group',
			'date_created' => bp_core_current_time(),
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'D Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );
		$g3 = self::factory()->group->create( array(
			'name' => 'B Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100000 ),
		) );
		$g4 = self::factory()->group->create( array(
			'name' => 'C Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 1000 ),
		) );

		$groups = BP_Groups_Group::get( array( 'type' => 'alphabetical' ) );
		$found = wp_parse_id_list( wp_list_pluck( $groups['groups'], 'id' ) );
		$this->assertEquals( array( $g1, $g3, $g4, $g2 ), $found );
	}

	/**
	 * @group get
	 * @group group_meta_query
	 * @ticket BP5099
	 */
	public function test_meta_query_and_total_groups() {
		$time = time();

		$g1 = self::factory()->group->create( array(
			'name' => 'A Group',
			'date_created' => bp_core_current_time(),
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'D Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );
		$g3 = self::factory()->group->create( array(
			'name' => 'B Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100000 ),
		) );
		$g4 = self::factory()->group->create( array(
			'name' => 'C Group',
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 1000 ),
		) );

		// mark one group with the metakey 'supergroup'
		groups_update_groupmeta( $g1, 'supergroup', 1 );

		// fetch groups with our 'supergroup' metakey
		$groups = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key'     => 'supergroup',
					'compare' => 'EXISTS',
				)
			)
		) );

		// group total should match 1
		$this->assertEquals( '1', $groups['total'] );
	}

	/**
	 * @group get
	 * @ticket BP5477
	 */
	public function test_get_groups_page_perpage_params() {
		// Create more than 20 groups (20 is the default per_page number)
		$group_ids = array();

		for ( $i = 1; $i <= 25; $i++ ) {
			$group_ids[] = self::factory()->group->create();
		}

		// Tests
		// Passing false to 'per_page' and 'page' should result in pagination not being applied
		$groups = BP_Groups_Group::get( array(
			'per_page' => false,
			'page'     => false
		) );

		// Should return all groups; "paged" group total should be 25
		$this->assertEquals( count( $group_ids ), count( $groups['groups'] ) );

		unset( $groups );

		// Passing 'per_page' => -1 should result in pagination not being applied.
		$groups = BP_Groups_Group::get( array(
			'per_page' => -1
		) );

		// Should return all groups; "paged" group total should match 25
		$this->assertEquals( count( $group_ids ), count( $groups['groups'] ) );

		unset( $groups );

		// If "per_page" and "page" are both set, should result in pagination being applied.
		$groups = BP_Groups_Group::get( array(
			'per_page' => 12,
			'page'     => 1
		) );

		// Should return top 12 groups only
		$this->assertEquals( '12', count( $groups['groups'] ) );
	}

	/**
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_queries_should_be_cached() {
		global $wpdb;

		$g = self::factory()->group->create();

		$found1 = BP_Groups_Group::get();

		$num_queries = $wpdb->num_queries;

		$found2 = BP_Groups_Group::get();

		$this->assertEqualSets( $found1, $found2 );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_groupmeta_update() {
		global $wpdb;

		$groups = self::factory()->group->create_many( 2 );
		groups_update_groupmeta( $groups[0], 'foo', 'bar' );
		groups_update_groupmeta( $groups[1], 'foo', 'bar' );

		$found1 = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );

		$this->assertEqualSets( array( $groups[0], $groups[1] ), wp_list_pluck( $found1['groups'], 'id' ) );

		groups_update_groupmeta( $groups[1], 'foo', 'baz' );

		$found2 = BP_Groups_Group::get( array(
			'meta_query' => array(
				array(
					'key' => 'foo',
					'value' => 'bar',
				),
			),
		) );

		$this->assertEqualSets( array( $groups[0] ), wp_list_pluck( $found2['groups'], 'id' ) );
	}

	/**
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_group_save() {
		$groups = self::factory()->group->create_many( 2 );
		groups_update_groupmeta( $groups[0], 'foo', 'bar' );
		groups_update_groupmeta( $groups[1], 'foo', 'bar' );

		$found1 = BP_Groups_Group::get( array(
			'search_terms' => 'Foo',
		) );

		$this->assertEmpty( $found1['groups'] );

		$group0 = groups_get_group( $groups[0] );
		$group0->name = 'Foo';
		$group0->save();

		$found2 = BP_Groups_Group::get( array(
			'search_terms' => 'Foo',
		) );

		$this->assertEqualSets( array( $groups[0] ), wp_list_pluck( $found2['groups'], 'id' ) );
	}

	/**
	 * @group cache
	 * @ticket BP5451
	 * @ticket BP6643
	 */
	public function test_get_query_caches_should_be_busted_by_group_delete() {
		$groups = self::factory()->group->create_many( 2 );

		$found1 = BP_Groups_Group::get();

		$this->assertEqualSets( $groups, wp_list_pluck( $found1['groups'], 'id' ) );

		$group0 = groups_get_group( $groups[0] );
		$group0->delete();

		$found2 = BP_Groups_Group::get();

		$this->assertEqualSets( array( $groups[1] ), wp_list_pluck( $found2['groups'], 'id' ) );
	}

	/**
	 * @ticket BP5451
	 */
	public function test_bp_groups_group_magic_isset_with_empty_check() {
		$this->old_current_user = get_current_user_id();

		$u = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'creator_id' => $u ) );

		// Instantiate group object.
		wp_set_current_user( $u );
		$group = new BP_Groups_Group( $g );

		// Assert ! empty() check is not false.
		$this->assertTrue( ! empty( $group->is_member ) );

		wp_set_current_user( $this->old_current_user );
	}

	/** convert_type_to_order_orderby() **********************************/

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_newest() {
		$expected = array(
			'order' => 'DESC',
			'orderby' => 'date_created',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'newest' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_active() {
		$expected = array(
			'order' => 'DESC',
			'orderby' => 'last_activity',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'active' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_popular() {
		$expected = array(
			'order' => 'DESC',
			'orderby' => 'total_member_count',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'popular' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_alphabetical() {
		$expected = array(
			'order' => 'ASC',
			'orderby' => 'name',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'alphabetical' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_random() {
		$expected = array(
			// order gets thrown out
			'order' => '',
			'orderby' => 'random',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'random' ) );
	}

	/**
	 * @group convert_type_to_order_orderby
	 */
	public function test_convert_type_to_order_orderby_invalid() {
		$expected = array(
			'order' => '',
			'orderby' => '',
		);
		$this->assertEquals( $expected, _BP_Groups_Group::_convert_type_to_order_orderby( 'foooooooooooooooobar' ) );
	}

	/** convert_orderby_to_order_by_term() **********************************/

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_date_created() {
		$this->assertEquals( 'g.date_created', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'date_created' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_last_activity() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( 'gm_last_activity.meta_value', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'last_activity' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_total_member_count() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( 'CONVERT(gm_total_member_count.meta_value, SIGNED)', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'total_member_count' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_name() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( 'g.name', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'name' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_random() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( 'rand()', _BP_Groups_Group::_convert_orderby_to_order_by_term( 'random' ) );
	}

	/**
	 * @group convert_orderby_to_order_by_term
	 */
	public function test_convert_orderby_to_order_by_term_invalid_fallback_to_date_created() {
		$c = new _BP_Groups_Group();
		$this->assertEquals( _BP_Groups_Group::_convert_orderby_to_order_by_term( 'date_created' ), _BP_Groups_Group::_convert_orderby_to_order_by_term( 'I am a bad boy' ) );
	}

	/**
	 * @group groups_get_orderby_meta_id
	 */
	public function test_get_orderby_meta_id() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();
		$g3 = self::factory()->group->create();

		groups_update_groupmeta( $g2, 'orderup', 'sammy' );
		groups_update_groupmeta( $g1, 'orderup', 'sammy' );

		$args = array(
			'meta_query'         => array(
				array(
					'key'   => 'orderup',
					'value' => 'sammy'
				),
			),
			'orderby'           => 'meta_id',
			'order'             => 'ASC',
		);
		$groups = BP_Groups_Group::get( $args );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g2, $g1 ), $found );
	}

	/**
	 * @group groups_get_orderby_meta_id
	 */
	public function test_get_orderby_meta_id_invalid_fallback_to_date_created() {
		$time = time();
		$g1 = self::factory()->group->create( array(
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 10000 ),
		) );
		$g2 = self::factory()->group->create( array(
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 1000 ),
		) );
		$g3 = self::factory()->group->create( array(
			'date_created' => gmdate( 'Y-m-d H:i:s', $time - 100 ),
		) );

		$args = array(
			'orderby' => 'meta_id',
		);
		$groups = BP_Groups_Group::get( $args );

		// Orderby meta_id should be ignored if no meta query is present.
		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g3, $g2, $g1 ), $found );
	}

	public function test_filter_user_groups_normal_search() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => 'This is one cool group',
		) );
		$g2 = self::factory()->group->create();
		$u = self::factory()->user->create();
		self::add_user_to_group( $u, $g1 );

		$groups = BP_Groups_Group::filter_user_groups( 'Cool', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_filter_user_groups_normal_search_middle_of_string() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => 'This group is for mandocellos and oboes.',
		) );
		$g2 = self::factory()->group->create();
		$u = self::factory()->user->create();
		self::add_user_to_group( $u, $g1 );

		$groups = BP_Groups_Group::filter_user_groups( 'cello', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_filter_user_groups_search_with_underscores() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => '_cool_ dude',
		) );
		$g2 = self::factory()->group->create();

		$u = self::factory()->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$groups = BP_Groups_Group::filter_user_groups( '_cool_', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_filter_user_groups_search_with_percent_sign() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => '100% awesome',
		) );
		$g2 = self::factory()->group->create();

		$u = self::factory()->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$groups = BP_Groups_Group::filter_user_groups( '100%', $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_filter_user_groups_search_with_quotes() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => "'tis sweet",
		) );
		$g2 = self::factory()->group->create();

		$u = self::factory()->user->create();
		self::add_user_to_group( $u, $g1 );
		self::add_user_to_group( $u, $g2 );

		$groups = BP_Groups_Group::filter_user_groups( "'tis ", $u );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );

		// @todo
		//$this->assertEquals( array( $g1->id ), $found );

		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	public function test_search_groups_normal_search() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => 'This is one cool group',
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::search_groups( 'Cool' );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_search_groups_search_with_underscores() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => '_cool_ dude',
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::search_groups( '_cool_' );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_search_groups_search_with_percent_sign() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => '100% awesome',
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::search_groups( '100%' );

		$found = wp_list_pluck( $groups['groups'], 'group_id' );
		$this->assertEquals( array( $g1 ), $found );
	}

	public function test_search_groups_search_with_quotes() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Cool Group',
			'description' => "'tis sweet",
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::search_groups( "'tis " );
		$found  = wp_list_pluck( $groups['groups'], 'group_id' );

		$this->assertEquals( array( $g1 ), $found );
		$this->assertNotContains( $g2, $found );
	}

	/**
	 * @expectedDeprecated BP_Groups_Group::get_by_letter
	 */
	public function test_get_by_letter_with_deprecated_arg() {
		$g1 = self::factory()->group->create( array(
			'name'        => 'Awesome Cool Group',
			'description' => 'Neat',
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get_by_letter( 'A', null, null, false );
		$found  = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g1 ), $found );
		$this->assertNotContains( $g2, $found );
	}

	public function test_get_by_letter_typical_use() {
		$g1 = self::factory()->group->create( array(
			'name'        => 'Awesome Cool Group',
			'description' => 'Neat',
		) );
		$g2 = self::factory()->group->create();

		$groups = BP_Groups_Group::get_by_letter( 'A' );
		$found  = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g1 ), $found );
		$this->assertNotContains( $g2, $found );
	}

	public function test_get_by_letter_with_exclude() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Awesome Cool Group',
			'description' => 'Neat',
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'Another Cool Group',
			'description' => 'Awesome',
		) );

		$groups = BP_Groups_Group::get_by_letter( 'A', null, null, true, array( $g1, 'stringthatshouldberemoved' ) );
		$found  = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g2 ), $found );

	}

	public function test_get_by_letter_starts_with_apostrophe() {
		$g1 = self::factory()->group->create( array(
			'name' => "'Tis Sweet",
			'description' => 'Neat',
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'Another Cool Group',
			'description' => 'Awesome',
		) );

		$groups = BP_Groups_Group::get_by_letter( "'" );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		// @todo
		// The test fails but at least it's sanitized
		//$this->assertEquals( array( $g1->id ), $found );

		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @expectedDeprecated BP_Groups_Group::get_random
	 */
	public function test_get_random_with_deprecated_arg() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();

		// There are only two groups, so excluding one should give us the other
		$groups = BP_Groups_Group::get_random( null, null, 0, false, false, array( $g1, 'ignore this' ) );
		$found  = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g2 ), $found );
	}

	public function test_get_random_with_exclude() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create();

		// There are only two groups, so excluding one should give us the other
		$groups = BP_Groups_Group::get_random( null, null, 0, false, true, array( $g1, 'ignore this' ) );

		$found = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g2 ), $found );
	}

	public function test_get_random_with_search_terms() {
		$g1 = self::factory()->group->create( array(
			'name' => 'Bodacious',
		) );
		$g2 = self::factory()->group->create( array(
			'name' => 'Crummy group',
		) );

		// Only one group will match, so the random part doesn't matter
		$groups = BP_Groups_Group::get_random( null, null, 0, 'daci' );
		$found  = wp_list_pluck( $groups['groups'], 'id' );

		$this->assertEquals( array( $g1 ), $found );
		$this->assertNotContains( $g2, $found );
	}

	/**
	 * @group delete
	 * @group cache
	 */
	public function test_delete_clear_cache() {
		$g = self::factory()->group->create();

		// Prime cache
		groups_get_group( $g );

		$this->assertNotEmpty( wp_cache_get( $g, 'bp_groups' ) );

		$group = new BP_Groups_Group( $g );
		$group->delete();

		$this->assertFalse( wp_cache_get( $g, 'bp_groups' ) );
	}

	/**
	 * @group save
	 * @group cache
	 */
	public function test_save_clear_cache() {
		$g = self::factory()->group->create();

		// Prime cache
		groups_get_group( $g );

		$this->assertNotEmpty( wp_cache_get( $g, 'bp_groups' ) );

		$group = new BP_Groups_Group( $g );
		$group->name = 'Foo';
		$group->save();

		$this->assertFalse( wp_cache_get( $g, 'bp_groups' ) );
	}
	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_non_logged_in() {
		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[] = new stdClass;

		$paged_groups[0]->id = 5;
		$paged_groups[1]->id = 10;

		$group_ids = array( 5, 10 );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		wp_set_current_user( 0 );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		wp_set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_non_member() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		wp_set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		wp_set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_member() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();
		$this->add_user_to_group( $u, $g );

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '1';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		wp_set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		wp_set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_invited() {
		$u = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'creator_id' => $u2, 'status' => 'private' ) );

		// Outstanding invitations should be left intact.
		groups_invite_user( array(
			'user_id' => $u,
			'group_id' => $g,
			'inviter_id' => $u2,
			'send_invite' => 1,
		) );

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '1';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		wp_set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		wp_set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_pending() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create( array( 'status' => 'private' ) );

		// Create membership request
		groups_send_membership_request( array(
			'user_id'       => $u,
			'group_id'      => $g,
		) );

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '1';
			$expected[ $key ]->is_banned = false;
		}

		$old_user = get_current_user_id();
		wp_set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		wp_set_current_user( $old_user );
	}

	/**
	 * @group get_group_extras
	 */
	public function test_get_group_extras_banned() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();

		$member                = new BP_Groups_Member;
		$member->group_id      = $g;
		$member->user_id       = $u;
		$member->date_modified = bp_core_current_time();
		$member->is_banned     = true;
		$member->save();

		$paged_groups = array();
		$paged_groups[] = new stdClass;
		$paged_groups[0]->id = $g;

		$group_ids = array( $g );

		$expected = array();
		foreach ( $paged_groups as $key => $value ) {
			$expected[ $key ] = new stdClass;
			$expected[ $key ]->id = $value->id;
			$expected[ $key ]->is_member = '0';
			$expected[ $key ]->is_invited = '0';
			$expected[ $key ]->is_pending = '0';
			$expected[ $key ]->is_banned = true;
		}

		$old_user = get_current_user_id();
		wp_set_current_user( $u );

		$this->assertEquals( $expected, BP_Groups_Group::get_group_extras( $paged_groups, $group_ids ) );

		wp_set_current_user( $old_user );
	}

	/**
	 * @ticket BP5451
	 */
	public function test_admins_property() {
		$user_1 = self::factory()->user->create_and_get();
		$g = self::factory()->group->create( array(
			'creator_id' => $user_1->ID,
		) );

		$group = new BP_Groups_Group( $g );

		$expected_admin_props = array(
			'user_id' => $user_1->ID,
			'user_login' => $user_1->user_login,
			'user_email' => $user_1->user_email,
			'user_nicename' => $user_1->user_nicename,
			'is_admin' => 1,
			'is_mod' => 0,
		);

		$found_admin = $group->admins[0];
		foreach ( $expected_admin_props as $prop => $value ) {
			$this->assertEquals( $value, $found_admin->{$prop} );
		}
	}

	/**
	 * @ticket BP7497
	 */
	public function test_admins_property_should_match_users_without_wp_role() {
		$user_1 = self::factory()->user->create_and_get();
		$g = self::factory()->group->create( array(
			'creator_id' => $user_1->ID,
		) );

		$user_1->remove_all_caps();

		$group = new BP_Groups_Group( $g );

		$this->assertEqualSets( array( $user_1->ID ), wp_list_pluck( $group->admins, 'user_id' ) );
	}

	/**
	 * @ticket BP7677
	 */
	public function test_demoting_sole_admin() {
		$user = self::factory()->user->create_and_get();
		$group = self::factory()->group->create_and_get( array(
			'creator_id' => $user->ID,
		) );
		$member = new BP_Groups_Member( $user->ID, $group->id );
		$member->demote();

		$this->assertEmpty( $group->admins );
		$this->assertEmpty( $group->mods );
	}

	/**
	 * @ticket BP5451
	 */
	public function test_mods_property() {
		$users = self::factory()->user->create_many( 2 );
		$user_1 = new WP_User( $users[0] );
		$user_2 = new WP_User( $users[1] );

		$g = self::factory()->group->create( array(
			'creator_id' => $user_1->ID,
		) );

		$this->add_user_to_group( $user_2->ID, $g, array( 'is_mod' => 1 ) );

		$group = new BP_Groups_Group( $g );

		$expected_mod_props = array(
			'user_id' => $user_2->ID,
			'user_login' => $user_2->user_login,
			'user_email' => $user_2->user_email,
			'user_nicename' => $user_2->user_nicename,
			'is_admin' => 0,
			'is_mod' => 1,
		);

		$found_mod = $group->mods[0];
		foreach ( $expected_mod_props as $prop => $value ) {
			$this->assertEquals( $value, $found_mod->{$prop} );
		}
	}

	/**
	 * @ticket BP5451
	 * @ticket BP7658
	 */
	public function test_is_member_property() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g  = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		wp_set_current_user( $u2 );

		$group_a = new BP_Groups_Group( $g );

		// $u2 IS NOT a member of $g yet.
		$this->assertFalse( $group_a->is_member );

		// Now $u2 IS a member of $g.
		$this->add_user_to_group( $u2, $g );

		$group_b = new BP_Groups_Group( $g );

		// $u2 IS a member of $g. This returns the ID of the membership, not the User id or boolean.
		$this->assertTrue( (bool) $group_b->is_member );
	}

	/**
	 * @ticket BP5451
	 * @ticket BP7658
	 */
	public function test_is_invited_property() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g  = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		wp_set_current_user( $u2 );

		$group_a = new BP_Groups_Group( $g );

		$this->assertFalse( $group_a->is_invited );

		groups_invite_user( array(
			'user_id'    => $u2,
			'group_id'   => $g,
			'inviter_id' => $u1,
			'send_invite' => 1
		) );

		$group_b = new BP_Groups_Group( $g );

		$this->assertTrue( wp_validate_boolean( $group_b->is_invited ) );
	}

	/**
	 * @ticket BP5451
	 */
	public function test_is_pending_property() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$g  = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		wp_set_current_user( $u2 );

		$group_a = new BP_Groups_Group( $g );

		$this->assertFalse( $group_a->is_pending );

		groups_send_membership_request( array(
			'user_id' => $u2,
			'group_id' => $g
		) );

		$group_b = new BP_Groups_Group( $g );
		$this->assertFalse( $group_b->is_pending );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => $g1,
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g2 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_ignore_grandparent() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => $g2,
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEquals( array( $g3 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_array() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => array( $g1, $g2 ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g2, $g3 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_comma_separated_string() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => "$g1, $g2",
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g2, $g3 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_top_level_groups() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => 0,
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g4 ), $found );
	}

	/**
	 * @group hierarchical_groups
	 */
	public function test_get_by_parent_id_top_level_groups_using_false() {
		$g1 = self::factory()->group->create();
		$g2 = self::factory()->group->create( array(
			'parent_id' => $g1,
		) );
		$g3 = self::factory()->group->create( array(
			'parent_id' => $g2,
		) );
		$g4 = self::factory()->group->create();

		$groups = BP_Groups_Group::get( array(
			'parent_id' => false,
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g4 ), $found );
	}

	/**
	 * @group get_by_slug
	 */
	public function test_get_by_slug() {
		$g1 = self::factory()->group->create(array(
			'slug'      => 'apr'
		) );
		$g2 = self::factory()->group->create( array(
			'slug'      => 'jan'
		) );
		$g3 = self::factory()->group->create( array(
			'slug'      => 'mar'
		) );

		$groups = BP_Groups_Group::get( array(
			'slug' => array( 'apr', 'mar' ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g3 ), $found );
	}

	/**
	 * @group get_by_slug
	 */
	public function test_get_by_slug_accept_string() {
		$g1 = self::factory()->group->create(array(
			'slug'      => 'apr'
		) );
		$g2 = self::factory()->group->create( array(
			'slug'      => 'jan'
		) );
		$g3 = self::factory()->group->create( array(
			'slug'      => 'mar'
		) );

		$groups = BP_Groups_Group::get( array(
			'slug' => 'jan',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g2 ), $found );
	}

	/**
	 * @group get_by_slug
	 */
	public function test_get_by_slug_accept_comma_separated_string() {
		$g1 = self::factory()->group->create(array(
			'slug'      => 'apr'
		) );
		$g2 = self::factory()->group->create( array(
			'slug'      => 'jan'
		) );
		$g3 = self::factory()->group->create( array(
			'slug'      => 'mar'
		) );

		$groups = BP_Groups_Group::get( array(
			'slug' => 'apr, mar',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g3 ), $found );
	}

	/**
	 * @group get_by_slug
	 */
	public function test_get_by_slug_accept_space_separated_string() {
		$g1 = self::factory()->group->create(array(
			'slug'      => 'apr'
		) );
		$g2 = self::factory()->group->create( array(
			'slug'      => 'jan'
		) );
		$g3 = self::factory()->group->create( array(
			'slug'      => 'mar'
		) );

		$groups = BP_Groups_Group::get( array(
			'slug' => 'apr mar',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g3 ), $found );
	}

	/**
	 * @group get_by_status
	 */
	public function test_get_by_status() {
		$g1 = self::factory()->group->create(array(
			'status'      => 'private'
		) );
		$g2 = self::factory()->group->create( array(
			'status'      => 'public'
		) );
		$g3 = self::factory()->group->create( array(
			'status'      => 'hidden'
		) );

		$groups = BP_Groups_Group::get( array(
			'status' => array( 'private', 'hidden' ),
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g3 ), $found );
	}

	/**
	 * @group get_by_status
	 */
	public function test_get_by_status_accept_string() {
		$g1 = self::factory()->group->create(array(
			'status'      => 'private'
		) );
		$g2 = self::factory()->group->create( array(
			'status'      => 'public'
		) );
		$g3 = self::factory()->group->create( array(
			'status'      => 'hidden'
		) );

		$groups = BP_Groups_Group::get( array(
			'status' => 'public',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g2 ), $found );
	}

	/**
	 * @group get_by_status
	 */
	public function test_get_by_status_accept_comma_separated_string() {
		$g1 = self::factory()->group->create(array(
			'status'      => 'private'
		) );
		$g2 = self::factory()->group->create( array(
			'status'      => 'public'
		) );
		$g3 = self::factory()->group->create( array(
			'status'      => 'hidden'
		) );

		$groups = BP_Groups_Group::get( array(
			'status' => 'private, hidden',
		) );

		$found = wp_list_pluck( $groups['groups'], 'id' );
		$this->assertEqualSets( array( $g1, $g3 ), $found );
	}

	/**
	 * @group get_ids_only
	 */
	public function test_get_return_ids_only() {
		$now = time();
		$g1 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60 ),
		) );
		$g2 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*2 ),
		) );
		$g3 = self::factory()->group->create( array(
			'last_activity' => date( 'Y-m-d H:i:s', $now - 60*60*3 ),
		)  );

		$groups = BP_Groups_Group::get( array(
			'fields' => 'ids',
		) );

		$this->assertSame( array( $g1, $g2, $g3 ), $groups['groups'] );
	}
}

/**
 * Stub class for accessing protected methods
 */
class _BP_Groups_Group extends BP_Groups_Group {
	public static function _convert_type_to_order_orderby( $type ) {
		return self::convert_type_to_order_orderby( $type );
	}

	public static function _convert_orderby_to_order_by_term( $term ) {
		return self::convert_orderby_to_order_by_term( $term );
	}
}
