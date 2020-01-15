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
 * ScheduledNotificationsTest.php
 * skyline-notification-service
 *
 * Created on 2020-01-15 14:20 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\Notification\Delivery\CallbackDelivery;
use Skyline\Notification\Delivery\SchedulerDelivery;
use Skyline\Notification\Fetch\Notification;
use Skyline\Notification\NotificationServiceInterface;
use Skyline\Notification\Service\AbstractNotificationService;
use Skyline\Notification\Service\MySQLNotificationService;
use Skyline\Notification\Service\SQLiteNotificationService;

class ScheduledNotificationsTest extends TestCase
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
    public function testScheduledNotification(AbstractNotificationService $service) {
        $schedule = true;
        $scheduled = [];
        $delivered = [];

        $service->addDeliveryInstance(
            new SchedulerDelivery("dev", function(Notification $notification, &$options) use (&$schedule) {
                $options = 11;

                return $schedule ? new DateTime('now +1second') : NULL;
            }, function(Notification $notification, $options) use (&$scheduled) {
                $this->assertEquals(11, $options);
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

        $service->unregister(13);
        $service->unregister(1);
        $service->unregister(2);
        $service->unregister(3);
        $service->unregister(4);


        $service->register(1, [1], 'dev', 2);
        $service->register(2, [1, 2], 'dev', 2);
        $service->register(3, [2, 3], 'dev', 2);

        $service->postNotification("Hello", 1, ["test", "args"]);
        $this->assertCount(0, $delivered);
        $this->assertCount(0, $scheduled);

        $service->deliverPendentNotifications();

        $this->assertCount(0, $delivered);
        $this->assertCount(0, $scheduled);

        $this->assertEquals(2, $service->getPDO()->selectFieldValue("SELECT count(*) AS C FROM SKY_NS_ENTRY_PENDENT", 'C'));

        usleep(1100000);

        $service->deliverPendentNotifications();

        $this->assertCount(0, $delivered);
        $this->assertCount(2, $scheduled);

        $schedule = false;
        $service->postNotification("Hello", 1, ["test", "args"]);

        $this->assertCount(2, $delivered);
        $this->assertCount(2, $scheduled);

        $service->deliverPendentNotifications();

        $this->assertCount(2, $delivered);
        $this->assertCount(2, $scheduled);

        $service->deliverPendentNotifications();

        $this->assertCount(2, $delivered);
        $this->assertCount(2, $scheduled);
    }
}
