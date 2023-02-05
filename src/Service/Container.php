<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2020, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Skyline\Notification\Service;


use Skyline\Notification\ConflictSolver\PickFirstSolver;
use Skyline\Notification\ConflictSolver\PickPostedSolver;
use TASoft\Service\Container\AbstractContainer;
use TASoft\Util\PDO;

class Container extends AbstractContainer
{
	const RESOLVER_DISABLED = '';
	const RESOLVER_PICK_EARLIEST = 'PickEarliestSolver';
	const RESOLVER_PICK_FIRST = 'PickFirstSolver';
	const RESOLVER_PICK_LAST = 'PickLastSolver';
	const RESOLVER_PICK_LATEST = 'PickLatestSolver';
	const RESOLVER_PICK_POSTED = 'PickPostedSolver';


	/** @var PDO */
    private $PDO;
    private $deliveryInstances = [];
	private $tableMap;

	/** @var  */
	private $resolver;

	/**
	 * Container constructor.
	 * @param PDO $PDO
	 * @param array $deliveryInstances
	 * @param null $tableMap
	 * @param string $resolver
	 */
    public function __construct(PDO $PDO, array $deliveryInstances = [], $tableMap = NULL, string $resolver = self::RESOLVER_DISABLED)
    {
        $this->PDO = $PDO;
        $this->deliveryInstances = $deliveryInstances;
		$this->tableMap = $tableMap;
		$this->resolver = $resolver;
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->PDO;
    }



    protected function loadInstance()
    {
		$service = null;
        switch ($this->getPDO()->getAttribute( PDO::ATTR_DRIVER_NAME )) {
            case 'mysql':
                $service = new MySQLNotificationService( $this->getPDO(), $this->deliveryInstances, $this->tableMap ?: [] );
				break;
            default:
                $service = new SQLiteNotificationService( $this->getPDO(), $this->deliveryInstances, $this->tableMap ?: [] );
        }

		if($this->resolver) {
			$class = "\Skyline\Notification\ConflictSolver\\$this->resolver";
			$service->setResolver(new $class);
		}

		return $service;
    }
}