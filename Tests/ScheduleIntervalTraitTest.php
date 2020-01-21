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
 * ScheduleIntervalTraitTest.php
 * skyline-notification-service
 *
 * Created on 2020-01-21 17:28 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\Notification\Delivery\ScheduleIntervalTrait;
use Skyline\Notification\Domain\Domain;
use Skyline\Notification\Fetch\Notification;

class ScheduleIntervalTraitTest extends TestCase
{
    use ScheduleIntervalTrait;

    public function testScheduleIntervalFromNotification() {
        $not = new MockNotification(0);
        $intv = $this->getDailyInterval($not);
        $this->assertEquals("0 0 * * *", $intv);

        $not = new MockNotification(3600);
        $intv = $this->getDailyInterval($not);
        $this->assertEquals("0 1 * * *", $intv);

        $not = new MockNotification(36000);
        $intv = $this->getDailyInterval($not);
        $this->assertEquals("0 10 * * *", $intv);

        $not = new MockNotification(12.5 * 3600);
        $intv = $this->getDailyInterval($not);
        $this->assertEquals("30 12 * * *", $intv);

        $not = new MockNotification(23.75 * 3600);
        $intv = $this->getDailyInterval($not);
        $this->assertEquals("45 23 * * *", $intv);
    }

    public function testNextScheduledInterval() {
        $nowSec = date("G") * 3600 + date("i") * 60;
        $now = new DateTime("today +{$nowSec}sec");

        $not = new MockNotification($nowSec + 180);
        $next = $this->getNextDailyDate($not);

        $this->assertEquals(180, $next->getTimestamp() - $now->getTimestamp());

        $not = new MockNotification($nowSec - 180);
        $next = $this->getNextDailyDate($not);

        $this->assertEquals(86400 - 180, $next->getTimestamp() - $now->getTimestamp());
    }
}

class MockNotification extends Notification {
    public function __construct(int $userOptions = NULL)
    {
        parent::__construct(1, new Domain([
            Domain::ID_KEY => 15,
            Domain::NAME_KEY => 'domain'
        ]), "", [], $userOptions, 1, NULL);
    }
}
