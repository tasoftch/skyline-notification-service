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

namespace Skyline\Notification\Service;

use DateTime;
use Skyline\Notification\Delivery\DeliveryInterface;
use Skyline\Notification\Domain\Domain;
use Skyline\Notification\Exception\DuplicateRegistrationException;
use Skyline\Notification\Fetch\Notification;
use Skyline\Notification\Fetch\PendentEntry;
use Skyline\Notification\Fetch\PendentEntryInterface;
use Skyline\Notification\Fetch\Registration;

/**
 * The default notification service implements all SQL relevant methods that are valid for most SQL languages.
 *
 * @package Skyline\Notification\Service
 */
abstract class AbstractDefaultNotificationService extends AbstractNotificationService
{
    protected function makeDomain($nameOrID): ?Domain
    {
        $record = $this->getPDO()->selectOne("SELECT id, name, description, options FROM SKY_NS_DOMAIN WHERE id = ? OR name = ?", [$nameOrID, $nameOrID]);
        if($record)
            return new Domain($record);
        return NULL;
    }

    protected function makeUserRegistration(int $user, DeliveryInterface $delivery, int $options): bool
    {
        if($r = $this->getPDO()->selectOne("SELECT id, options, delivery FROM SKY_NS_USER WHERE id = ?", [$user])) {
            $e = new DuplicateRegistrationException("User $user is already registered");
            $e->setExistingRegistration($r);
            throw $e;
        }

        $this->getPDO()->inject("INSERT INTO SKY_NS_USER (id, options, delivery) VALUES ($user, $options, ?)")->send([
            $delivery->getName()
        ]);
        return true;
    }

    protected function updateUserRegistration(int $user, ?array $domains, ?DeliveryInterface $delivery, ?int $options)
    {
        $PDO = $this->getPDO();
        $PDO->transaction(function() use ($PDO, $user, $domains, $delivery, $options) {
            if(NULL !== $options && NULL !== $delivery) {
                $this->getPDO()->inject("UPDATE SKY_NS_USER SET delivery = ?, options = ? WHERE id = $user")->send([
                    $delivery->getName(),
                    $options
                ]);
            } elseif(NULL !== $options) {
                $this->getPDO()->inject("UPDATE SKY_NS_USER SET options = ? WHERE id = $user")->send([
                    $options
                ]);
            } elseif(NULL !== $delivery) {
                $this->getPDO()->inject("UPDATE SKY_NS_USER SET delivery = ? WHERE id = $user")->send([
                    $delivery->getName()
                ]);
            }

            if($domains) {
                $domains = implode(",", array_map(function(Domain $domain) use ($user) {
                    return sprintf("($user, %d)", $domain->getID());
                }, $domains));
                $this->getPDO()->exec("DELETE FROM SKY_NS_USER_DOMAIN WHERE user = $user");
                $this->getPDO()->exec("INSERT INTO SKY_NS_USER_DOMAIN (user, domain) VALUES $domains");
            }
        });
    }

    protected function clearRegistration(int $user)
    {
        $this->getPDO()->exec("DELETE FROM SKY_NS_ENTRY_PENDENT WHERE user = $user");
        $this->getPDO()->exec("DELETE FROM SKY_NS_USER WHERE id = $user");
        $this->getPDO()->exec("DELETE FROM SKY_NS_USER_DOMAIN WHERE user = $user");

        $this->clearUnusedEntries();
    }

    protected function clearEntries(?int $user, bool $completedOnly)
    {
        if($user !== NULL) {
            if($completedOnly)
                $this->getPDO()->exec("DELETE FROM SKY_NS_ENTRY_PENDENT WHERE id = $user AND completed IS NOT NULL");
            else
                $this->getPDO()->exec("DELETE FROM SKY_NS_ENTRY_PENDENT WHERE id = $user");
        } else {
            if($completedOnly)
                $this->getPDO()->exec("DELETE FROM SKY_NS_ENTRY_PENDENT WHERE completed IS NOT NULL");
            else
                $this->getPDO()->exec("DELETE FROM SKY_NS_ENTRY_PENDENT WHERE 1");
        }

        $this->clearUnusedEntries();
    }

    protected function fetchConflictingEntries(int $user, array $tags, ?int $options): ?array
    {
        $tags = array_map(function($v) {
            return $this->getPDO()->quote( $v );
        }, $tags);

        $entries = NULL;

        foreach($this->getPDO()->select("SELECT
SKY_NS_ENTRY_PENDENT.id,
       domain,
       message,
       updated,
       name
FROM SKY_NS_ENTRY
JOIN SKY_NS_ENTRY_TAG ON SKY_NS_ENTRY_TAG.entry = SKY_NS_ENTRY.id
JOIN SKY_NS_ENTRY_PENDENT ON SKY_NS_ENTRY_PENDENT.entry = SKY_NS_ENTRY.id
WHERE SKY_NS_ENTRY_TAG.name IN ($tags) AND user = $user") as $record) {
            $id = $record["id"];

            $entries[$id]["domain"] = $record["domain"];
            $entries[$id]["message"] = $record["message"];
            $entries[$id]["updated"] = $record["updated"];
            $entries[$id]["tags"][] = $record["name"];
        }

        if($entries) {
            foreach($entries as $id => &$entry) {
                $entry = new PendentEntry(
                    $id,
                    $this->getDomain( $entry["domain"] ),
                    $entry["message"],
                    new DateTime( $entry["updated"] ),
                    $entry["tags"],
                    $user,
                    $options
                );
            }
        }

        return $entries;
    }

    protected function fetchRegistrations(Domain $domain): ?array
    {
        $registrations = NULL;
        foreach($this->getPDO()->select("SELECT DISTINCT 
id, options, delivery
FROM SKY_NS_USER
JOIN SKY_NS_USER_DOMAIN ON user = id
WHERE domain = ?", [$domain->getID()]) as $record) {
            $registrations[] = new Registration(
                $record["id"],
                $this->getDeliveryInstance( $record["delivery"] ),
                $record["options"]
            );
        }
        return $registrations;
    }

    protected function scheduleNotification(Domain $domain, string $message, DateTime $updated, ?array $tags): int
    {
        $this->getPDO()->inject("INSERT INTO SKY_NS_ENTRY (domain, updated, message) VALUES (?, ?, ?)")->send([
            $domain->getID(),
            $updated->format("Y-m-d G:i:s"),
            $message
        ]);

        $id = $this->getPDO()->lastInsertId("SKY_NS_ENTRY");

        if($tags) {
            $tags = implode(",", array_map(function($t) use ($id) {
                return "($id, " . $this->getPDO()->quote($t) . ")";
            }, $tags));

            $this->getPDO()->exec("INSERT INTO SKY_NS_ENTRY_TAG (entry, name) VALUES $tags");
        }
        return $id;
    }

    protected function schedulePendentNotification(int $notificationID, int $user, DateTime $scheduleDate, int $deliveryOptions)
    {
        $this->getPDO()->inject("INSERT INTO SKY_NS_ENTRY_PENDENT (entry, user, options, scheduled) VALUES ($notificationID, $user, $deliveryOptions, ?)")->send([
            $scheduleDate->format("Y-m-d G:i:s")
        ]);
        return true;
    }

    protected function fetchPendentEntries(&$options): ?array
    {
        $entries = NULL;

        $now = (new DateTime())->format("Y-m-d G:i:s");

        foreach($this->getPDO()->select("SELECT
SKY_NS_ENTRY_PENDENT.id,
       domain,
       message,
       updated,
       name,
       user,
       SKY_NS_USER.options AS userOptions,
       SKY_NS_ENTRY_PENDENT.options AS deliveryOptions
FROM SKY_NS_ENTRY_PENDENT
JOIN SKY_NS_ENTRY ON SKY_NS_ENTRY_PENDENT.entry = SKY_NS_ENTRY.id
LEFT JOIN SKY_NS_ENTRY_TAG ON SKY_NS_ENTRY_TAG.entry = SKY_NS_ENTRY.id
JOIN SKY_NS_USER ON user = SKY_NS_USER.id
WHERE completed IS NULL AND '$now' >= scheduled") as $record) {
            $id = $record["id"];

            $entries[$id]["domain"] = $record["domain"];
            $entries[$id]["message"] = $record["message"];
            $entries[$id]["updated"] = $record["updated"];
            $entries[$id]["tags"][] = $record["name"];
            $entries[$id]["user"] = $record["user"];
            $entries[$id]["uopt"] = $record["userOptions"];
            $options[$id] = $record["deliveryOptions"];
        }

        if($entries) {
            foreach($entries as $id => &$entry) {
                $entry = new PendentEntry(
                    $id,
                    $this->getDomain( $entry["domain"] ),
                    $entry["message"],
                    new DateTime( $entry["updated"] ),
                    $entry["tags"],
                    $entry["user"],
                    $entry["uopt"]
                );
            }
        }

        return $entries;
    }

    protected function completeNotification(Notification $notification)
    {
        $this->getPDO()->inject("UPDATE SKY_NS_ENTRY_PENDENT SET completed = ? WHERE id = ?")->send([
            (new DateTime())->format("Y-m-d G:i:s"),
            $notification->getID()
        ]);
    }
}