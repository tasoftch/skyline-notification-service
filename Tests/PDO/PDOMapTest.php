<?php

namespace PDO;

use Skyline\Notification\PDO\PDOMap;
use PHPUnit\Framework\TestCase;

class PDOMapTest extends TestCase
{
	public function testPrepareSQL() {
		$map = new PDOMap(new \Skyline\PDO\SQLite("test.db"), [
			"TABLE1" => 'ikarus.TABLE_TEST'
		]);

		$this->assertEquals("SELECT * FROM ikarus.TABLE_TEST WHERE id = 34", $map->prepareSQL("SELECT * FROM TABLE1 WHERE id = 34"));
	}
}
