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

/**
 * KindTest.php
 * skyline-notification-service
 *
 * Created on 2020-01-09 13:07 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\Notification\NotificationServiceInterface;
use Skyline\Notification\Service\MySQLNotificationService;
use Skyline\Notification\Service\SQLiteNotificationService;

class DomainTest extends TestCase
{
    public function getServiceInstances() {
        global $MySQL_PDO, $SQLite_PDO;

        return [
            [ new SQLiteNotificationService($SQLite_PDO) ],
            [ new MySQLNotificationService($MySQL_PDO) ]
        ];
    }

    /**
     * @dataProvider getServiceInstances
     */
    public function testKinds(NotificationServiceInterface $ns) {

        $kind = $ns->getDomain(3);
        $other = $ns->getDomain("Page Changed");

        $this->assertEquals(3, $kind->getID());
        $this->assertEquals("Role Changed", $kind->getName());

        $this->assertEquals(2, $other->getID());
        $this->assertEquals("Page Changed", $other->getName());

        $other = $ns->getDomain('Role Changed');

        $this->assertSame($other, $kind);
    }
}
