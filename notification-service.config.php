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

use Skyline\Kernel\Config\MainKernelConfig;
use Skyline\Notification\Service\AbstractNotificationService;
use Skyline\Notification\Service\Container;
use Skyline\Notification\Service\MySQLNotificationService;
use TASoft\Service\Config\AbstractFileConfiguration;

return [
    MainKernelConfig::CONFIG_SERVICES => [
        AbstractNotificationService::SERVICE_NAME => [
            AbstractFileConfiguration::SERVICE_CONTAINER => Container::class,
            AbstractFileConfiguration::SERVICE_INIT_ARGUMENTS => [
                'pdo' => '$PDO',
                'deliveryInstances' => [],
				"table-map" => [
					'SKY_NS_DOMAIN' => "SKY_NS_DOMAIN",
					"SKY_NS_ENTRY" => "SKY_NS_ENTRY",
					"SKY_NS_ENTRY_PENDENT" => "SKY_NS_ENTRY_PENDENT",
					"SKY_NS_ENTRY_TAG" => "SKY_NS_ENTRY_TAG",
					"SKY_NS_USER" => "SKY_NS_USER",
					"SKY_NS_USER_DOMAIN" => "SKY_NS_USER_DOMAIN"
				],
				"resolver" => Container::RESOLVER_DISABLED
            ],
			AbstractFileConfiguration::CONFIG_SERVICE_TYPE_KEY => MySQLNotificationService::class
        ]
    ]
];