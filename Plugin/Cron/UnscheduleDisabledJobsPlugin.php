<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Plugin\Cron;

use Magento\Cron\Model\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Strips the cron expression from jobs whose "enabled" config flag is off, so Magento
 * neither schedules them nor keeps their pending rows. Without this a disabled job with
 * a default schedule in config.xml is still scheduled and runs as a no-op.
 *
 * Jobs are registered via di.xml: job code => enabled flag path (or a list of paths,
 * all of which must be set for the job to be scheduled).
 */
class UnscheduleDisabledJobsPlugin
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param array $enabledFlagPaths
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly array $enabledFlagPaths = []
    ) {
    }

    /**
     * @param ConfigInterface $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetJobs(ConfigInterface $subject, array $result): array
    {
        foreach ($result as $groupId => $jobs) {
            foreach (array_intersect_key($this->enabledFlagPaths, $jobs) as $jobCode => $paths) {
                if (!$this->isEnabled((array) $paths)) {
                    unset($result[$groupId][$jobCode]['config_path'], $result[$groupId][$jobCode]['schedule']);
                }
            }
        }

        return $result;
    }

    /**
     * @param string[] $paths
     * @return bool
     */
    private function isEnabled(array $paths): bool
    {
        foreach ($paths as $path) {
            if (!$this->scopeConfig->isSetFlag($path)) {
                return false;
            }
        }

        return true;
    }
}
