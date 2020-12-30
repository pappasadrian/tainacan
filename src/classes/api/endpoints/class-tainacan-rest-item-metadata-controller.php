<?php

namespace Tainacan\API\EndPoints;

use \Tainacan\API\REST_Controller;
use Tainacan\Entities;
use Tainacan\Repositories;

class REST_Item_Metadata_Controller extends REST_Controller {
	private $metadatum;
	private $item_metadata_repository;
	private $item_repository;
	private $collection_repository;
	private $metadatum_repository;

	public function __construct() {
		$this->rest_base = 'metadata';
		parent::__construct();
		add_action('init', array(&$this, 'init_objects'), 11);
	}

	/**
	 * Initialize objects after post_type register
	 */
	public function init_objects() {
		$this->metadatum = new Entities\Metadatum();
		$this->metadatum_repository = Repositories\Metadata::get_instance();
		$this->item_metadata_repository = Repositories\Item_Metadata::get_instance();
		$this->item_repository = Repositories\Items::get_instance();
		$this->collection_repository = Repositories\Collections::get_instance();
	}

	/**
	 * If POST on metadatum/collection/<collection_id>, then
	 * a metadatum will be created in matched collection and all your item will receive this metadatum
	 *
	 * If POST on metadatum/item/<item_id>, then a value will be added in a metadatum and metadatum passed
	 * id body of requisition
	 *
	 * Both of GETs return the metadatum of matched objects
	 */
	public function register_routes() {
		register_rest_route($this->namespace, '/item/(?P<item_id>[\d]+)/' . $this->rest_base . '/(?P<metadatum_id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array($this, 'update_item'),
					'permission_callback' => array($this, 'update_item_permissions_check'),
					'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::EDITABLE)
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array($this, 'delete_item'),
					'permission_callback' => array($this, 'delete_item_permissions_check')
				),
			)
		);
		register_rest_route($this->namespace,  '/item/(?P<item_id>[\d]+)/'. $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_items'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
					'args'                => $this->get_endpoint_args_for_item_schema(\WP_REST_Server::READABLE),
				)
			)
		);
		register_rest_route($this->namespace,  '/item/(?P<item_id>[\d]+)/'. $this->rest_base. '/(?P<metadatum_id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array($this, 'get_item_metadatum_value'),
					'permission_callback' => array($this, 'get_items_permissions_check'),
				)
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return object|void|\WP_Error
	 */
	public function prepare_item_for_database( $request ) {
		$meta = json_decode($request[0]->get_body(), true);

		foreach ($meta as $key => $value){
			$set_ = 'set_' . $key;
			$this->metadatum->$set_($value);
		}

		$collection = new Entities\Collection($request[1]);

		$this->metadatum->set_collection($collection);
	}

	/**
	 * @param mixed $item
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error|\WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$item_arr = $item->_toArray(true, true);

		if($request['context'] === 'edit'){
			$item_arr['current_user_can_edit'] = $item->can_edit();
			$item_arr['current_user_can_delete'] = $item->can_delete();
		}

		return $item_arr;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_items( $request ) {
		$item_id = $request['item_id'];

		$item = $this->item_repository->fetch($item_id);

		if( in_array($item->get_status(), ['auto-draft'] ) ) {
			$this->item_metadata_repository->create_default_value_metadata($item);
		}

		$items_metadata = $item->get_metadata();

		$prepared_item = [];

		foreach ($items_metadata as $item_metadata){
			$index = array_push($prepared_item, $this->prepare_item_for_response($item_metadata, $request));
			$prepared_item[$index-1]['metadatum']['metadata_type_object'] = $item_metadata->get_metadatum()->get_metadata_type_object()->_toArray();
		}

		return new \WP_REST_Response(apply_filters('tainacan-rest-response', $prepared_item, $request), 200);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function get_item_metadatum_value( $request ) {
		$item_id = $request['item_id'];
		$metadatum_id = $request['metadatum_id'];

		$item = $this->item_repository->fetch($item_id);

		$items_metadata = $item->get_metadata();

		$prepared_item = '';

		foreach ($items_metadata as $item_metadata){
			$metadatum = $item_metadata->get_metadatum();
			if($metadatum->get_id() == $metadatum_id) {
				$prepared_item = $this->prepare_item_for_response($item_metadata, $request);
				$prepared_item['metadatum']['metadata_type_object'] = $metadatum->get_metadata_type_object()->_toArray();
			}
		}

		return new \WP_REST_Response(apply_filters('tainacan-rest-response', $prepared_item, $request), 200);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 * @throws \Exception
	 */
	public function get_items_permissions_check( $request ) {
		$item = $this->item_repository->fetch($request['item_id']);

		if(($item instanceof Entities\Item)) {
			return $item->can_read();
		}

		return false;
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function update_item( $request ) {
		$body = json_decode( $request->get_body(), true );

		if ($body) {
			$item_id      = $request['item_id'];
            $value        = $body['values'];
            $metadatum_id = $request['metadatum_id'];
			$parent_meta_id = isset( $body['parent_meta_id'] ) && $body['parent_meta_id'] > 0 ? $body['parent_meta_id'] : null;

			$item  = $this->item_repository->fetch($item_id);
			$metadatum = $this->metadatum_repository->fetch($metadatum_id);

			$item_metadata = new Entities\Item_Metadata_Entity( $item, $metadatum, null, $parent_meta_id);

            $value = $this->get_metadata_value($item_metadata->is_multiple(), $value);
            $item_metadata->set_value($value);

            if ($item_metadata->validate()) {
				if($item->can_edit()) {
					$updated_item_metadata = $this->item_metadata_repository->update( $item_metadata );

					$prepared_item =  $this->prepare_item_for_response($updated_item_metadata, $request);
					$prepared_item['metadatum']['metadata_type_object'] = $updated_item_metadata->get_metadatum()->get_metadata_type_object()->_toArray();
					$prepared_item['parent_meta_id'] = ( $parent_meta_id && $parent_meta_id > 0) ? $parent_meta_id : $updated_item_metadata->get_parent_meta_id();
				}
				elseif($metadatum->get_accept_suggestion()) {
					$log = $this->item_metadata_repository->suggest( $item_metadata );
					$prepared_item = $log->_toArray();
				}
				else {
					return new \WP_REST_Response( [
						'error_message' => __( 'The metadatum does not accept suggestions', 'tainacan' ),
					], 400 );
				}

				return new \WP_REST_Response( $prepared_item, 200 );
			} else {
				return new \WP_REST_Response( [
					'error_message' => __( 'Please verify, invalid value(s)', 'tainacan' ),
					'errors'        => $item_metadata->get_errors(),
					'item_metadata' => $this->prepare_item_for_response($item_metadata, $request),
				], 400 );
			}
		}

		return new \WP_REST_Response([
			'error_message' => 'The body could not be empty',
			'body'          => $body
		], 400);
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 * @throws \Exception
	 */
	public function update_item_permissions_check( $request ) {
		if (isset($request['item_id'])) {
			$item = $this->item_repository->fetch($request['item_id']);
			$metadatum = $this->metadatum_repository->fetch( $request['metadatum_id'] );

			if ( $item instanceof Entities\Item && $metadatum instanceof Entities\Metadatum ) {
				if( $item->can_edit() && $metadatum->can_read() ) {
					return true;
				}
				else {
					// not yet implemented
					// return 'publish' === $metadatum->get_status() && $metadatum->get_accept_suggestion();
				}
			}

		}

		return false;
	}

	/**
	 * @param string $method
	 *
	 * @return array|mixed
	 */
	public function get_endpoint_args_for_item_schema( $method = null ) {
		$endpoint_args = [];

		if($method === \WP_REST_Server::READABLE) {
			$endpoint_args = array_merge(
				$endpoint_args,
				$this->get_wp_query_params()
            );
		} elseif ($method === \WP_REST_Server::EDITABLE) {
			$endpoint_args['values'] = [
				'type'        => ['array', 'string', 'object', 'integer'],
				'items'       => [
					'type' => ['array', 'string', 'object', 'integer']
				],
				'description' => __('The value(s) of item metadata')
			];
			$endpoint_args['parent_meta_id'] = [
				'type'        => ['array', 'string', 'object', 'integer'],
				'items'       => ['type' => ['array', 'string', 'object', 'integer'] ],
				'description' => __('The parent meta ID for the item metadata children group')
			];
		}

		return $endpoint_args;
	}

	/**
	 *
	 * Return the queries supported when getting a collection of objects
	 *
	 * @param null $object_name
	 *
	 * @return array
	 */
	public function get_wp_query_params() {
		$query_params['context'] = array(
			'type'    	  => 'string',
			'default' 	  => 'view',
			'description' => 'The context in which the request is made.',
			'enum'    	  => array(
				'view',
				'edit'
			),
		);

		return $query_params;
	}

	/**
	 * Verify if current user has permission to delete a item metadata value
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool|\WP_Error
	 * @throws \Exception
	 */
	public function delete_item_permissions_check( $request ) {
		if (isset($request['item_id'])) {
			$item = $this->item_repository->fetch($request['item_id']);
			$metadatum = $this->metadatum_repository->fetch( $request['metadatum_id'] );

			if ( $item instanceof Entities\Item && $metadatum instanceof Entities\Metadatum ) {
				if( $item->can_edit() && $metadatum->can_read() ) {
					return true;
				}
				else {
					// not yet implemented
					// return 'publish' === $metadatum->get_status() && $metadatum->get_accept_suggestion();
				}
			}

		}

		return false;
	}

	public function delete_item( $request ) {
		$body = json_decode( $request->get_body(), true );
		if($body) {
			$item_id  = $request['item_id'];
			$metadatum_id = $request['metadatum_id'];
			$parent_meta_id = isset( $body['parent_meta_id'] ) && $body['parent_meta_id'] > 0 ? $body['parent_meta_id'] : null;
		
			$item  = $this->item_repository->fetch( $item_id );
			$metadatum = $this->metadatum_repository->fetch( $metadatum_id );

			$item_metadata = new Entities\Item_Metadata_Entity( $item, $metadatum, null, $parent_meta_id);

			if($item->can_edit()) {
				$deleted_item_metadata = $this->item_metadata_repository->delete_metadata( $item_metadata );

				$prepared_item =  $this->prepare_item_for_response($deleted_item_metadata, $request);
				$prepared_item['metadatum']['metadata_type_object'] = $deleted_item_metadata->get_metadatum()->get_metadata_type_object()->_toArray();
				$prepared_item['parent_meta_id'] = ( $parent_meta_id && $parent_meta_id > 0) ? $parent_meta_id : $deleted_item_metadata->get_parent_meta_id();
				
				return new \WP_REST_Response( $prepared_item, 200 );
			}
			else {
				return new \WP_REST_Response( [
					'error_message' => __( 'you are not allowed to update the item', 'tainacan' ),
					'errors'        => "operation not permitted"
				], 400 );
			}
		}
	}

	private function get_metadata_value($is_multiple, $value) {
	    $filtered_value = $value;
	    if ($is_multiple) {
	        // if metadata is multiple, leave it as array
            // this has to be verified before and separately than the array check
        } elseif (is_array($value)) {
            $filtered_value = implode(' ', $value);
        }

	    if (is_array($filtered_value)) {
            $filtered_arr = array_map(function($v) {
                return $this->filter_value($v);
            }, $filtered_value);

            $filtered_value = $filtered_arr;
        } else {
            $filtered_value = $this->filter_value($filtered_value);
        }

	    return $filtered_value;
    }
}
