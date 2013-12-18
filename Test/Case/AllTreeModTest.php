<?php
/**
 * Class AllTreeModTest
 */
class AllTreeModTest extends CakeTestSuite {

/**
 * @return CakeTestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All TreeMod plugin tests');

		$caseDir = dirname(__FILE__) . DS;

		$suite->addTestDirectoryRecursive($caseDir . 'Model');
		return $suite;
	}
}

