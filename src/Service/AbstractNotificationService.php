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
use Skyline\Notification\ConflictSolver\ConflictSolverInterface;
use Skyline\Notification\Delivery\DeliveryGroupedInterface;
use Skyline\Notification\Delivery\DeliveryInterface;
use Skyline\Notification\Delivery\DeliveryResolvedInterface;
use Skyline\Notification\Delivery\DeliveryScheduledInterface;
use Skyline\Notification\Domain\Domain;
use Skyline\Notification\Exception\DeliveryInstanceNotFoundException;
use Skyline\Notification\Exception\DomainNotFoundException;
use Skyline\Notification\Exception\DuplicateRegistrationException;
use Skyline\Notification\Fetch\Notification;
use Skyline\Notification\Fetch\PendentEntryInterface;
use Skyline\Notification\Fetch\RegistrationInterface;
use Skyline\Notification\PDO\PDOMap;
use TASoft\Util\PDO;
use Throwable;

abstract class AbstractNotificationService implements DefaultConflictResolverInterface
{
    const SERVICE_NAME = 'notificationService';

    /** @var PDO */
    private $PDO;
    /** @var DeliveryInterface[] */
    private $deliveryInstances = [];
	private $groupedDeliveryInstances = [];

    /** @var Domain[] */
    private $domains = [];
    /** @var ConflictSolverInterface|null */
    private $resolver;

	/**
	 * NotificationService constructor.
	 * @param PDO $PDO
	 * @param array $deliveryInstances
	 * @param array $tableMap
	 */
    public function __construct(PDO $PDO, array $deliveryInstances = [], $tableMap = [])
    {
        $this->PDO = new PDOMap( $PDO , $tableMap);
        foreach ($deliveryInstances as $instance) {
            if($instance instanceof DeliveryInterface) {
                $this->addDeliveryInstance($instance);
            }
        }
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDOMap
    {
        return $this->PDO;
    }

    /**
     * Adds delivery instance to the service
     *
     * @param DeliveryInterface $delivery
     */
    public function addDeliveryInstance(DeliveryInterface $delivery) {
        $this->deliveryInstances[ $delivery->getName() ] = $delivery;

		if($delivery instanceof DeliveryGroupedInterface)
			$this->groupedDeliveryInstances[ $delivery->getName() ] = $delivery;
    }

    /**
     * Removes a delivery instance from service
     *
     * @param $instance
     */
    public function removeDeliveryInstance($instance) {
        if($instance instanceof DeliveryInterface)
            $instance = $instance->getName();

        unset($this->deliveryInstances[$instance]);
		unset($this->groupedDeliveryInstances[$instance]);
    }

    /**
     * @inheritDoc
     */
    public function getDeliveryInstance($name): ?DeliveryInterface
    {
        return $this->deliveryInstances[$name] ?? NULL;
    }

    /**
     * @inheritDoc
     */
    public function getDomain($nameOrID): ?Domain
    {
        if(($domain = $this->domains[$nameOrID] ?? NULL) === NULL) {
            if($domain = $this->makeDomain($nameOrID)) {
                $this->domains[ $domain->getName() ] = $this->domains[ $domain->getID() ] = $domain;
            } else
                $this->domains[$nameOrID] = false;
        }
        return $domain ?: NULL;
    }

    /**
     * Called once for each required domain to be fetched from PDO and created
     *
     * @param $nameOrID
     * @return Domain|null
     */
    abstract protected function makeDomain($nameOrID): ?Domain;

    /**
     * Clears the manager's cache to fetch again from storage.
     */
    public function reset() {
        $this->domains = [];
        $this->entryCache = [];
        $this->deliverableEntryCache = [];
    }



    /**
     * Registers a user to be notified if a notification on one of the passed domains gets posted.
     *
     * @param int $user
     * @param array $domains
     * @param DeliveryInterface|string $delivery
     * @param int $options
     * @return bool
     * @throws DomainNotFoundException
     * @throws DeliveryInstanceNotFoundException
     * @throws DuplicateRegistrationException
     * @throws Throwable
     */
    public function register(int $user, array $domains, $delivery, int $options = 0): bool
    {
        if(is_string($delivery))
            $delivery = $this->getDeliveryInstance( $delivery );

        if($delivery instanceof DeliveryInterface) {
            $PDO = $this->getPDO();
            $PDO->transaction(function() use ($user, $domains, $delivery, $options, $PDO) {
                $this->makeUserRegistration($user, $delivery, $options);
                $this->_updateUserRegistration($user, $domains, NULL, NULL);
            });
            return true;
        } else {
            $e = new DeliveryInstanceNotFoundException("Invalid delivery specified");
            $e->setSymbol($delivery);
            throw $e;
        }
    }

    /**
     * Creates a table entry on SKY_NS_USER.
     * Please note that the column user must be unique.
     * If the user is created, return true, otherwise false.
     * Returning false is interpreted as duplicate user registration and will reject the registration.
     * Please note that this method is called during a PDO transaction.
     *
     * @param int $user
     * @param DeliveryInterface $delivery
     * @param int $options
     * @throws DuplicateRegistrationException
     */
    abstract protected function makeUserRegistration(int $user, DeliveryInterface $delivery, int $options);

    /**
     * @param int $user
     * @param array|null $domains
     * @param $delivery
     * @param int|null $options
     * @internal
     */
    private function _updateUserRegistration(int $user, ?array $domains, $delivery, ?int $options)
    {
        if($delivery) {
            if(is_string($delivery))
                $delivery = $this->getDeliveryInstance($delivery);
            if(!($delivery instanceof DeliveryInterface)) {
                $e = new DeliveryInstanceNotFoundException("Invalid delivery specified");
                $e->setSymbol($delivery);
                throw $e;
            }
        }

        if($domains) {
            $domains = array_map(function($k) {
                if(!($k instanceof Domain))
                    $k = $this->getDomain( $k );

                if($k instanceof Domain) {
                    return $k;
                } else {
                    $e = new DomainNotFoundException("Invalid domain specified");
                    $e->setSymbol($k);
                    throw $e;
                }
            }, $domains);
        }

        $this->updateUserRegistration($user, $domains, $delivery, $options);
    }

    /**
     * Called to update the registration for user $user.
     *
     * Receiving NULL for domains, delivery or options means to not change the property.
     *
     * @param int $user
     * @param Domain[]|null $domains
     * @param DeliveryInterface|null $delivery
     * @param int|null $options
     */
    abstract protected function updateUserRegistration(int $user, ?array $domains, ?DeliveryInterface $delivery, ?int $options);

    /**
     * This method must delete all affected rows from
     *  SKY_NS_USER
     *  SKY_NS_USER_DOMAIN
     *  SKY_NS_ENTRY_PENDENT
     *
     * @param int $user
     */
    abstract protected function clearRegistration(int $user);

    /**
     * @return ConflictSolverInterface|null
     */
    public function getResolver(): ?ConflictSolverInterface
    {
        return $this->resolver;
    }

    /**
     * @param ConflictSolverInterface|null $resolver
     */
    public function setResolver(?ConflictSolverInterface $resolver): void
    {
        $this->resolver = $resolver;
    }

    /**
     * This method must delete all affected rows from
     *  SKY_NS_ENTRY
     *  SKY_NS_ENTRY_TAG
     *  where no pendent entry (SKY_NS_ENTRY_PENDENT) refers to SKY_NS_ENTRY.
     */
    abstract protected function clearUnusedEntries();

    /**
     * This method must delete all affected rows from
     *  SKY_NS_ENTRY
     *  SKY_NS_ENTRY_TAG
     *  SKY_NS_ENTRY_PENDENT
     *
     * If $user is NULL clear all, if is a number, only clear the entries from the passed user, but leave for others.
     * If $completedOnly is true, delete only completed but leave scheduled, otherwise also scheduled
     *
     * @param int|null $user
     * @param bool $completedOnly
     */
    abstract protected function clearEntries(?int $user, bool $completedOnly);

    /**
     * @inheritDoc
     */
    public function unregister(int $user)
    {
        $this->clearRegistration($user);
        $this->reset();
    }

    /**
     * @inheritDoc
     */
    public function modify(int $user, array $domains = NULL, $delivery = NULL, int $options = NULL)
    {
        $this->_updateUserRegistration($user, $domains, $delivery, $options);
    }

    /**
     * Fetches all registered users for given domain.
     *
     * @param Domain $domain
     * @return RegistrationInterface|null
     */
    abstract protected function fetchRegistrations(Domain $domain): ?array;

    /**
     * @param int $user
     * @param array $tags
     * @param int|null $userOptions
     * @return PendentEntryInterface[]|null
     */
    abstract protected function fetchConflictingEntries(int $user, array $tags, ?int $userOptions): ?array;

    /**
     * This method gets called to insert an entry into the table.
     * The return value must be the id of that entry, so further pendent notifications may refer to it.
     *
     * @param Domain $domain
     * @param string $message
     * @param DateTime $updated
     * @param array|null $tags
     * @return int
     */
    abstract protected function scheduleNotification(Domain $domain, string $message, DateTime $updated, ?array $tags): int;

    /**
     * This method gets called for each user who's delivery require to reschedule the notification delivery.
     *
     * Returning true assumes everything went well, otherwise the default implementation of the delivery instance gets resumed.
     *
     * @param int $notificationID
     * @param int $user
     * @param DateTime $scheduleDate
     * @param int $deliveryOptions
     * @return bool
     */
    abstract protected function schedulePendentNotification(int $notificationID, int $user, DateTime $scheduleDate, int $deliveryOptions);

    /**
     * This method gets called after conflict resolving happens.
     * By default, should removes all remaining entries.
     *
     * @param PendentEntryInterface $selected
     * @param array $remaining
     */
    abstract protected function handleResolvedNotificationConflicts(PendentEntryInterface $selected, array $remaining);

    /**
     * @inheritDoc
     */
    public function postNotification(string $message, $domain, array $tags = [])
    {
        if(!($domain instanceof Domain))
            $domain = $this->getDomain($domain);
        if($domain instanceof Domain) {
            if($registrations = $this->fetchRegistrations($domain)) {
                /** @var RegistrationInterface $registration */

				if($this->groupedDeliveryInstances)
					array_walk($this->groupedDeliveryInstances, function (DeliveryGroupedInterface $grouped) {
						$grouped->deliveryBegin();
					});

                $deliveryCount = 0;
                $notificationID = NULL;

                foreach($registrations as $registration) {
                    $notification = new Notification(
                        $registration->getID(),
                        $domain,
                        $message,
                        $tags,
                        $registration->getOptions()
                    );

                    $delivery = $registration->getDelivery();
					$schedule = true;

                    if($tags && ($delivery instanceof DeliveryResolvedInterface || $this instanceof DefaultConflictResolverInterface)) {
                        $resolver = $delivery instanceof DeliveryResolvedInterface ? $delivery->getSolver() : $this->getResolver();

                        if($resolver) {
                            if($conflicts = $this->fetchConflictingEntries( $registration->getID(), $tags , $registration->getOptions())) {
                                if($conflicts) {
                                    $conflicts[] = $notification;
                                    $notification = $resolver->getSolvedNotificationEntry( $conflicts );
                                    if(($idx = array_search($notification, $conflicts)) !== false)
                                        unset($conflicts[$idx]);
                                    $this->handleResolvedNotificationConflicts($notification, $conflicts);
									$schedule = false;
                                }
                            }
                        }
                    }

                    if(!($notification instanceof Notification)) {
                        $notification = new Notification(
                            $notification->getUser(),
                            $notification->getDomain(),
                            $notification->getMessage(),
                            $notification->getTags(),
                            $notification->getUserOptions(),
                            $notification->getID(),
                            $notification->getUpdated()
                        );
                    }

                    if($delivery->canDeliverNotification($notification)) {
                        $deliveryCount++;
                        if($delivery instanceof DeliveryScheduledInterface) {
                            $options = 0;
                            $date = $delivery->getDeliveryDate($notification, $options);
                            if($date) {
                                $nid = $notification->getID();
                                if($nid<1) {
                                    // Notification does not exist yet
                                    if(NULL === $notificationID)
                                        $notificationID = $this->scheduleNotification(
                                            $notification->getDomain(),
                                            $message,
                                            $notification->getUpdated(),
                                            $notification->getTags()
                                        );
                                    $nid = $notificationID;
									$schedule = true;
                                }

								if($schedule) {
									if($this->schedulePendentNotification(
										$nid,
										$registration->getID(),
										$date,
										$options
									))
										continue;
									trigger_error("Could not schedule notification", E_USER_NOTICE);
								}
                            }
                        } elseif(!$delivery->deliverNotification($notification)) {
                            trigger_error(sprintf("Could not deliver notification for user %d on domain %s using %s", $registration->getID(), $domain->getName(), $delivery->getName()), E_USER_WARNING);
                        }
                    } else
                        trigger_error(sprintf("Delivery instance %s does not accept notification on domain %s", $delivery->getName(), $domain->getName()), E_USER_WARNING);
                }

				if($this->groupedDeliveryInstances)
					array_walk($this->groupedDeliveryInstances, function (DeliveryGroupedInterface $grouped) {
						$grouped->deliveryEnd();
					});

                return $deliveryCount;
            }
            return 0;
        }
        return false;
    }

    /**
     * Fetches all pendent entries.
     * Put the pendent entries keys for $options array and the delivery options as value.
     *
     * @param array $options
     * @return PendentEntryInterface[]|null
     */
    abstract protected function fetchPendentEntries(&$options): ?array;

    /**
     * Called after a pendent notification was delivered successfully.
     *
     * @param Notification $notification
     */
    abstract protected function completeNotification(Notification $notification);

    /**
     * @inheritDoc
     */
    public function deliverPendentNotifications()
    {
        $options = [];
        if($entries = $this->fetchPendentEntries($options)) {

			if($this->groupedDeliveryInstances)
				array_walk($this->groupedDeliveryInstances, function (DeliveryGroupedInterface $grouped) {
					$grouped->deliveryBegin();
				});

            /** @var PendentEntryInterface $entry */
            $deliveries = [];
            $registrations = [];

            $getDelivery = function(Notification $notification) use (&$deliveries, &$registrations) {
                $uid = $notification->getUser();
                if(!$deliveries[$uid]) {
                    if(! $registrations[$notification->getDomain()->getID()] ) {
                        $registrations[$notification->getDomain()->getID()] = $this->fetchRegistrations($notification->getDomain());
                    }

                    /** @var RegistrationInterface $registration */
                    foreach($registrations[$notification->getDomain()->getID()] as $registration) {
                        $deliveries[ $registration->getID() ] = $registration->getDelivery();
                    }
                }

                return $deliveries[$uid] ?? NULL;
            };

            foreach($entries as $entry) {
                if(!($entry instanceof Notification)) {
                    $entry = new Notification(
                        $entry->getUser(),
                        $entry->getDomain(),
                        $entry->getMessage(),
                        $entry->getTags(),
                        $entry->getUserOptions(),
                        $entry->getID(),
                        $entry->getUpdated()
                    );
                }

                $delivery = @$getDelivery($entry);

                if($delivery instanceof DeliveryScheduledInterface) {
                    if($delivery->deliverScheduledNotification($entry, $options[ $entry->getID() ] ?? 0))
                       $this->completeNotification($entry);
                    else
                        trigger_error(sprintf("Could not deliver scheduled notification #%d", $entry->getID()), E_USER_WARNING);
                } else
                    trigger_error(sprintf("Delivery can not be found for pendent entry #%d", $entry->getID()), E_USER_WARNING);
            }

			if($this->groupedDeliveryInstances)
				array_walk($this->groupedDeliveryInstances, function (DeliveryGroupedInterface $grouped) {
					$grouped->deliveryEnd();
				});
        }
    }
}