<?php
App::uses('TreeModAppController', 'TreeMod.Controller');

/**
 * Class JqTreeController
 */
class JqTreeController extends TreeModAppController {

/**
 * @var array
 */
	public $uses = array();

/**
 * @var Model
 */
	public $Model;

/**
 * @param string $modelName
 * @throws NotFoundException
 */
	public function admin_alter($modelName = null) {
		//Configure::write('debug', 0);
		$this->viewClass = 'Json';

		if (!$modelName) {
			throw new NotFoundException(__('Model name missing'));
		}

		// build query
		$defaultQuery = array(
			'node' => null,
			'target' => null,
			'position' => null,
			'prev' => null
		);
		if ($this->request->is('post')) {
			$query = am($defaultQuery, $this->request->data);
		} else {
			$query = am($defaultQuery, $this->request->query);
		}

		// init plugin
		list($plugin, $modelName) = pluginSplit($modelName, true);
		$modelName = Inflector::camelize($modelName);
		$plugin = Inflector::camelize($plugin);
		$this->Model = ClassRegistry::init($plugin . $modelName);
		$this->Model->Behaviors->load('TreeMod.TreeMod');

		// alter position
		$success = false;
		if ($this->Model->alterPosition($query)) {
			$success = true;
		}

		// serialize
		$this->set(compact('success', 'query'));
		$this->set('_serialize', array('success', 'query'));
	}
}