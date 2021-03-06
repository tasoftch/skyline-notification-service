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


use Skyline\Kernel\Service\SkylineServiceManager;
use Skyline\Notification\Domain\Domain;
use TASoft\EventManager\EventManagerInterface;
use TASoft\EventManager\SectionEventManager;

trait NotificationServiceTrait
{
    protected function getEventManager(): EventManagerInterface {
        return SkylineServiceManager::getEventManager();
    }

    /**
     * @param $message
     * @param Domain|string|int $domain
     * @param array|NULL $tags
     */
    protected function notify($message, $domain, array $tags = NULL) {
        $em = $this->getEventManager();

        if($em instanceof EventManagerInterface) {
            if($em instanceof SectionEventManager)
                $em->triggerSection( SKY_NOTIFICATION_EVENT_SECTION, SKY_NOTIFICATION_EVENT_NAME, NULL, $message, $domain, $tags);
            else
                $em->trigger(SKY_NOTIFICATION_EVENT_NAME, NULL, $message, $domain, $tags);
        }
    }
}