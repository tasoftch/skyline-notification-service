<?php

namespace Skyline\Notification\PDO;

use TASoft\Util\PDO;

class PDOMap
{
	/** @var PDO */
	private $PDO;
	/** @var array */
	private $tableMap;

	public function __construct(PDO $PDO, array $tableMap = [])
	{
		$this->PDO = $PDO;
		$this->tableMap = $tableMap;
	}

	public function prepareSQL($sql) {
		return str_replace(array_keys($this->tableMap), array_values($this->tableMap), $sql);
	}

	public function exec($sql) {
		return $this->PDO->exec( $this->prepareSQL($sql) );
	}

	public function inject($sql) {
		yield from $this->PDO->inject( $this->prepareSQL($sql) );
	}

	public function select(string $sql, array $arguments = []) {
		yield from $this->PDO->select($this->prepareSQL($sql), $arguments);
	}

	public function lastInsertId($table = null) {
		return $this->PDO->lastInsertId( $this->tableMap[$table] ?? $table );
	}


}