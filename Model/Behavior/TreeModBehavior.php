<?php
App::uses('TreeBehavior', 'Model/Behavior');

/**
 * Class TreeModBehavior
 *
 * An extended version of the CakePHP default TreeBehavior
 *
 * Added features:
 * generateTree() - Generates nested model records
 * alterPosition() - Move record after|before|inside other record
 */
class TreeModBehavior extends TreeBehavior {

/**
 * Generate a nested tree of model records
 *
 * @param Model $model
 * @param array $options
 * @return array
 * @todo scope-support
 */
	public function generateTree(Model $model, $options = array()) {
		$options = am(array(
			'rootId' => null, // Root model Id
			//'scope' => null,
			'depth' => 3, // Unlimited depth with -1
			'childrenOnly' => false, // Has no effect if no rootId is set
		), $options);

		if (!$options['rootId']) {
			$conditions = array(
				$model->alias . '.parent_id' => null,
			);
			//if ($options['scope'] !== null) {
			//	$conditions[$model->alias . '.scope'] = $options['scope'];
			//}
			$root = $model->find('all', array(
				'conditions' => $conditions,
				'recursive' => -1
			));
		} else {
			$root = $model->find('all', array(
				'conditions' => array(
					$model->alias . '.id' => $options['rootId'],
					//$model->alias.'.scope' => $scope
				),
				'recursive' => -1
			));
		}

		if (!$root) {
			return;
		}

		$tree = array();
		foreach ($root as $r) {
			$treeData = $r[$model->alias];

			if ($options['depth'] === -1 || ($options['depth'] && $options['depth'] > 0)) {
				$children = $this->children($model, array(
					'id' => $treeData['id'],
					//'fields' => array('id','type','title','alias'),
					'direct' => true,
				));
				foreach ((array)$children as $child) {
					$childTree = $this->generateTree($model, array(
						'rootId' => $child[$model->alias]['id'],
						'depth' => ($options['depth'] !== -1) ? $options['depth'] - 1 : -1,
						//'scope' => $options['scope']
					));
					$treeData['children'][] = $childTree[0];
				}
			}
			$tree[] = $treeData;
		}

		if ($options['rootId'] !== null && $options['childrenOnly']) {
			return (isset($tree[0]) && isset($tree[0]['children'])) ? $tree[0]['children'] : array();
		}

		return $tree;
	}

/**
 * Alter the position of a tree node
 *
 * @param Model $model
 * @param int $nodeId Model Id of node
 * @param int $targetId Target node Id
 * @param string $position Position relative to target (after|before|inside)
 * @param int $prevId Previous node Id
 * @return bool
 * @throws InvalidArgumentException
 */
	public function alterPosition(Model $model, $nodeId, $targetId = null, $position = 'after', $prevId = null) {
		// support options parameter array
		// additional aliases: node => nodeId, target => targetId, prev => prevId
		// little bit dirty, but useful for jqTree-compatibility
		if (is_array($nodeId)) {
			$options = $nodeId;
			$nodeId = $targetId = $prevId = $node = $target = $prev = null;
			extract($options, EXTR_IF_EXISTS);
			$nodeId = ($nodeId) ? $nodeId : $node;
			$targetId = ($targetId) ? $targetId : $target;
			$prevId = ($prevId) ? $prevId : $prevId;
			unset($options, $node, $target, $prev);
		}

		if (!$nodeId || !$targetId || !$position) {
			throw new InvalidArgumentException('NodeId, TargetId or Position missing');
		}

		// get model data of selected node
		$model->recursive = -1;
		$node = $model->findById($nodeId);

		// get model data of target node
		$model->recursive = -1;
		$target = $model->findById($targetId);

		// get model data of previous node
		//$prev = $model->findById($prevId);
		$prev = array();

		switch($position) {
			case "after":
				return $this->_alterPositionAfter($model, $node, $target, $prev);
			case "before":
				return $this->_alterPositionBefore($model, $node, $target, $prev);
			case "inside":
				return $this->_alterPositionInside($model, $node, $target, $prev);
			default:
				throw new InvalidArgumentException('Unknown altering position: ' . $position);
		}

		//return false;
	}

/**
 * Move $node inside $target as first child
 *
 * @param Model $model
 * @param array $node Node to move
 * @param array $target Target node
 * @param array $prev Previous node
 * @return bool
 */
	protected function _alterPositionInside(Model $model, $node, $target, $prev) {
		// nest inside target
		if ($node[$model->alias]['parent_id'] !== $target[$model->alias]['id']) {
			// change parent
			$node = $this->_alterNodeParent($model, $node, $target[$model->alias]['id']);
		}

		if (!$node) {
			return $node;
		}

		// move to top
		$firstChildLft = $target[$model->alias]['lft'] + 1;
		$nodeLft = $node[$model->alias]['lft'];

		if ($nodeLft > $firstChildLft) {
			$offset = ($nodeLft - $firstChildLft) / 2;
			return $this->moveUp($model, $node[$model->alias]['id'], $offset);
		}

		return true;
	}

/**
 * Move $node after $target
 *
 * @param Model $model
 * @param array $node Node to move
 * @param array $target Target node
 * @param array $prev Previous node
 * @return bool
 */
	protected function _alterPositionAfter(Model $model, $node, $target, $prev) {
		if ($node[$model->alias]['parent_id'] !== $target[$model->alias]['parent_id']) {
			// change parent
			$node = $this->_alterNodeParent($model, $node, $target[$model->alias]['parent_id']);
		}

		if (!$node) {
			return $node;
		}

		// move after target
		$targetLft = $target[$model->alias]['lft'];
		$nodeLft = $node[$model->alias]['lft'];
		$offset = ($nodeLft - $targetLft) / 2;
		$nodeChildren = ($node[$model->alias]['rght'] - $node[$model->alias]['lft'] - 1) / 2;
		$delta = abs($offset) - $nodeChildren;

		if ($offset > 0) {
			return $this->moveUp($model, $node[$model->alias]['id'], $delta);
		} elseif ($offset < 0) {
			return $this->moveDown($model, $node[$model->alias]['id'], $delta);
		}

		return true;
	}

/**
 * Move $node before $target
 *
 * @param Model $model
 * @param array $node Node to move
 * @param array $target Target node
 * @param array $prev Previous node
 * @return bool
 */
	protected function _alterPositionBefore(Model $model, $node, $target, $prev) {
		if ($node[$model->alias]['parent_id'] !== $target[$model->alias]['parent_id']) {
			// change parent
			$node = $this->_alterNodeParent($model, $node, $target[$model->alias]['parent_id']);
		}

		if (!$node) {
			return $node;
		}

		// move before target
		$targetLft = $target[$model->alias]['lft'];
		$nodeLft = $node[$model->alias]['lft'];
		$offset = ($nodeLft - $targetLft) / 2;

		$targetChildren = ($target[$model->alias]['rght'] - $target[$model->alias]['lft'] - 1) / 2;
		$delta = abs($offset) - $targetChildren;

		if ($offset > 0) {
			return $this->moveUp($model, $node[$model->alias]['id'], $delta);
		} elseif ($offset < 0) {
			return $this->moveDown($model, $node[$model->alias]['id'], $delta);
		}

		return true;
	}

/**
 * Alter the parent Id of a tree node
 *
 * @param Model $model
 * @param array $node Model data
 * @param int $parentId New parent Id
 * @return array|bool
 */
	protected function _alterNodeParent(Model $model, $node, $parentId) {
		$node[$model->alias]['parent_id'] = $parentId;

		// update node and tree positions
		if (!$model->save($node)) {
			return false;
		}

		$model->recursive = -1;
		return $model->read(null, $model->id);
	}

/**
 * Generate a nested jqTree data set
 *
 * @param Model $model
 * @param array $options $this::generateTree()-compatible options
 * @return array
 */
	public function generateJqTree(Model $model, $options = array()) {
		$tree = $this->generateTree($model, $options);
		return $this->_treeToJqTree($tree);
	}

/**
 * Helper method to generate nested jqTree data
 *
 * @param array $tree A tree generated by generateTree()
 * @return array A jqTree-compatible data set
 */
	protected function _treeToJqTree($tree) {
		$jqTree = array();
		foreach ((array)$tree as $node) {
			$_jqNode = array(
				'label' => $node['name'],
				'id' => $node['id'],
				'load_on_demand' => false,
				'children' => array()
			);
			if (isset($node['children']) && !empty($node['children'])) {
				$_jqNode['children'] = $this->_treeToJqTree($node['children']);
			}
			array_push($jqTree, $_jqNode);
		}
		return $jqTree;
	}
}