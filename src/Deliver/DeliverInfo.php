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

namespace Skyline\Notification\Deliver;


use Skyline\Notification\Element\AffectedElementInterface;
use Skyline\Notification\Kind\NotificationKind;

class DeliverInfo
{
    /** @var string */
    private $message;

    /** @var NotificationKind */
    private $kind;

    /** @var null|AffectedElementInterface[] */
    private $affectedElements;

    /** @var int */
    private $user;

    /** @var int */
    private $userOptions;

    /**
     * DeliverInfo constructor.
     * @param string $message
     * @param NotificationKind $kind
     * @param int $user
     * @param int $userOptions
     * @param AffectedElementInterface[]|null $affectedElements
     */
    public function __construct(string $message, NotificationKind $kind, int $user, int $userOptions = 0, array $affectedElements = NULL)
    {
        $this->message = $message;
        $this->kind = $kind;
        $this->affectedElements = $affectedElements;
        $this->user = $user;
        $this->userOptions = $userOptions;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return NotificationKind
     */
    public function getKind(): NotificationKind
    {
        return $this->kind;
    }

    /**
     * @return AffectedElementInterface[]|null
     */
    public function getAffectedElements(): ?array
    {
        return $this->affectedElements;
    }

    /**
     * @return int
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @return int
     */
    public function getUserOptions(): int
    {
        return $this->userOptions;
    }
}