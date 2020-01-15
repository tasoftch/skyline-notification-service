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

namespace Skyline\Notification\Delivery;


use DateTime;
use Skyline\Notification\Fetch\Notification;

class SchedulerDelivery extends CallbackDelivery implements DeliveryScheduledInterface
{
    /** @var callable */
    private $scheduleCallback;
    /** @var callable */
    private $scheduleDeliveryCallback;

    /**
     * SchedulerDelivery constructor.
     * @param string $name
     * @param callable $scheduleCallback  getDeliveryDate(Notification $notification, int &$options): ?DateTime
     * @param callable $deliveryCallback  deliverScheduledNotification(Notification $notification, int $options): bool
     * @param callable $standardCallback  deliverNotification(Notification $notification)
     */
    public function __construct(string $name, callable $scheduleCallback, callable $deliveryCallback, callable $standardCallback)
    {
        parent::__construct($name, $standardCallback);
        $this->scheduleCallback = $scheduleCallback;
        $this->scheduleDeliveryCallback = $deliveryCallback;
    }

    public function getDeliveryDate(Notification $notification, int &$options): ?DateTime
    {
        return ($this->getScheduleCallback())($notification, $options);
    }

    public function deliverScheduledNotification(Notification $notification, int $options): bool
    {
        return ($this->getScheduleDeliveryCallback())($notification, $options) ? true : false;
    }

    /**
     * @return callable
     */
    public function getScheduleDeliveryCallback(): callable
    {
        return $this->scheduleDeliveryCallback;
    }

    /**
     * @return callable
     */
    public function getScheduleCallback(): callable
    {
        return $this->scheduleCallback;
    }

    /**
     * @param callable $scheduleCallback
     */
    public function setScheduleCallback(callable $scheduleCallback): void
    {
        $this->scheduleCallback = $scheduleCallback;
    }

    /**
     * @param callable $scheduleDeliveryCallback
     */
    public function setScheduleDeliveryCallback(callable $scheduleDeliveryCallback): void
    {
        $this->scheduleDeliveryCallback = $scheduleDeliveryCallback;
    }

}