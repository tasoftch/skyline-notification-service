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
use Skyline\Notification\Fetch\PendentEntry;

class MySQLNotificationService extends AbstractDefaultNotificationService
{
    protected function clearUnusedEntries()
    {
        $this->getPDO()->exec(/** @lang MySQL */ "DELETE
    SKY_NS_ENTRY,
    SKY_NS_ENTRY_TAG
FROM SKY_NS_ENTRY
    LEFT JOIN SKY_NS_ENTRY_TAG ON id = entry
    LEFT JOIN SKY_NS_ENTRY_PENDENT ON SKY_NS_ENTRY_PENDENT.entry = SKY_NS_ENTRY.id
    WHERE user IS NULL");
    }

    protected function fetchPendentEntries(&$options): ?array
    {
        $entries = NULL;

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
WHERE completed IS NULL AND NOW() >= scheduled") as $record) {
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
}