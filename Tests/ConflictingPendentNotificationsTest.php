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
 * ConflictingPendentNotificationsTest.php
 * skyline-notification-service
 *
 * Created on 2020-01-16 11:11 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\Notification\ConflictSolver\CallbackResolver;
use Skyline\Notification\ConflictSolver\PickPostedSolver;
use Skyline\Notification\Delivery\SchedulerDelivery;
use Skyline\Notification\Fetch\Notification;
use Skyline\Notification\Service\AbstractNotificationService;
use Skyline\Notification\Service\MySQLNotificationService;
use Skyline\Notification\Service\SQLiteNotificationService;

class ConflictingPendentNotificationsTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        global $MySQL_PDO, $SQLite_PDO;

        setupPDO($MySQL_PDO);
        setupPDO($SQLite_PDO);
    }

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
    public function testOneConflict(AbstractNotificationService $service) {
        $scheduled = [];
        $delivered = [];

        $service->addDeliveryInstance(
            new SchedulerDelivery("dev", function() {
                return new DateTime('now -1second');
            }, function(Notification $notification) use (&$scheduled) {
                $scheduled[] = [
                    $notification->getDomain()->getID(),
                    $notification->getMessage(),
                    $notification->getUser(),
                    $notification->getTags()
                ];
                return true;
            }, function(Notification $notification) use (&$delivered) {
                $delivered[] = [
                    $notification->getDomain()->getID(),
                    $notification->getMessage(),
                    $notification->getUser(),
                    $notification->getTags()
                ];
                return true;
            } )
        );

        $service->register(25, [2], 'dev');

        $service->postNotification("Hello", 2, [1,2,3]);
        $service->postNotification("Hello", 2, [1,2,3]);
        $service->postNotification("Hello", 2, [1,2,3]);

        $service->deliverPendentNotifications();

        $this->assertCount(3, $scheduled);
        $scheduled = [];
        $conflicting = [];

        $service->setResolver( new CallbackResolver(function(array $notifications) use (&$conflicting) {
            foreach($notifications as $notification) {
                $conflicting[] = [
                    $notification->getDomain()->getID(),
                    $notification->getMessage(),
                    $notification->getUser(),
                    $notification->getTags()
                ];
            }
            return array_shift($notifications);
        }) );

        $service->postNotification("Hello", 2, [1]);
        $service->postNotification("Hello", 2, [2]);
        $service->postNotification("Hello", 2, [3]);

        $service->deliverPendentNotifications();

        $this->assertCount(3, $scheduled);
        $this->assertCount(0, $conflicting);
        $scheduled = [];


        $service->postNotification("One", 2, [1]);
        $service->postNotification("Two", 2, [1, 2]);

        $service->deliverPendentNotifications();

        $this->assertEquals([
            [
                2,
                'One',
                25,
                [1]
            ],
            [
                2,
                'Two',
                25,
                [1, 2]
            ]
        ], $conflicting);

        $this->assertEquals([
            [
                2,
                'One',
                25,
                [1]
            ]
        ], $scheduled);

        $scheduled = [];
        $conflicting = [];




        $service->postNotification("Three", 2, [1, 2]);
        $service->postNotification("Four", 2, [1]);

        $service->deliverPendentNotifications();

        $this->assertEquals([
            [
                2,
                'Three',
                25,
                [1] // Does only return the common tags!
            ],
            [
                2,
                'Four',
                25,
                [1]
            ]
        ], $conflicting);

        $this->assertEquals([
            [
                2,
                'Three',
                25,
                [1, 2]
            ]
        ], $scheduled);

        $scheduled = [];
        $conflicting = [];

        $service->setResolver( new PickPostedSolver() );

        $service->postNotification("Five", 2, [1, 2]);  // Post
        $service->postNotification("Six", 2, [3]);      // Post
        $service->postNotification("Seven", 2, [2]);    // Replace Five (common 2)
        $service->postNotification("Eight", 2, [1]);    // Post (Five was deleted by Seven)
        $service->postNotification("Nine", 2, [1, 3]);  // Replace Six and Eight
        $service->postNotification("Ten", 2);           // Post (no tags just post)

        $service->deliverPendentNotifications();

        $this->assertEquals([
            [
                2,
                'Seven',
                25,
                [2]
            ],
            [
                2,
                'Nine',
                25,
                [1, 3]
            ],
            [
                2,
                'Ten',
                25,
                NULL  // No tags
            ]
        ], $scheduled);
    }
}
