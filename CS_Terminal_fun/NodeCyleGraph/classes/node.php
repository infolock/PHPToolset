<?php
/**
 * This is a simple object for creating nodes.
 * Each node created is required to h ave a node_name
 * definition, and a node_path definition.  When the passed in
 * node_path is -1 (indicating a "dead end", the $is_cycle property
 * is immediately set to false, and the node_path left as null.
 * 
 * Otherwise, the $is_cycle property is left as "null", indicating 
 * that it should be further investigated as a possible cycle.
 * 
 * For more information on definitions and naming conventions, please
 * refer to the find_cycles.php script's information located one level up from
 * this file's current directory.
 * 
 * @see ../find_cycles.php
 * @see graph() 
 */
class node {
  /**
   * Holds the definition for wether or not this node is part of a cycle.
   * It is set to NULL by default, but houses a boolean true|false once 
   * the investigation is completed by the graph object.
   * 
   * @var boolean | null (default)
   * @see graph()
   */
  private $is_cycle  = null;
  /**
   * If this node has a "path" to another node, this variable is updated with the
   * $node_name of that path.  If the node is instead a "dead end" node, the 
   * value is left as null.
   * 
   * @var string | null (default)
   */
  private $node_path = null;

  /**
   * This is used for "identifying" the instance created for a node.
   * @var type 
   */
  private $node_name;

  /**
   * The object's constructor.  Stores the node_name and node_path (if any) properties.
   * When the node_path passed in is set as -1, the $is_cycle property is immedately 
   * set as false for any investigations into wether it or nodes that have paths to it are
   * a cycle.
   * 
   * @param string $node_name
   * @param string $node_path 
   */
  public function __construct($node_name, $node_path) {
    $this->node_name = $node_name;
    if(intval($node_path) > -1) {
      $this->node_path = $node_path;
    } else {
      $this->is_cycle = false;
    }
  }

  /**
   * Magic Method for checking if our object's private properties have yet
   * been defined.
   * 
   * @param string $name  Name of the property we want to check for.
   * @return boolean      Returns TRUE if the property has been set.  False otherwise.
   */
  public function __isset($name) {
    return isset($this->$name);
  }

  /**
   * GETTER (magic method) for obtaining the value of a requested private property 
   * if it exists.
   * 
   * @param string $name  The name of the property to obtain.
   * @return mixed        Returns the value of the property being requested if it exists.
   */
  public function __get($name) {
    if(isset($this->$name)) {
      return $this->$name;
    }
    return null;
  }

  /**
   * Sets the $is_cycle property of this node's instance, defining wether it is a node or not.
   * 
   * @param boolean $is_cycle 
   */
  public function setCycle($is_cycle) {
    $this->is_cycle = (bool)$is_cycle;
  }
}
?>