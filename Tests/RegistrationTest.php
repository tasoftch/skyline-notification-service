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
use Skyline\Notification\Deliver\NullDelivery;
use Skyline\Notification\NotificationService;

class RegistrationTest extends TestCase
{
    /**
     * @expectedException \Skyline\Notification\Exception\MissingDeliveryException
     */
    public function testRegistrationFailureMissingDelivery() {
        global $PDO;
        $ns = new NotificationService($PDO, [
            // No delivery instance set
        ]);

        $ns->register(
            13,
            [
                $ns->getKind(1),
                $ns->getKind(2)
            ],
            '/dev/null',
            35
        );
    }

    public function testSuccessfulRegistration() {
        global $PDO;
        $ns = new NotificationService($PDO, [
            new NullDelivery()
        ]);

        $this->assertTrue($ns->register(
            13,
            [
                $ns->getKind(1),
                $ns->getKind(2)
            ],
            '/dev/null',
            35
        ));

        $this->assertEquals(1, $PDO->selectFieldValue("SELECT count(user) AS C FROM SKY_NS_REGISTER", 'C'));
        $this->assertEquals(2, $PDO->selectFieldValue("SELECT count(kind) AS C FROM SKY_NS_REGISTER_KIND", 'C'));

        $this->assertTrue(
            $ns->unregister(13, [
                $ns->getKind(3)
            ])
        );

        $this->assertEquals(1, $PDO->selectFieldValue("SELECT count(user) AS C FROM SKY_NS_REGISTER", 'C'));
        $this->assertEquals(2, $PDO->selectFieldValue("SELECT count(kind) AS C FROM SKY_NS_REGISTER_KIND", 'C'));

        $this->assertTrue(
            $ns->unregister(13, [
                $ns->getKind(1)
            ])
        );

        $this->assertEquals(1, $PDO->selectFieldValue("SELECT count(user) AS C FROM SKY_NS_REGISTER", 'C'));
        $this->assertEquals(1, $PDO->selectFieldValue("SELECT count(kind) AS C FROM SKY_NS_REGISTER_KIND", 'C'));

        $this->assertTrue(
            $ns->unregister(13)
        );

        $this->assertEquals(0, $PDO->selectFieldValue("SELECT count(user) AS C FROM SKY_NS_REGISTER", 'C'));
        $this->assertEquals(0, $PDO->selectFieldValue("SELECT count(kind) AS C FROM SKY_NS_REGISTER_KIND", 'C'));
    }

    /**
     * @expectedException \Skyline\Notification\Exception\DuplicateRegistrationException
     */
    public function testRegistrationRepetition() {
        global $PDO;
        $ns = new NotificationService($PDO, [
            new NullDelivery()
        ]);

        $this->assertTrue($ns->register(
            13,
            [
                $ns->getKind(1),
                $ns->getKind(2)
            ],
            '/dev/null',
            35
        ));

        $this->assertFalse($ns->register(
            13,
            [
                $ns->getKind(1),
                $ns->getKind(3)
            ],
            '/dev/null',
            35
        ));
    }

    /**
     * @depends testRegistrationRepetition
     */
    public function testUnregisterAll() {
        global $PDO;
        $ns = new NotificationService($PDO, [
            new NullDelivery()
        ]);

        $ns->unregister(13);
        $this->assertEquals(0, $PDO->selectFieldValue("SELECT count(user) AS C FROM SKY_NS_REGISTER", 'C'));
        $this->assertEquals(0, $PDO->selectFieldValue("SELECT count(kind) AS C FROM SKY_NS_REGISTER_KIND", 'C'));
    }


}
