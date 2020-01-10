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

namespace Skyline\Notification\ConflictSolver;


use Skyline\Notification\Element\AffectedElementInterface;
use Skyline\Notification\Kind\NotificationKind;
use TASoft\Util\AbstractRecordPDOResource;

class Entry extends AbstractRecordPDOResource
{
    /** @var NotificationKind */
    private $kind;
    /** @var string */
    private $message;
    /** @var \DateTime */
    private $updated;
    /** @var AffectedElementInterface[] */
    private $affectedElements;

    /**
     * Entry constructor.
     * @param NotificationKind $kind
     * @param string $message
     * @param \DateTime $updated
     * @param AffectedElementInterface[] $affectedElements
     */
    public function __construct(NotificationKind $kind, string $message, \DateTime $updated, array $affectedElements)
    {
        $this->kind = $kind;
        $this->message = $message;
        $this->updated = $updated;
        $this->affectedElements = $affectedElements;
    }


    /**
     * @return NotificationKind
     */
    public function getKind(): NotificationKind
    {
        return $this->kind;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    /**
     * @return AffectedElementInterface[]
     */
    public function getAffectedElements(): array
    {
        return $this->affectedElements;
    }
}