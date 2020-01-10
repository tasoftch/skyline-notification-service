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

namespace Skyline\Notification\Element;


use TASoft\Util\PDOResourceInterface;

/**
 * The affected element class can be used to add recognize stuff to an entry.
 * Each entry may have one or more affected elements.
 * All elements must have unique names for the same entry.
 * So you can check, if an affected element is already set on an entry.
 *
 * Example:
 * Your app sells softwares. A new release gets published and all clients should be informed.
 * The entry has the information, what did happen.
 * User A adjusted the notification service to get informed immediately, so the app sends a mail to him.
 * User B want to be informed on Thursday and Monday at 4:00pm.
 * In the User B's case, the notification service schedules the information at the next desired date to be delivered.
 * But the next day, you publish a new release again. So the old one is no longer relevant for User B (Don't send two mails with redundant information)
 * Now an affected element holds the software name, so you are able to find all entries affecting the software
 *
 * @package Skyline\Notification\Element
 */
interface AffectedElementInterface extends PDOResourceInterface
{
    /**
     * Gets the identification name of the element.
     * Must be unique for all elements of the same entry
     *
     * @return string
     */
    public function getName(): string;

    /**
     * A description for the element
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Custom options
     *
     * @return int
     */
    public function getOptions(): int;
}