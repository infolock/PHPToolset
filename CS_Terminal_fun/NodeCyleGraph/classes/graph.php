<?php
/**
 * This graph class creates and tracks all nodes and their processes,
 * looking for a cycle.  When one is found, all nodes in that list are 
 * set as a cycle, the number_of_cyles counter incremented, and then we move
 * on to the next set.
 * 
 * The names of the methods are done with the mind that we're looking for cycles,
 * which are the "suspects" we're trying to track down.
 * 
 * For more information on the terminology relating to what a "node" is as is 
 * being used and destribed within this object, please refer to the 
 * "find_cycles.php" file located one folder up the tree from this file's location.
 * 
 * @author Jonathon Hibbard 
 * @copyright 2012, Jonathon Hibbard
 */

require_once("node.php");
class graph {
  /**
   * This is an internal array collection keeping track of all
   * nodes that have been added along with if the node
   * has already been checked for a cycle.
   * 
   * @access private
   * @var array
   * @see checkNode()
   */
  private $node_collection    = array();

  /**
   * This is our "stack" that will be used for pushing/popping.
   * This array will only house "nodes" that have NOT yet been
   * checked for a cycle.
   * 
   * @access private
   * @see processNodeStack()
   * @var array
   */
  private $check_node_stack   = array();

  /**
   * This var is incremented by the checkNode() method
   * everytime a new cycle is found.  It is also used
   * for reporting by the "find_cycles" caller script.
   * 
   * @access private
   * @see checkNode()
   * @var integer 
   */
  private $number_of_cycles   = 0;

  /**
   * This variable represents the current "node" (and its
   * paths) as a potential cycle source.
   * 
   * Once the node (and its paths) have either been
   * identified as a cycle or a not, this var is then
   * reset to null and is then open for a new suspect
   * node for investigation.
   * 
   * @access private
   * @see isSuspect()
   * @var type 
   */
  private $cycle_suspect     = null;

  /**
   * This method creates all new news that have been passed in
   * to the constructor.
   * 
   * @param type $node_name
   * @param type $node_path 
   * 
   * @see node()
   * @see __construct()
   */
  private function createNode($node_name, $node_path) {
    $nodeObj = new node($node_name, $node_path);
    $this->addNodeToCollections($nodeObj);
  }

  /**
   * Handles adding nodes to the node_collection array and also
   * the check_node_stack.  When a node that has a deadend is found,
   * that node (and any children found after) is:
   *   o Excluded from the check_node_stack
   *   o Updated with a status of "checked" in the node_collection array.
   * 
   * @see $node_collection
   * @see $check_node_stack
   * @param node $nodeObj 
   */
  private function addNodeToCollections(node $nodeObj) {
    if(!isset($this->node_collection[$nodeObj->node_name])) {
      $this->node_collection[$nodeObj->node_name] = array("obj"     => $nodeObj,
                                                          "checked" => false,
                                                         );
      if(!isset($nodeObj->is_cycle)) {
        $this->check_node_stack[$nodeObj->node_name] = $nodeObj;
      } else {
        $this->node_collection[$nodeObj->node_name]['checked'] = true;
      }
    }
  }

  /**
   * This method is called any time a node has been investigated
   * as a cycle, and updates the 'checked' key found
   * within the node_collection for the name of the node
   * being passed in (which is the primary key for the array).
   * 
   * @param string $node_name  Key in the $node_collection
   * @see checkNode()
   * @see $node_collection
   */
  private function markAsChecked($node_name) {
    $this->node_collection[$node_name]['checked'] = true;
    $this->removeNodeFromStack($node_name);
  }

  /**
   * Removes a node_name (if it still exists) from the
   * check_node_stack.  This is done when a "path" node
   * has been checked as the original "parent" node
   * is "popped" off the stack by default.
   * 
   * @param string $node_name key in the $check_node_stack
   * @see markAsChecked()
   * @see $check_node_stack
   */
  private function removeNodeFromStack($node_name) {
    if(isset($this->check_node_stack[$node_name])) {
      unset($this->check_node_stack[$node_name]);
    }
  }

  /**
   * When called, this method begins the entire "investigation"
   * for checking for a cycle.  This process started ONLY
   * if the check_node_stack array is not empty.
   * 
   * Process/Steps:
   *   o Checks to ensure the $check_node_stack array is not empty.
   *   o Calls getNextNodeFromStack() to obtain a new node (and 
   *     its paths) for investigation which are immediately
   *     passed to the checkNode() method to begin the low
   *     level checks.
   *   o After checkNode() completes, the method recursively
   *     calls itself again to start the whole process over again
   *     until no node keys are left to investigate. 
   * 
   * @see $check_node_stack
   * @see getNextNodeFromStack()
   * @see checkNode()
   */
  public function processNodeStack() {
    if(!empty($this->check_node_stack)) {
      $this->checkNode($this->getNextNodeFromStack());
      $this->processNodeStack();
    }
  }

  /**
   * Called when a new node (and its paths, if any) can be
   * invesgitated as being a cycle.  This is essentially what
   * starts the process of "popping" off the check_node_stack.
   * 
   * By doing this, we eliminate the need to constantly check
   * for paths we've already been down, and also investigating
   * nodes redundantly.
   * 
   * @return node  Returns the next available key within the 
   *               check_node_stack, which is a node object.
   * 
   * @see  processNodeStack()
   */
  private function getNextNodeFromStack() {
    return array_pop($this->check_node_stack);
  }

  /**
   * Sets a $node_name being passed in as a suspect via the $cycle_suspect 
   * variable, but only if one has not already been assigned.
   * 
   * @param string $node_name 
   * @see $cycle_suspect
   * @see checkNode()
   */
  private function setSuspect($node_name) {
    if(!isset($this->cycle_suspect)) {
      $this->cycle_suspect = $node_name;
    }
  }

  /**
   * Checks if the the $node_name being passed in is the currently
   * assigned cycle_suspect node name.
   * 
   * @param string $node_name
   * @return boolen Returns TRUE if is the cycle_suspect.  FALSE otherwise.
   */
  private function isSuspect($node_name) {
    return (isset($this->cycle_suspect) && $node_name == $this->cycle_suspect ?: false);
  }


  /**
   * Constructor for the object.  Loops through all the nodes being 
   * passed in (currently by the find_cyles calling script) and passes
   * each off to createNode for processing.
   * 
   * @param array $nodes 
   * @see createNode()
   */
  public function __construct(array $nodes) {
    foreach($nodes as $node_name => $node_path) {
      $this->createNode($node_name, $node_path);
    }
  }

  /**
   * This method is where all path/node searches are performed.
   * It also :
   *   o Increments the $number_of_cyles variable when a cycle is found
   *   o Is what aids a node in knowing wether or not it is, indeed, a cycle.
   *   o Is where the suspect node is defined and reset (once the all paths
   *     have been investigated)
   *   o Calls itself recursively whenever a $node has been found to contain
   *     a path (that isn't the suspect) and then starts the cycle over.
   *   o Once all paths have been investigated, each $is_cycle is then reported
   *     back through the tree to the other nodes as to what the verdict is for 
   *     setting their is_cycle property.
   * 
   * @todo
   * This method should be the starting point for breaking up the logic into
   * other objects for handline the jobs of tracking, assigning, and reporting.
   * 
   * @param  node $nodeObj
   * @return boolean    Returns true when the passed nodeObject has beeen labeled
   *                    a cycle, false when not.
   * 
   * @see setsuspect 
   * @see isSuspect()
   * @see markAsChecked()
   * @see node()
   * @see $node_collection
   */
  public function checkNode(node $nodeObj) {
    $is_cycle = false;
    if(false === $this->isSuspect($nodeObj->node_name)) {
      $this->setSuspect($nodeObj->node_name);

      if($this->node_collection[$nodeObj->node_name]['checked'] === false) {
        $this->markAsChecked($nodeObj->node_name);

        if(isset($nodeObj->node_path) && isset($this->node_collection[$nodeObj->node_path])) {
          $node_path = $this->node_collection[$nodeObj->node_path];
          if(isset($node_path['obj']) || $this->isSuspect($nodeObj->node_path) === true) {
            if(isset($node_path['obj']->is_cycle)) {
              $is_cycle = $node_path['obj']->is_cycle;
            } else {
              $is_cycle = true;
              $this->number_of_cycles++;
            }
            $this->cycle_suspect = null;
          } else {
            if($node_path['checked'] === false) {
              $is_cycle = $this->checkNode($node_path['obj']);
            } else {
              $is_cycle = $node_path['obj']->is_cycle;
            }
          }
        }
      }
    } else {
      $is_cycle = true;
      $this->cycle_suspect = null;
    }

    $nodeObj->setCycle($is_cycle);

    return $nodeObj->is_cycle;
  }

  /**
   * Returns the number of cyles that have been found.
   * Will return 0 if the processNodeStack() method has not yet been
   * called, or if after calling it no cycles were found.
   * 
   * @return integer  Represents the number of cycles that have been found.
   * @see processNodeStack()
   * @see checkNode()
   */
  public function getNumCycles() {
    return $this->number_of_cycles;
  }
}
?>