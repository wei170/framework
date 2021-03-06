<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   framework
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Hubzero\Config\Tests\Processor;

use Hubzero\Test\Basic;
use Hubzero\Config\Processor\Ini;
use stdClass;

/**
 * Ini Processor tests
 */
class IniTest extends Basic
{
	/**
	 * Format processor
	 *
	 * @var  object
	 */
	private $processor = null;

	/**
	 * Expected data as an object
	 *
	 * @var  object
	 */
	private $obj = null;

	/**
	 * Expected data as an array
	 *
	 * @var  array
	 */
	private $arr = null;

	/**
	 * Expected data as a string
	 *
	 * @var  string
	 */
	private $str = '
[app]
application_env="development"
editor="ckeditor"
list_limit=25
helpurl="English (GB) - HUBzero help"
debug=1
debug_lang=0
sef=1
sef_rewrite=1
sef_suffix=0
sef_groups=0
feed_limit=10
feed_email="author"

[seo]
sef=1
sef_groups=0
sef_rewrite=1
sef_suffix=0
unicodeslugs=0
sitename_pagetitles=0';

	/**
	 * Test setup
	 *
	 * @return  void
	 **/
	protected function setUp()
	{
		$data = new stdClass();

		$data->app = new stdClass();
		$data->app->application_env = "development";
		$data->app->editor = "ckeditor";
		$data->app->list_limit = 25;
		$data->app->helpurl = "English (GB) - HUBzero help";
		$data->app->debug = 1;
		$data->app->debug_lang = 0;
		$data->app->sef = 1;
		$data->app->sef_rewrite = 1;
		$data->app->sef_suffix = 0;
		$data->app->sef_groups = 0;
		$data->app->feed_limit = 10;
		$data->app->feed_email = "author";

		$data->seo = new stdClass();
		$data->seo->sef = 1;
		$data->seo->sef_groups = 0;
		$data->seo->sef_rewrite = 1;
		$data->seo->sef_suffix = 0;
		$data->seo->unicodeslugs = 0;
		$data->seo->sitename_pagetitles = 0;

		$this->obj = $data;
		$this->arr = array(
			'app' => (array)$data->app,
			'seo' => (array)$data->seo
		);

		$this->processor = new Ini();

		parent::setUp();
	}

	/**
	 * Tests the getSupportedExtensions() method.
	 *
	 * @covers  \Hubzero\Config\Processor\Ini::getSupportedExtensions
	 * @return  void
	 **/
	public function testGetSupportedExtensions()
	{
		$extensions = $this->processor->getSupportedExtensions();

		$this->assertTrue(is_array($extensions));
		$this->assertCount(1, $extensions);
		$this->assertTrue(in_array('ini', $extensions));
	}

	/**
	 * Tests the canParse() method.
	 *
	 * @covers  \Hubzero\Config\Processor\Ini::canParse
	 * @return  void
	 **/
	public function testCanParse()
	{
		$this->assertFalse($this->processor->canParse('Cras justo odio, dapibus ac facilisis in, egestas eget quam.'));
		$this->assertFalse($this->processor->canParse('{"application_env":"development","editor":"ck = editor","list_limit":"25"}'));
		$this->assertFalse($this->processor->canParse('<foo att=="val">Cras justo odio dapibus ac facilisis in, egestas eget quam.</foo>'));
		$this->assertTrue($this->processor->canParse($this->str));
	}

	/**
	 * Tests the parse() method.
	 *
	 * @covers  \Hubzero\Config\Processor\Ini::parse
	 * @return  void
	 **/
	public function testParse()
	{
		$result = $this->processor->parse(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Files' . DIRECTORY_SEPARATOR . 'test.ini');

		$this->assertEquals($this->arr, $result);

		$this->setExpectedException('Hubzero\Config\Exception\ParseException');

		$result = $this->processor->parse(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Files' . DIRECTORY_SEPARATOR . 'test.xml');
	}

	/**
	 * Tests the objectToString() method.
	 *
	 * @covers  \Hubzero\Config\Processor\Ini::objectToString
	 * @return  void
	 **/
	public function testObjectToString()
	{
		// Test that a string is returned as-is
		$result = $this->processor->objectToString($this->str);

		$this->assertEquals($this->str, $result);

		// Test object to string conversion
		$result = $this->processor->objectToString($this->obj);

		$this->assertEquals($this->str, $result);
	}

	/**
	 * Tests the stringToObject() method.
	 *
	 * @covers  \Hubzero\Config\Processor\Ini::stringToObject
	 * @return  void
	 **/
	public function testStringToObject()
	{
		// Test that an object is returned as-is
		$result = $this->processor->stringToObject($this->obj, array('processSections' => true));

		$this->assertEquals($this->obj, $result);

		// Test that an empty string returns an empty stdClass object
		$result = $this->processor->stringToObject('', array('processSections' => true));

		$this->assertEquals(new stdClass, $result);

		// Test that a string gets converted as expected
		$result = $this->processor->stringToObject($this->str, array('processSections' => true));

		$this->assertEquals($this->obj, $result);
	}
}
