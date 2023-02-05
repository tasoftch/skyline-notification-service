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
		return preg_replace_callback("/SKY_NS_[a-z0-9_]+/i", function($ms) {
			return $this->tableMap[ $ms[0] ] ?: $ms[0];
		}, $sql);
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

	public function selectOne($sql, $arguments = []) {
		return $this->PDO->selectOne($this->prepareSQL( $sql ), $arguments);
	}

	public function transaction(callable $callbackToPerformTransaction, bool $propagateException = true, bool $bindToPDO = false): bool {
		return $this->PDO->transaction($callbackToPerformTransaction, $propagateException, $bindToPDO);
	}

	public function quote($string, $type = PDO::PARAM_STR) {
		return $this->PDO->quote($string, $type);
	}
}