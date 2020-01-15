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
 * NotificationTest.php
 * skyline-notification-service
 *
 * Created on 2020-01-15 13:59 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\Notification\Delivery\CallbackDelivery;
use Skyline\Notification\Fetch\Notification;
use Skyline\Notification\Service\AbstractNotificationService;
use Skyline\Notification\Service\MySQLNotificationService;
use Skyline\Notification\Service\SQLiteNotificationService;

/**
 * Class NotificationTest
 */
class NotificationTest extends TestCase
{
    public function getServiceInstances() {
        global $MySQL_PDO, $SQLite_PDO;

        return [
            [ new SQLiteNotificationService($SQLite_PDO) ],
            [ new MySQLNotificationService($MySQL_PDO) ]
        ];
    }

    /**
     * @param AbstractNotificationService $service
     * @dataProvider getServiceInstances
     */
    public function testPostNotification(AbstractNotificationService $service) {
        $arguments = NULL;

        $service->addDeliveryInstance(new CallbackDelivery("dev", function(Notification $notification) use (&$arguments) {
            $arguments[]= [
                $notification->getDomain()->getID(),
                $notification->getMessage(),
                $notification->getUser(),
                $notification->getTags()
            ];
            return true;
        }));

        $service->unregister(1);
        $service->unregister(2);
        $service->unregister(3);
        $service->unregister(4);


        $service->register(1, [1, 3], 'dev', 3);
        $service->register(2, [1], 'dev', 3);
        $service->register(3, [3], 'dev', 3);
        $service->register(4, [3], 'dev', 3);

        $arguments = [];
        $service->postNotification("Hi there", 2);

        $this->assertCount(0, $arguments);

        $service->postNotification("Hello World", 1);

        $this->assertEquals([
            [ 1, 'Hello World', 1, [] ],
            [ 1, 'Hello World', 2, [] ]
        ], $arguments);

        $arguments = [];

        $service->postNotification("Hello World", 3, ['test', 'hehe']);

        $this->assertEquals([
            [ 3, 'Hello World', 1, ['test', 'hehe'] ],
            [ 3, 'Hello World', 3, ['test', 'hehe'] ],
            [ 3, 'Hello World', 4, ['test', 'hehe'] ]
        ], $arguments);

        $service->unregister(1);
        $service->unregister(2);
        $service->unregister(3);
        $service->unregister(4);
    }
}
