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


use Generator;
use Skyline\Notification\Deliver\DeliverInfo;
use Skyline\Notification\Deliver\DeliverInterface;
use Skyline\Notification\Deliver\DeliverScheduledInterface;
use Skyline\Notification\Element\AffectedElementInterface;
use Skyline\Notification\Exception\DuplicateRegistrationException;
use Skyline\Notification\Exception\MissingDeliveryException;
use Skyline\Notification\Exception\MissingKindException;
use Skyline\Notification\Kind\NotificationKind;
use TASoft\Service\AbstractService;
use TASoft\Util\PDO;

class NotificationService extends AbstractService implements NotificationServiceInterface
{
    const SERVICE_NAME = 'notificationService';

    /** @var PDO */
    private $PDO;

    private $deliveryInstances = [];

    private $kinds = [];

    /**
     * NotificationService constructor.
     * @param PDO $PDO
     */
    public function __construct(PDO $PDO, array $deliveryInstances = [])
    {
        $this->PDO = $PDO;
        foreach ($deliveryInstances as $instance) {
            if($instance instanceof DeliverInterface) {
                $this->deliveryInstances[ $instance->getName() ] = $instance;
            }
        }
    }

    /**
     * Clears the manager's cache to fetch again from storage.
     */
    public function reset() {
        $this->kinds = [];
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->PDO;
    }

    public function getKind($nameOrID): ?NotificationKind
    {
        if(($kind = $this->kinds[$nameOrID] ?? NULL) === NULL) {
            $record = $this->getPDO()->selectOne("SELECT * FROM SKY_NS_KIND WHERE id = ? OR name = ?", [$nameOrID, $nameOrID]);
            if($record)
                $this->kinds[ $record["name"] ] = $this->kinds[ $record["id"] * 1 ] = $kind = new NotificationKind($record);
            else
                $this->kinds[ $record["name"] ] = $this->kinds[ $record["id"] * 1 ] = false;
        }
        return $kind;
    }

    public function getDeliveryInstance($name): ?DeliverInterface
    {
        return $this->deliveryInstances[$name] ?? NULL;
    }

    public function register(int $user, array $kinds, $deliver, int $options = 0): bool
    {
        if(is_string($deliver))
            $deliver = $this->getDeliveryInstance( $deliver );

        if($deliver instanceof DeliverInterface) {
            foreach($kinds as &$kind) {
                $k = $kind;

                if(!($k instanceof NotificationKind))
                    $k = $this->getKind( $kind );

                if($k instanceof NotificationKind) {
                    $kid = $k->getID();
                    $kind = $this->getPDO()->inject("INSERT INTO SKY_NS_REGISTER_KIND (kind, user) VALUES ($kid, ?)");
                } else {
                    $e = new MissingKindException("Invalid kind specified");
                    $e->setKind($kind);
                    throw $e;
                }
            }

            $PDO = $this->getPDO();
            $PDO->transaction(function() use ($user, $kinds, $deliver, $options, $PDO) {
                if($r = $PDO->selectOne("SELECT user, options, delivery FROM SKY_NS_REGISTER WHERE user = ?", [$user])) {
                    $e = new DuplicateRegistrationException("User $user is already registered");
                    $e->setExistingRegistration($r);
                    throw $e;
                }

                $PDO->inject("INSERT INTO SKY_NS_REGISTER (user, options, delivery) VALUES ($user, $options, ?)")->send([
                    $deliver->getName()
                ]);

                array_walk($kinds, function(Generator $k) use ($user) {
                    $k->send([$user]);
                });
            });

            return true;
        } else {
            $e = new MissingDeliveryException("Invalid delivery specified");
            $e->setDelivery($deliver);
            throw $e;
        }
    }

    public function unregister(int $user, array $kinds = NULL, bool $remainPendent = false): bool
    {
        if($kinds) {
            foreach($kinds as &$kind) {
                $k = $kind;

                if(!($k instanceof NotificationKind))
                    $k = $this->getKind( $kind );

                if($k instanceof NotificationKind) {
                    $kind = $k->getID();
                } else {
                    $e = new MissingKindException("Invalid kind specified");
                    $e->setKind($kind);
                    throw $e;
                }
            }
        }

        $PDO = $this->getPDO();
        return $PDO->transaction(function() use ($user, $kinds, $PDO, $remainPendent) {
            if($kinds) {
                $kinds = "(".implode(",",$kinds).")";
                $PDO->exec("DELETE FROM SKY_NS_REGISTER_KIND WHERE kind IN $kinds");
            } else {
                $PDO->exec("DELETE FROM SKY_NS_REGISTER_KIND WHERE user = $user");
                $PDO->exec("DELETE FROM SKY_NS_REGISTER WHERE user = $user");
                if(!$remainPendent)
                    $PDO->exec("DELETE FROM SKY_NS_PENDENT WHERE user = $user");
            }
        });
    }

    public function postNotification($message, $kind, array $affectedElements = [])
    {
        if($message) {
            if(!($kind instanceof NotificationKind))
                $kind = $this->getKind($kind);

            if($kind instanceof NotificationKind) {
                $theElements = [];

                foreach($affectedElements as &$element) {
                    if($element instanceof AffectedElementInterface) {
                        $theElements[] = $element;

                        $name = $this->getPDO()->quote( $element->getName() );
                        $desc = $this->getPDO()->quote( $element->getDescription() );
                        $options =  $this->getPDO()->quote( $element->getOptions(), PDO::PARAM_INT);

                        $element = $this->getPDO()->inject("INSERT INTO SKY_NS_AFFECTED_ELEMENT (name, description, options, entry) VALUES ($name, $desc, $options, ?)");
                    } else
                        $element = NULL;
                }

                $insertAsPendent = function() use ($message, $kind, $affectedElements) {
                    static $inserted = false;
                    if(false === $inserted) {
                        $PDO = $this->getPDO();
                        $inserted = $PDO->transaction(function() use ($PDO, $message, $kind, $affectedElements) {
                            $PDO->inject("INSERT INTO SKY_NS_ENTRY (kind, message, updated) VALUES (?, ?, ?)")->send([
                                $kind->getID(),
                                $message,
                                (new \DateTime('now'))->format("Y-m-d G:i:s")
                            ]);

                            $eid = $PDO->lastInsertId("SKY_NS_ENTRY");
                            array_walk($affectedElements, function(?Generator $w) use ($eid) {
                                if($w)
                                    $w->send([$eid]);
                            });
                            return $eid;
                        });
                    }
                    return $inserted;
                };

                $users = 0;

                foreach($this->getPDO()->select("SELECT
                user, options, delivery
FROM SKY_NS_REGISTER
JOIN SKY_NS_REGISTER_KIND ON SKY_NS_REGISTER_KIND.user = SKY_NS_REGISTER.user
WHERE kind = ?
", [$kind->getID()]) as $record) {
                    $users++;

                    $delivery = $this->getDeliveryInstance( $record["delivery"] );
                    if($delivery instanceof DeliverInterface) {
                        $info = new DeliverInfo(
                            $message,
                            $kind,
                            $user = $record["user"] * 1,
                            $record["options"] * 1,
                            $theElements
                        );

                        if($delivery->canDeliverNotification($info)) {
                            if($delivery instanceof DeliverScheduledInterface) {
                                $options = 0;

                                if($date = $delivery->getDeliveryDate($info, $options)) {
                                    $eid = $insertAsPendent();
                                    $this->getPDO()->inject("INSERT INTO SKY_NS_PENDENT (entry, user, deliverAt, options) VALUES ($eid, $user, ?, ?)")->send([
                                        $date->format("Y-m-d G:i:s"),
                                        $options
                                    ]);

                                    continue;
                                }
                            }

                            $delivery->deliverNotification($info);
                        }
                    } else
                        trigger_error("No delivery instance found for {$record["delivery"]}", E_USER_WARNING);
                }

                return $users;
            }
        }
        return false;
    }

    public function deliverPendentNotifications()
    {

    }
}