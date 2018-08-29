<?php

namespace Tainacan;
use Tainacan\Repositories;
use Tainacan\Entities;

defined( 'ABSPATH' ) || exit;

/**
 * Bulk_Edit class handles bulk item edition
 */
class Bulk_Edit  {
	
	private $meta_key = '_tnc_bulk';
	
	/**
	 * The ID of the current bulk edition group.
	 * @var string
	 */
	private $id;

	/**
	 * Initializes a bulk edit object
	 *
	 * This object have an ID that identifies the group of selected items that are to be affected by the changes.
	 *
	 * When itialized it adds a postmeta to the posts of these groups so they can be easily fetched as a group in all operations.
	 *
	 * The object can be initialized in three different ways:
	 * 1. passing an array of Items IDs, using the items_ids params
	 * 2. passing a query array, that will be passed to the fetch method of items repository to create the group  (in this case you also need to inform the collection id)
	 * 3. passing an group ID, generated by this class in a previous initialization using one of the methods above.
	 *
	 * When initializing using methods 1 or 2, controllers should then call the get_id() method and store it if they want to perform future requests that wil affect this same group of items
	 *
	 * Note: if the ID paramater is passed, other paramaters will be ignored.
	 *
	 * @param  array $params {
	 *
	 *        Initialization paramaters
	 *
	 * @type int $collection_id The items collection ID. Required if initializing using a query search
	 * @type array $query The query paramaters used to fetch items that will be part of this bulk edit group
	 * @type array $items_ids an array containing the IDs of items that will be part of this bulk edit group
	 * @type string $id The ID of the Bulk edit group.
	 *
	 * }
	 * @throws \Exception
	 */
	public function __construct($params) {
		
		if (isset($params['id']) && !empty($params['id'])) {
			$this->id = $params['id'];
			return;
		}
		
		global $wpdb;
		
		$id = uniqid();
		$this->id = $id;

		if (isset($params['query']) && is_array($params['query'])) {
			
			if (!isset($params['collection_id']) || !is_numeric($params['collection_id'])) {
				throw new \Exception('Collection ID must be informed when creating a group via query');
			}
			
			/**
			 * Here we use the fetch method to parse the parameter and use WP_Query
			 *
			 * However, we add a filter so the query is not executed. We just want WP_Query to build it for us
			 * and then we can use it to INSERT the postmeta with the bulk group ID
			 */
			
			// this avoids wp_query to run the query. We just want to build the query
			add_filter('posts_pre_query', '__return_empty_array');
			
			// this adds the meta key and meta value to the SELECT query so it can be used directly in the INSERT below
			add_filter('posts_fields_request', [$this, 'add_fields_to_query'], 10, 2);
			
			$itemsRepo = Repositories\Items::get_instance();
			$params['query']['fields'] = 'ids';
			$items_query = $itemsRepo->fetch($params['query'], $params['collection_id']);
			
			remove_filter('posts_pre_query', '__return_empty_array');
			remove_filter('posts_fields_request', [$this, 'add_fields_to_query']);
			
			$wpdb->query( "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) {$items_query->request}" );
			
			return;
			
		} elseif (isset($params['items_ids']) && is_array($params['items_ids'])) {
			$items_ids = array_filter($params['items_ids'], 'is_integer');
			
			$insert_q = '';
			foreach ($items_ids as $item_id) {
				$insert_q .= $wpdb->prepare( "(%d, %s, %s),", $item_id, $this->meta_key, $this->get_id() );
			}
			$insert_q = rtrim($insert_q, ',');
			
			$wpdb->query( "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES $insert_q" );
			
			return;
			
		}
		
	}
	
	/**
	 * Internally used to filter WP_Query and build the INSERT statement. 
	 * Must be public because it is registered as a filter callback
	 */
	public function add_fields_to_query($fields, $wp_query) {
		global $wpdb;
		if ( $wp_query->get('fields') == 'ids' ) { // just to make sure we are in the right query
			$fields .= $wpdb->prepare( ", %s, %s", $this->meta_key, $this->get_id() );
		}
		return $fields;
	}
	
	/**
	 * Get the current group ID
	 * @return string the group ID
	 */
	public function get_id() {
		return $this->id;
	}
	
	// return the number of items selected in the current bulk group
	public function count_posts() {
		global $wpdb;
		$id = $this->get_id();
		if (!empty($id)) {
			return $wpdb->get_var( $wpdb->prepare("SELECT COUNT(post_id) FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $this->meta_key, $id) );
		}
		return 0;
	}

	private function _build_select($fields) {
		global $wpdb;

		return $wpdb->prepare( "SELECT $fields FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $this->meta_key, $this->get_id() );

	}
	
	/**
	 * Sets the status to all items in the current group
	 * 
	 */
	public function set_status($value) {

		if (!$this->get_id()) {
			return new \WP_Error( 'no_id', __( 'Bulk Edit group not initialized', 'tainacan' ) );
		}
		
		$possible_values = ['draft', 'publish', 'private'];

		// Specific validation
		if (!in_array($value, $possible_values)) {
			return new \WP_Error( 'invalid_action', __( 'Invalid status', 'tainacan' ) );
		}

		global $wpdb;

		$select_q = $this->_build_select( 'post_id' );
		
		$query = $wpdb->prepare("UPDATE $wpdb->posts SET post_status = %s WHERE ID IN ($select_q)", $value);

		return $wpdb->query($query);

	}

	/**
	 * Adds a value to a metadatum to all items in the current group
	 * Must be used with a multiple metadatum
	 * 
	 */
	public function add_value(Entities\Metadatum $metadatum, $value) {

		if (!$this->get_id()) {
			return new \WP_Error( 'no_id', __( 'Bulk Edit group not initialized', 'tainacan' ) );
		}

		// Specific validation
		if (!$metadatum->is_multiple()) {
			return new \WP_Error( 'invalid_action', __( 'Unable to add a value to a metadata if it does not accept multiple values', 'tainacan' ) );
		}
		if ($metadatum->is_collection_key()) {
			return new \WP_Error( 'invalid_action', __( 'Unable to add a value to a metadata set to be a collection key', 'tainacan' ) );
		}

		$dummyItem = new Entities\Item();
		$checkItemMetadata = new Entities\Item_Metadata_Entity($dummyItem, $metadatum);
		$checkItemMetadata->set_value([$value]);

		if ($checkItemMetadata->validate()) {
			return $this->_add_value($metadatum, $value);
		} else {
			return new \WP_Error( 'invalid_value', __( 'Invalid Value', 'tainacan' ) );
		}


	}

	/**
	 * Sets a value to a metadatum to all items in the current group.
	 * 
	 * If metadatum is multiple, it will delete all values item may have for this metadatum and then add
	 * this value
	 */
	public function set_value(Entities\Metadatum $metadatum, $value) {

		if (!$this->get_id()) {
			return new \WP_Error( 'no_id', __( 'Bulk Edit group not initialized', 'tainacan' ) );
		}

		// Specific validation
		
		if ($metadatum->is_collection_key()) {
			return new \WP_Error( 'invalid_action', __( 'Unable to set a value to a metadata set to be a collection key', 'tainacan' ) );
		}

		$dummyItem = new Entities\Item();
		$checkItemMetadata = new Entities\Item_Metadata_Entity($dummyItem, $metadatum);
		$checkItemMetadata->set_value( $metadatum->is_multiple() ? [$value] : $value );

		if ($checkItemMetadata->validate()) {
			$this->_remove_values($metadatum);
			return $this->_add_value($metadatum, $value);
		} else {
			return new \WP_Error( 'invalid_value', __( 'Invalid Value', 'tainacan' ) );
		}


	}

	/**
	 * Removes one value from a metadatum of all items in current group
	 * 
	 * Must be used with multiple metadatum that are not set as required
	 * 
	 */
	public function remove_value(Entities\Metadatum $metadatum, $value) {

		if (!$this->get_id()) {
			return new \WP_Error( 'no_id', __( 'Bulk Edit group not initialized', 'tainacan' ) );
		}

		// Specific validation
		
		if ($metadatum->is_required()) {
			return new \WP_Error( 'invalid_action', __( 'Unable to remove a value from a required metadatum', 'tainacan' ) );
		}
		if (!$metadatum->is_multiple()) {
			return new \WP_Error( 'invalid_action', __( 'Unable to remove a value from a metadata if it does not accept multiple values', 'tainacan' ) );
		}

		return $this->_remove_value($metadatum, $value);


	}

	/**
	 * Relplaces a value from one metadata with another value in all items in current group
	 */
	public function replace_value(Entities\Metadatum $metadatum, $new_value, $old_value) {

		if (!$this->get_id()) {
			return new \WP_Error( 'no_id', __( 'Bulk Edit group not initialized', 'tainacan' ) );
		}

		// Specific validation
		
		if ($metadatum->is_collection_key()) {
			return new \WP_Error( 'invalid_action', __( 'Unable to set a value to a metadata set to be a collection key', 'tainacan' ) );
		}

		$dummyItem = new Entities\Item();
		$checkItemMetadata = new Entities\Item_Metadata_Entity($dummyItem, $metadatum);
		$checkItemMetadata->set_value( $metadatum->is_multiple() ? [$new_value] : $new_value );

		if ($checkItemMetadata->validate()) {
			$this->_remove_value($metadatum, $old_value);
			return $this->_add_value($metadatum, $new_value);
		} else {
			return new \WP_Error( 'invalid_value', __( 'Invalid Value', 'tainacan' ) );
		}


	}

	public function trash_items() {
		if (!$this->get_id()) {
			return new \WP_Error( 'no_id', __( 'Bulk Edit group not initialized', 'tainacan' ) );
		}

		global $wpdb;

		$select_q = $this->_build_select( 'post_id' );
		
		$select_insert = "SELECT ID, '_wp_trash_meta_status', post_status FROM $wpdb->posts WHERE ID IN ($select_q)";
		$select_insert_time = $wpdb->prepare("SELECT ID, '_wp_trash_meta_time', %s FROM $wpdb->posts WHERE ID IN ($select_q)", time());

		$query_original_status = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) $select_insert";
		$query_trash_time = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) $select_insert_time";

		$wpdb->query($query_original_status);
		$wpdb->query($query_trash_time);

		
		$query = "UPDATE $wpdb->posts SET post_status = 'trash' WHERE ID IN ($select_q)";

		// TODO trash comments?

		return $wpdb->query($query);

	}

	public function untrash_items() {
		if (!$this->get_id()) {
			return new \WP_Error( 'no_id', __( 'Bulk Edit group not initialized', 'tainacan' ) );
		}

		global $wpdb;

		$select_q = $this->_build_select( 'post_id' );

		// restore status

		$query_restore = "UPDATE $wpdb->posts SET post_status = (SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_trash_meta_status' AND post_id = ID) WHERE ID IN ($select_q) AND post_status = 'trash'";
		$query_delete_meta1 = "DELETE FROM $wpdb->postmeta WHERE meta_key = '_wp_trash_meta_status' AND post_id IN ( SELECT implicitTemp.post_id FROM ($select_q) implicitTemp )";
		$query_delete_meta2 = "DELETE FROM $wpdb->postmeta WHERE meta_key = '_wp_trash_meta_time' AND post_id IN ( SELECT implicitTemp.post_id FROM ($select_q) implicitTemp )";

		$affected = $wpdb->query( $query_restore );
		$wpdb->query( $query_delete_meta1 );
		$wpdb->query( $query_delete_meta2 );

		// TODO untrash comments?

		return $affected;

	}

	public function delete_items() {
		if (!$this->get_id()) {
			return new \WP_Error( 'no_id', __( 'Bulk Edit group not initialized', 'tainacan' ) );
		}

		global $wpdb;

		$select_q = $this->_build_select( 'post_id' );

		$query_delete = "DELETE FROM $wpdb->posts WHERE ID IN ($select_q)";

		return $wpdb->query($query_delete);

	}


	/**
	 * Adds a value to the current group of items
	 * 
	 * This method adds value to the database directly, any check or validation must be done beforehand
	 */
	private function _add_value(Entities\Metadatum $metadatum, $value) {
		global $wpdb;
		$type = $metadatum->get_metadata_type_object();

		if ($type->get_primitive_type() == 'term') {

			$options = $metadatum->get_metadata_type_options();
			$taxonomy_id = $options['taxonomy_id'];
			$tax = Repositories\Taxonomies::get_instance()->fetch($taxonomy_id);

			if ($tax instanceof Entities\Taxonomy) {

				$term = term_exists($value, $tax->get_db_identifier());

				if (!is_array($term)) {
					$term = wp_insert_term($value, $tax->get_db_identifier());
				}

				if (is_WP_Error($term) || !isset($term['term_taxonomy_id'])) {
					return new \WP_Error( 'error', __( 'Error adding term', 'tainacan' ) );
				}

				$insert_q = $this->_build_select( $wpdb->prepare("post_id, %d", $term['term_taxonomy_id']) );

				$query = "INSERT IGNORE INTO $wpdb->term_relationships (object_id, term_taxonomy_id) $insert_q";

				return $wpdb->query($query);

				//TODO update term count


			}

		} else {

			global $wpdb;

			$insert_q = $this->_build_select( $wpdb->prepare("post_id, %s, %s", $metadatum->get_id(), $value) );

			$query = "INSERT IGNORE INTO $wpdb->postmeta (post_id, meta_key, meta_value) $insert_q";

			$affected = $wpdb->query($query);

			if ($type->get_core()) {
				$field = $type->get_related_mapped_prop();
				$map_field = [
					'title' => 'post_title',
					'description' => 'post_content'
				];
				$column = $map_field[$field];
				$update_q = $this->_build_select( "post_id" );
				$core_query = $wpdb->prepare( "UPDATE $wpdb->posts SET $column = %s WHERE ID IN ($update_q)", $value );

				$wpdb->query($core_query);
			}

			return $affected;

		}

	}

	/**
	 * Removes a value from the current group of items
	 * 
	 * This method removes value from the database directly, any check or validation must be done beforehand
	 */
	private function _remove_value(Entities\Metadatum $metadatum, $value) {
		global $wpdb;
		$type = $metadatum->get_metadata_type_object();

		if ($type->get_primitive_type() == 'term') {

			$options = $metadatum->get_metadata_type_options();
			$taxonomy_id = $options['taxonomy_id'];
			$tax = Repositories\Taxonomies::get_instance()->fetch($taxonomy_id);

			if ($tax instanceof Entities\Taxonomy) {

				$term = term_exists($value, $tax->get_db_identifier());

				if (!$term) {
					return 0;
				}

				if (is_WP_Error($term) || !isset($term['term_taxonomy_id'])) {
					return new \WP_Error( 'error', __( 'Term not found', 'tainacan' ) );
				}

				$delete_q = $this->_build_select( "post_id" );

				$query = $wpdb->prepare( "DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d AND object_id IN ($delete_q)", $term['term_taxonomy_id'] );

				return $wpdb->query($query);

				//TODO update term count

			}

		} else {

			global $wpdb;

			$delete_q = $this->_build_select( "post_id" );

			$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s AND post_id IN ( SELECT implicitTemp.post_id FROM ($delete_q) implicitTemp )", $metadatum->get_id(), $value );

			return $wpdb->query($query);

		}

	}

	/**
	 * Removes all values of a metadatum from the current group of items
	 * 
	 * This method removes value from the database directly, any check or validation must be done beforehand
	 */
	private function _remove_values(Entities\Metadatum $metadatum) {
		global $wpdb;
		$type = $metadatum->get_metadata_type_object();

		if ($type->get_primitive_type() == 'term') {

			$options = $metadatum->get_metadata_type_options();
			$taxonomy_id = $options['taxonomy_id'];
			$tax = Repositories\Taxonomies::get_instance()->fetch($taxonomy_id);

			if ($tax instanceof Entities\Taxonomy) {

				$delete_q = $this->_build_select( "post_id" );
				$delete_tax_q = $wpdb->prepare( "SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s" , $tax->get_db_identifier() );

				$query = "DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id IN ($delete_tax_q) AND object_id IN ($delete_q)";

				return $wpdb->query($query);

				//TODO update term count

			}

		} else {

			global $wpdb;

			$delete_q = $this->_build_select( "post_id" );

			$query = $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND post_id IN ( SELECT implicitTemp.post_id FROM ($delete_q) implicitTemp )", $metadatum->get_id() );


			return $wpdb->query($query);

		}

	}
	
	
	
}