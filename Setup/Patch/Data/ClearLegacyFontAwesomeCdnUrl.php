<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Clear a stored FontAwesome host URL that equals the former hard-coded CDN
 * default (use.fontawesome.com). The module now bundles Font Awesome Free and
 * serves it from the 'self' origin, which works under any Content-Security-
 * Policy; an admin who explicitly saved the old default into config would
 * otherwise be pinned to the external CDN forever. Genuinely custom URLs
 * (Pro kits, merchant CDNs) are left untouched.
 */
class ClearLegacyFontAwesomeCdnUrl implements DataPatchInterface
{
    private const XML_PATH_SOURCE = 'byte8_core/ui/fontawesome_resource';

    private const LEGACY_DEFAULT_SOURCE = 'https://use.fontawesome.com/releases/v6.1.1/css/all.css';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $connection = $this->moduleDataSetup->getConnection();

        $connection->delete(
            $this->moduleDataSetup->getTable('core_config_data'),
            [
                'path = ?' => self::XML_PATH_SOURCE,
                'value = ?' => self::LEGACY_DEFAULT_SOURCE
            ]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
