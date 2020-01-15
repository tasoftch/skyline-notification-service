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
 * RegistrationTest.php
 * skyline-notification-service
 *
 * Created on 2020-01-09 13:52 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\Notification\Delivery\CallbackDelivery;
use Skyline\Notification\Delivery\NullDelivery;
use Skyline\Notification\NotificationServiceInterface;
use Skyline\Notification\Service\AbstractNotificationService;
use Skyline\Notification\Service\MySQLNotificationService;
use Skyline\Notification\Service\SQLiteNotificationService;

class RegistrationTest extends TestCase
{
    public function getServiceInstances() {
        global $MySQL_PDO, $SQLite_PDO;

        return [
            [ new SQLiteNotificationService($SQLite_PDO) ],
            [ new MySQLNotificationService($MySQL_PDO) ]
        ];
    }

    public function getNullDeliveryServiceInstances() {
        global $MySQL_PDO, $SQLite_PDO;

        return [
            [ new SQLiteNotificationService($SQLite_PDO, [
                new NullDelivery()
            ]) ],
            [ new MySQLNotificationService($MySQL_PDO, [
                new NullDelivery()
            ]) ]
        ];
    }

    /**
     * @dataProvider getServiceInstances
     * @expectedException \Skyline\Notification\Exception\DeliveryInstanceNotFoundException
     * @param NotificationServiceInterface $ns
     */
    public function testRegistrationFailureMissingDelivery(NotificationServiceInterface $ns) {
        $ns->register(
            13,
            [1, 2],
            '/dev/null',
            35
        );
    }

    /**
     * @dataProvider getNullDeliveryServiceInstances
     */
    public function testSuccessfulRegistration(AbstractNotificationService $ns) {
        $ns->unregister(1);
        $ns->unregister(2);
        $ns->unregister(3);
        $ns->unregister(4);

        $ns->register(
            13,
            [1, 2],
            '/dev/null',
            35
        );

        $this->assertEquals(1, $ns->getPDO()->selectFieldValue("SELECT count(id) AS C FROM SKY_NS_USER", 'C'));
        $this->assertEquals(2, $ns->getPDO()->selectFieldValue("SELECT count(domain) AS C FROM SKY_NS_USER_DOMAIN", 'C'));

        $ns->modify(13, [
            $ns->getDomain(3),
            1,
            2
        ]);

        $this->assertEquals(1, $ns->getPDO()->selectFieldValue("SELECT count(id) AS C FROM SKY_NS_USER", 'C'));
        $this->assertEquals(3, $ns->getPDO()->selectFieldValue("SELECT count(domain) AS C FROM SKY_NS_USER_DOMAIN", 'C'));


        $ns->modify(13, [
            2
        ]);

        $this->assertEquals(1, $ns->getPDO()->selectFieldValue("SELECT count(id) AS C FROM SKY_NS_USER", 'C'));
        $this->assertEquals(1, $ns->getPDO()->selectFieldValue("SELECT count(domain) AS C FROM SKY_NS_USER_DOMAIN", 'C'));

        $ns->unregister(13);

        $this->assertEquals(0, $ns->getPDO()->selectFieldValue("SELECT count(id) AS C FROM SKY_NS_USER", 'C'));
        $this->assertEquals(0, $ns->getPDO()->selectFieldValue("SELECT count(domain) AS C FROM SKY_NS_USER_DOMAIN", 'C'));

    }

    /**
     * @expectedException \Skyline\Notification\Exception\DuplicateRegistrationException
     * @dataProvider getNullDeliveryServiceInstances
     */
    public function testRegistrationRepetition(AbstractNotificationService $ns) {

        $ns->register(
            13,
            [
                1,
                3
            ],
            '/dev/null',
            35
        );

        $ns->register(
            13,
            [
                1,
                3
            ],
            '/dev/null',
            35
        );
    }

    /**
     * @param AbstractNotificationService $ns
     * @dataProvider getNullDeliveryServiceInstances
     * @depends testRegistrationRepetition
     */
    public function testModifyRegistration(AbstractNotificationService $ns) {
        $ns->unregister(13);

        $ns->register(
            13,
            [
                1,
                3
            ],
            '/dev/null',
            35
        );

        $ns->addDeliveryInstance(new CallbackDelivery('/my/name', function() {}));

        $records = iterator_to_array(
            $ns->getPDO()->select("SELECT * FROM SKY_NS_USER")
        );

        $this->assertCount(1, $records);
        $record = array_shift($records);

        $this->assertEquals(13, $record["id"]);
        $this->assertEquals('/dev/null', $record["delivery"]);
        $this->assertEquals(35, $record["options"]);

        $ns->modify(13, NULL, NULL, 45);

        $records = iterator_to_array(
            $ns->getPDO()->select("SELECT * FROM SKY_NS_USER")
        );

        $this->assertCount(1, $records);
        $record = array_shift($records);

        $this->assertEquals(13, $record["id"]);
        $this->assertEquals('/dev/null', $record["delivery"]);
        $this->assertEquals(45, $record["options"]);

        $ns->modify(13, NULL, '/my/name', 33);

        $records = iterator_to_array(
            $ns->getPDO()->select("SELECT * FROM SKY_NS_USER")
        );

        $this->assertCount(1, $records);
        $record = array_shift($records);

        $this->assertEquals(13, $record["id"]);
        $this->assertEquals('/my/name', $record["delivery"]);
        $this->assertEquals(33, $record["options"]);
    }
}
