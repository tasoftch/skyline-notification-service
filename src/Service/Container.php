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


use TASoft\Service\Container\AbstractContainer;
use TASoft\Util\PDO;

class Container extends AbstractContainer
{
    /** @var PDO */
    private $PDO;
    private $deliveryInstances;

    /**
     * Container constructor.
     * @param PDO $PDO
     * @param $deliveryInstances
     */
    public function __construct(PDO $PDO, $deliveryInstances = NULL)
    {
        $this->PDO = $PDO;
        $this->deliveryInstances = $deliveryInstances;
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
        switch ($this->getPDO()->getAttribute( PDO::ATTR_DRIVER_NAME )) {
            case 'mysql':
                return new MySQLNotificationService( $this->getPDO(), $this->deliveryInstances );
            default:
                return new SQLiteNotificationService( $this->getPDO(), $this->deliveryInstances );
        }
    }
}