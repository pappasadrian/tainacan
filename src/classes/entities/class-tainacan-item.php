<?php

namespace Tainacan\Entities;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Represents the Entity Item
 */
class Item extends Entity {
	use \Tainacan\Traits\Entity_Collection_Relation;
	/**
	 * {@inheritDoc}
	 * @see \Tainacan\Entities\Entity::repository
	 * @var string
	 */
	protected $repository = 'Tainacan_Items';
	
	/**
	 * {@inheritDoc}
	 */
	function __construct($which = 0) {
		parent::__construct($which);
		if($which !== 0 ) $this->set_cap();
	}
	
	public function  __toString(){
		return 'Hello, my name is '. $this->get_title();
	}

	/**
	 * @return mixed|null
	 */
	function get_featured_img(){
		return $this->get_mapped_property('featured_image');
	}

	/**
	 * @param $value
	 */
	function set_featured_img($value){
		$this->set_mapped_property('featured_image', $value);
	}

	/**
	 * @return mixed|null
	 */
	function get_modification_date(){
		return $this->get_mapped_property('modification_date');
	}

	/**
	 * @return mixed|null
	 */
	function get_creation_date(){
		return $this->get_mapped_property('creation_date');
	}

	/**
	 * @return mixed|null
	 */
	function get_author_id(){
		return $this->get_mapped_property('author_id');
	}

	/**
	 * @return mixed|null
	 */
	function get_url(){
		return $this->get_mapped_property('url');
	}

    /**
     * Return the item ID
     *
     * @return integer
     */
    function get_id() {
        return $this->get_mapped_property('id');
    }
    
    /**
     * Return the item title
     *
     * @return string
     */
    function get_title() {
		return $this->get_mapped_property('title');
    }

    /**
     * Return the item order type
     *
     * @return string
     */
    function get_order() {
        return $this->get_mapped_property('order');
    }

    /**
     * Return the parent ID
     *
     * @return integer
     */
    function get_parent() {
        return $this->get_mapped_property('parent');
    }

    /**
     * Return the item description
     *
     * @return string
     */
    function get_description() {
        return $this->get_mapped_property('description');
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \Tainacan\Entities\Entity::get_db_identifier()
     */
    public function get_db_identifier() {
    	return $this->get_mapped_property('collection_id');
    }
    
    /**
     * Define the title
     *
     * @param [string] $value
     * @return void
     */
    function set_title($value) {
        $this->set_mapped_property('title', $value);
    }

    /**
     * Define the order type
     *
     * @param [string] $value
     * @return void
     */
    function set_order($value) {
        $this->set_mapped_property('order', $value);
    }

    /**
     * Define the parent ID
     *
     * @param [integer] $value
     * @return void
     */
    function set_parent($value) {
        $this->set_mapped_property('parent', $value);
    }

    /**
     * Define the description
     *
     * @param [string] $value
     * @return void
     */
    function set_description($value) {
        $this->set_mapped_property('description', $value);
    }

    /**
     * Return a Metadata or a List of Metadata
     *
     * @return array || Metadata
     */
    function get_metadata() {
        global $Tainacan_Metadatas;

        if (isset($this->metadata))
            return $this->metadata;
        
        $collection = $this->get_collection();
        $all_metadata = [];
        if ($collection) {
            $meta_list = $Tainacan_Metadatas->fetch_by_collection( $collection, [], 'OBJECT' );
            
            foreach ($meta_list as $meta) {
                $all_metadata[$meta->get_id()] = new Item_Metadata_Entity($this, $meta);
            }
        }
        return $all_metadata;
    }
    
    /**
     * Define the Metadata
     *
     * @param Metadata $new_metadata
     * @param [string || integer || array] $value
     * @return void
     */
    function add_metadata(Metadata $new_metadata, $value) {
        
        //TODO Multiple metadata must receive an array as value
        $item_metadata = new Item_Metadata_Entity($this, $new_metadata);
       
        $item_metadata->set_value($value);
        
        $current_meta = $this->get_metadata();
        $current_meta[$new_metadata->get_id()] = $item_metadata;
        
        $this->set_metadata($current_meta);
    }
    
    /**
     * Aux function for @method add_metadata
     *
     * @param array $metadata
     * @return void
     */
    function set_metadata(Array $metadata) {
        $this->metadata = $metadata;
    }
    
    /**
     * set meta cap object
     */
    protected function set_cap() {
    	$db_identifier = $this->get_db_identifier();
    	if(!empty($db_identifier))
    	{
	    	$post_type_obj = get_post_type_object($db_identifier);
	    	if(!is_object($post_type_obj)) { //may be called before post_type registration
	    		$collection = $this->get_collection();
	    		if(is_object($collection)) {
	    			$post_type_obj = $collection->register_collection_item_post_type();
	    		}
	    	}
	    	if(!is_object($post_type_obj)) {
	    		throw new \Exception(sprintf("Collection post type (%s) is not setted and cannot be registred", $db_identifier));
	    	}
	    	$this->cap = $post_type_obj->cap;
    	} //TODO Item without collection? treat as error?
    }
}