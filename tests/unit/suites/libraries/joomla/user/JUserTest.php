<?php
/**
 * @package     Joomla.UnitTest
 * @subpackage  User
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

/**
 * Test class for JUser.
 * Generated by PHPUnit on 2012-01-22 at 02:37:10.
 *
 * @package     Joomla.UnitTest
 * @subpackage  User
 * @since       12.1
 */
class JUserTest extends TestCaseDatabase
{
	/**
	 * @var    JUser
	 * @since  12.1
	 */
	protected $object;

	/**
	 * Sets up the fixture.
	 *
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 *
	 * @since   12.1
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->saveFactoryState();

		$this->object = new JUser('42');

		JFactory::$application = $this->getMockApplication();
	}

	/**
	 * Overrides the parent tearDown method.
	 *
	 * @return  void
	 *
	 * @see     PHPUnit_Framework_TestCase::tearDown()
	 * @since   12.1
	 */
	protected function tearDown()
	{
		$this->restoreFactoryState();

		parent::tearDown();
	}

	/**
	 * Gets the data set to be loaded into the database during setup
	 *
	 * @return  PHPUnit_Extensions_Database_DataSet_CsvDataSet
	 *
	 * @since   12.1
	 */
	protected function getDataSet()
	{
		$dataSet = new PHPUnit_Extensions_Database_DataSet_CsvDataSet(',', "'", '\\');

		$dataSet->addTable('jos_assets', JPATH_TEST_DATABASE . '/jos_assets.csv');
		$dataSet->addTable('jos_extensions', JPATH_TEST_DATABASE . '/jos_extensions.csv');
		$dataSet->addTable('jos_users', JPATH_TEST_DATABASE . '/jos_users.csv');
		$dataSet->addTable('jos_user_usergroup_map', JPATH_TEST_DATABASE . '/jos_user_usergroup_map.csv');
		$dataSet->addTable('jos_usergroups', JPATH_TEST_DATABASE . '/jos_usergroups.csv');

		return $dataSet;
	}

	/**
	 * Test cases for getInstance
	 *
	 * @return  array
	 *
	 * @since   12.1
	 */
	public function casesGetInstance()
	{
		return array(
			'42' => array(
				42,
				'JUser'
			),
			'99' => array(
				99,
				'JUser'
			)
		);
	}

	/**
	 * Tests JUser::getInstance().
	 *
	 * @param   mixed  $userid    User ID or name
	 * @param   mixed  $expected  User object or false if unknown
	 *
	 * @return  void
	 *
	 * @since   12.1
	 *
	 * @covers  JUser::getInstance
	 * @dataProvider casesGetInstance
	 */
	public function testGetInstance($userid, $expected)
	{
		$user = JUser::getInstance($userid);
		$this->assertThat(
			$user,
			$this->isInstanceOf($expected)
		);
	}

	/**
	 * Tests JUser::getInstance() with an error
	 *
	 * @return  void
	 *
	 * @since   12.1
	 *
	 * @covers  JUser::getInstance
	 */
	public function testGetInstanceError()
	{
		$this->assertFalse(
			JUser::getInstance('nobody')
		);
	}

	/**
	 * Tests JUser Parameter setting and retrieval.
	 *
	 * @return  void
	 *
	 * @since   12.1
	 *
	 * @covers JUser::defParam
	 * @covers JUser::getParam
	 * @covers JUser::setParam
	 */
	public function testParameter()
	{
		$this->assertThat(
			$this->object->getParam('holy', 'fred'),
			$this->equalTo('fred')
		);

		$this->object->defParam('holy', 'batman');
		$this->assertThat(
			$this->object->getParam('holy', 'fred'),
			$this->equalTo('batman')
		);

		$this->object->setParam('holy', 'batman');
		$this->assertThat(
			$this->object->getParam('holy', 'fred'),
			$this->equalTo('batman')
		);
	}

	/**
	 * Test cases for testAuthorise
	 *
	 * @return  array
	 *
	 * @since   12.1
	 */
	public function casesAuthorise()
	{
		return array(
			'Publisher Create' => array(
				43,
				'core.create',
				'com_content',
				true
			),
			'null asset Super Admin' => array(
				42,
				'core.create',
				null,
				true
			),
			'fictional action Super Admin' => array(
				42,
				'nuke',
				'root.1',
				true
			),
			'core.admin Other user' => array(
				43,
				'core.admin',
				'root.1',
				false
			),
			'core.admin Super Admin' => array(
				42,
				'core.admin',
				'root.1',
				true
			),
			'core.admin emergency root_user' => array(
				99,
				'core.admin',
				'root.1',
				true,
			)
		);
	}

	/**
	 * Tests JUser::authorise().
	 *
	 * @param   integer  $userId    User id of user to test
	 * @param   string   $action    Action to get aithorized for this user
	 * @param   string   $asset     Asset to get authorization for
	 * @param   boolean  $expected  Expected return from the authorization check
	 *
	 * @return  void
	 *
	 * @since   12.1
	 *
	 * @covers  JUser::authorise
	 * @dataProvider  casesAuthorise
	 */
	public function testAuthorise($userId, $action, $asset, $expected)
	{
		// Set up user 99 to be root_user from configuration
		$testConfig = $this->getMock('JConfig', array('get'));
		$testConfig->expects(
			$this->any()
		)
			->method('get')
			->will($this->returnValue(99));
		JFactory::$config = $testConfig;

		// Run through test cases
		$user = new JUser($userId);
		$this->assertThat(
			$user->authorise($action, $asset),
			$this->equalTo($expected),
			'Line: ' . __LINE__ . ' Failed for user ' . $user->id
		);

	}

	/**
	 * Test getAuthorisedCategories
	 *
	 * @covers JUser::getAuthorisedCategories
	 * @todo Implement testGetAuthorisedCategories().
	 *
	 * @return void
	 */
	public function testGetAuthorisedCategories()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test cases for testGetAuthorisedViewLevels
	 *
	 * @return  array
	 *
	 * @since   12.1
	 */
	public function casesGetAuthorisedViewLevels()
	{
		return array(
			'User42' => array(
				null,
				array(1)
			),
			'User43' => array(
				43,
				array(1)
			),
			'User99' => array(
				99,
				array(1)
			)
		);
	}

	/**
	 * Tests JUser::getAuthorisedViewLevels().
	 *
	 * @param   integer  $user      User id of user to test
	 * @param   array    $expected  Authorized levels of use
	 *
	 * @return  void
	 *
	 * @since   12.1
	 *
	 * @covers  JUser::getAuthorisedViewLevels
	 * @dataProvider  casesGetAuthorisedViewLevels
	 */
	public function testGetAuthorisedViewLevels($user, $expected)
	{
		if ($user)
		{
			$user = new JUser($user);
		}
		else
		{
			$user = $this->object;
		}

		$this->assertThat(
			$user->getAuthorisedViewLevels(),
			$this->equalTo($expected),
			'Failed for user ' . $user->id
		);
	}

	/**
	 * Test...
	 *
	 * @covers JUser::getAuthorisedGroups
	 * @todo Implement testGetAuthorisedGroups().
	 *
	 * @return void
	 */
	public function testGetAuthorisedGroups()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Tests JUser::setLastVisit().
	 *
	 * @return  void
	 *
	 * @since   12.1
	 * @covers  JUser::setLastVisit
	 */
	public function testSetLastVisit()
	{
		$timestamp = '2012-01-22 02:00:00';

		$this->object->setLastVisit($timestamp);
		$testUser = new JUser(42);
		$this->assertThat(
			$testUser->lastvisitDate,
			$this->equalTo($timestamp)
		);
	}

	/**
	 * Test the setParameters method.
	 *
	 * @covers JUser::setParameters
	 * @todo Implement testSetParameters().
	 *
	 * @return void
	 */
	public function testSetParameters()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test...
	 *
	 * @covers JUser::getTable
	 * @todo Implement testGetTable().
	 *
	 * @return void
	 */
	public function testGetTable()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test...
	 *
	 * @covers JUser::bind
	 * @todo Implement testBind().
	 *
	 * @return void
	 */
	public function testBind()
	{
		$array = array();
		$string = '12345678901234567890123456789012345678901234567890123456789012345678901234567890'
			. '12345678901234567890123456789012345678901234567890123456789012345678901234567890'
			. '1234567890123456789012345678901234567890';

		$array['username'] = $string;
		$array['password'] = $string;
		$array['password2'] = $string;

		$testUser = new JUser;
		$result = $testUser->bind($array);
		$this->assertTrue(
			$result
		);

		$this->markTestIncomplete('Unexpected test failure in CMS environment');

		$this->assertTrue(
			(strlen($testUser->username) >= 1 && strlen($testUser->username) <= 150)
		);

		$this->assertTrue(
			(strlen($testUser->password) >= 1 && strlen($testUser->password) <= 100)
		);

		$array['password2'] = 'password_ok_not_same';

		$testUser = new JUser;
		$result = $testUser->bind($array);
		$this->assertFalse(
			$result
		);
	}

	/**
	 * Test...
	 *
	 * @covers JUser::save
	 * @todo Implement testSave().
	 *
	 * @return void
	 */
	public function testSave()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test...
	 *
	 * @covers JUser::delete
	 * @todo Implement testDelete().
	 *
	 * @return void
	 */
	public function testDelete()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * Test cases for testLoad
	 *
	 * @return  array
	 *
	 * @since   12.1
	 */
	public function casesLoad()
	{
		return array(
			'non-existant' => array(
				1120,
				false,
				true
			),
			'existing' => array(
				42,
				true,
				false
			),
			'existing-but-guest' => array(
				0,
				false,
				true
			)
		);
	}

	/**
	 * Tests JUser::load().
	 *
	 * @param   integer  $id        User ID to load
	 * @param   boolean  $expected  Expected result of load operation
	 * @param   boolean  $isGuest   Boolean marking an user as guest
	 *
	 * @return  void
	 *
	 * @since   12.1
	 *
	 * @dataProvider casesLoad
	 * @covers  JUser::load
	 */
	public function testLoad($id, $expected, $isGuest)
	{
		$testUser = new JUser($id);

		$this->assertThat(
			$testUser->load($id),
			$this->equalTo($expected)
		);

		$this->assertThat(
			$isGuest,
			$this->equalTo(TestReflection::getValue($testUser, 'guest'))
		);
	}
}
