<?php
App::uses('TreeModBehavior', 'Taxonomy.Model');

/**
 * TreeModBehavior Test Case
 *
 */
class TreeModBehaviorTest extends CakeTestCase {

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->TreeModBehavior);

		parent::tearDown();
	}

/**
 * @return void
 */
	public function testGenerateList() {
		$this->skipIf(true, 'Test me');
	}

/**
 * @return void
 */
	public function testAlterPosition() {
		$this->skipIf(true, 'Test me');
	}

}
