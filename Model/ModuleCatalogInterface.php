<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model;

/**
 * Groups the installed Byte8 packages into a view model for the admin
 * "Installed Modules" panel: the suite, its member modules, installed
 * add-ons, custom builds, and available add-ons to discover.
 */
interface ModuleCatalogInterface
{
    /**
     * @return array{
     *     suite: array|null,
     *     members: array<int, array>,
     *     addons: array<int, array>,
     *     custom: array<int, array>,
     *     available: array<int, array>
     * }
     */
    public function getGrouped(): array;
}
