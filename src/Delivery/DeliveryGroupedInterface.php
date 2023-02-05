<?php

namespace Skyline\Notification\Delivery;

interface DeliveryGroupedInterface
{
	/**
	 * Called before a delivery process of notifications will start.
	 */
	public function deliveryBegin();

	/**
	 * Called, after all notifications have been sent to their delivery instances.
	 */
	public function deliveryEnd();
}