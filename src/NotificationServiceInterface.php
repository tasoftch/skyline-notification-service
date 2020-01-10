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

namespace Skyline\Notification;


use Skyline\Notification\Delivery\DeliveryInterface;
use Skyline\Notification\Exception\DeliveryInstanceNotFoundException;
use Skyline\Notification\Exception\DomainNotFoundException;
use Skyline\Notification\Exception\MissingDeliveryException;
use Skyline\Notification\Exception\MissingKindException;
use Skyline\Notification\Domain\Domain;
use TASoft\Service\ServiceInterface;

interface NotificationServiceInterface extends ServiceInterface
{
    /**
     * Gets a notification domain from persistent storage
     *
     * @param $nameOrID
     * @return Domain|null
     */
    public function getDomain($nameOrID): ?Domain;

    /**
     * Gets the designated delivery instance for a name.
     *
     * @param $name
     * @return DeliveryInterface|null
     */
    public function getDeliveryInstance(string $name): ?DeliveryInterface;

    /**
     * Registers a user to get notified
     *
     * @param int $user
     * @param Domain[]|string[]|int[] $domains
     * @param DeliveryInterface|string $delivery
     * @param int $options
     * @return void
     * @throws DomainNotFoundException
     * @throws DeliveryInstanceNotFoundException
     */
    public function register(int $user, array $domains, $delivery, int $options = 0);

    /**
     * Unregisters a user. If $domains is NULL, unregister all.
     * Please note: unregistering domains will not unregister the user, if the last kind was removed!
     * Setting $remainPendent to true won't drop pendent notifications. This can be used to change a user's kind list.
     *
     * @param int $user
     * @param Domain[]|NULL $domains
     * @param bool $remainPendent
     * @throws DomainNotFoundException
     * @return void
     */
    public function unregister(int $user, array $domains = NULL, bool $remainPendent = false);

    /**
     * Posts a notification to the storage.
     *
     * Depending how deliver modes work they can schedule or handle the notifications.
     *
     * @param $message
     * @param Domain|string|int $domain
     * @param string[] $tags
     * @return bool|int     Returns to how many users the notification is delivered or false on failure
     */
    public function postNotification($message, $domain, array $tags = []);

    /**
     * Call this method to look for pendent notifications and deliver them if needed.
     * This method mostly gets called from a command line tool like systemd, launchd or cron.
     */
    public function deliverPendentNotifications();
}