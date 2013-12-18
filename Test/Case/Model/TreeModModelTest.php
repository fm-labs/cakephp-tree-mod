<?php
App::uses('AppModel', 'Model');

/**
 * TreeModModel Test Case
 *
 */
class TreeModModelTest extends CakeTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.tree_mod.tree_mod_model'
	);

/**
 * @var TreeModModel
 */
	public $Model;

/**
 * @var array
 */
	public $dummyData;

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Model = ClassRegistry::init('TreeModModel');
		$this->Model->Behaviors->load('TreeMod.TreeMod');

		// Food
		// Fruits
		// Cars
		// Audi
		$this->dummyData = array(
			array('id' => 1, 'name' => 'Food'),
			array('id' => 2, 'name' => 'Fruits'),
			array('id' => 3, 'name' => 'Cars'),
			array('id' => 4, 'name' => 'Audi'),
			array('id' => 5, 'name' => 'Apples'),
		);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Model);

		parent::tearDown();
	}

	protected function _truncateData() {
		$this->Model->deleteAll('1 = 1');
	}

	protected function _insertData($data) {
		foreach ($data as $_data) {
			$this->Model->create();
			$this->Model->save(array('TreeModModel' => $_data));
		}
	}

	public function testGenerateTree() {
		/*
		 * Food
		 * _Fruits
		 * __Apples
		 * Cars
		 * _Audi
		 */
		$data = array(
			array('id' => 1, 'parent_id' => null, 'name' => 'Food'),
			array('id' => 2, 'parent_id' => 1, 'name' => 'Fruits'),
			array('id' => 3, 'parent_id' => null, 'name' => 'Cars'),
			array('id' => 4, 'parent_id' => 3, 'name' => 'Audi'),
			array('id' => 5, 'parent_id' => 2, 'name' => 'Apples'),
		);

		$this->_truncateData();
		$this->_insertData($data);

		// test with zero-config
		$result = $this->Model->generateTree();
		$this->assertEqual($result[0]['id'], 1);
		$this->assertEqual($result[0]['children'][0]['id'], 2);
		$this->assertEqual($result[0]['children'][0]['children'][0]['id'], 5);
		$this->assertEqual($result[1]['id'], 3);
		$this->assertEqual($result[1]['children'][0]['id'], 4);

		// test with limited depth
		$result = $this->Model->generateTree(array('depth' => 1));
		$this->assertEqual(Set::countDim($result, true), 4);

		// test with custom rootId
		$result = $this->Model->generateTree(array('rootId' => 3));
		$this->assertEqual($result[0]['id'], 3);
		$this->assertEqual($result[0]['children'][0]['id'], 4);
		$this->assertEqual(Set::countDim($result, true), 4);

		// test with with custom rootId and childrenOnly
		$result = $this->Model->generateTree(array('rootId' => 3, 'childrenOnly' => true));
		$this->assertEqual($result[0]['id'], 4);
		$this->assertEqual(Set::countDim($result, true), 2);
	}

	public function testGenerateTreeScoped() {
		$this->skipIf(true, 'Test me');
	}

	public function testAlterPosition() {
		// clear table
		$this->_truncateData();
		$this->assertEqual($this->Model->find('count'), 0);

		// add dummy data
		$this->_insertData($this->dummyData);
		$this->assertEqual($this->Model->find('count'), 5);

		// default tree
		$tree = $this->Model->generateTreeList();
		$expected = array(
			1 => 'Food',
			2 => 'Fruits',
			3 => 'Cars',
			4 => 'Audi',
			5 => 'Apples',
		);
		$this->assertEquals(array_values($tree), array_values($expected), 'Test data is corrupted');

		// move node inside empty target
		$result = $this->Model->alterPosition(array('position' => 'inside', 'node' => 5, 'target' => 1));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			1 => 'Food',
			5 => '_Apples',
			2 => 'Fruits',
			3 => 'Cars',
			4 => 'Audi',
		);
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move node inside empty target');

		// move node inside target
		$result = $this->Model->alterPosition(array('position' => 'inside', 'node' => 2, 'target' => 1));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			1 => 'Food',
			2 => '_Fruits',
			5 => '_Apples',
			3 => 'Cars',
			4 => 'Audi',
		);
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move node inside target');

		$result = $this->Model->alterPosition(array('position' => 'inside', 'node' => 4, 'target' => 3));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			1 => 'Food',
			2 => '_Fruits',
			5 => '_Apples',
			3 => 'Cars',
			4 => '_Audi',
		);
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move node inside target');


		// move node inside target (same parent)
		$result = $this->Model->alterPosition(array('position' => 'inside', 'node' => 5, 'target' => 1));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			1 => 'Food',
			5 => '_Apples',
			2 => '_Fruits',
			3 => 'Cars',
			4 => '_Audi',
		);
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move node inside target (same parent)');

		// move root node (with children) after target (same parent)
		$result = $this->Model->alterPosition(array('position' => 'after', 'node' => 1, 'target' => 3));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			3 => 'Cars',
			4 => '_Audi',
			1 => 'Food',
			5 => '_Apples',
			2 => '_Fruits',
		);
		//debug(compact('tree','expected'));
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move root node (with children) after target (same parent)');

		// move root node after target (same parent)
		$result = $this->Model->alterPosition(array('position' => 'after', 'node' => 3, 'target' => 1));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			1 => 'Food',
			5 => '_Apples',
			2 => '_Fruits',
			3 => 'Cars',
			4 => '_Audi',
		);
		//debug(compact('tree','expected'));
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move root node after target (same parent)');


		// move node after target (same parent)
		$result = $this->Model->alterPosition(array('position' => 'after', 'node' => 5, 'target' => 2));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			1 => 'Food',
			2 => '_Fruits',
			5 => '_Apples',
			3 => 'Cars',
			4 => '_Audi',
		);
		//debug(compact('tree','expected'));
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move node after target (same parent)');


		// move node before target (same parent)
		$result = $this->Model->alterPosition(array('position' => 'before', 'node' => 3, 'target' => 1));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			3 => 'Cars',
			4 => '_Audi',
			1 => 'Food',
			2 => '_Fruits',
			5 => '_Apples',
		);
		//debug(compact('tree','expected'));
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move node before target (same parent)');


		// move node (with children) before target (same parent)
		$result = $this->Model->alterPosition(array('position' => 'before', 'node' => 1, 'target' => 3));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			1 => 'Food',
			2 => '_Fruits',
			5 => '_Apples',
			3 => 'Cars',
			4 => '_Audi',
		);
		//debug(compact('tree','expected'));
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move node (with children) before target (same parent)');


		// move node before target (different parent)
		$result = $this->Model->alterPosition(array('position' => 'before', 'node' => 4, 'target' => 5));
		$this->assertTrue($result, 'alterPostion() failed');
		$tree = $this->Model->generateTreeList();
		$expected = array(
			1 => 'Food',
			2 => '_Fruits',
			4 => '_Audi',
			5 => '_Apples',
			3 => 'Cars',
		);
		//debug(compact('tree','expected'));
		$this->assertEquals(array_values($tree), array_values($expected), 'Failed to move node before target (different parent)');
	}

}

class TreeModModel extends AppModel {

}