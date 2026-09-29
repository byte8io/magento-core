<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model;

use function str_replace;
use function ucwords;
use function usort;

/**
 * @inheritDoc
 */
class ModuleCatalog implements ModuleCatalogInterface
{
    /**
     * @param ModuleListProviderInterface $moduleListProvider
     * @param array $products Single source of truth for every Byte8 product, keyed by slug:
     *        [package_name?, display_name, tagline, url?, docs_url?,
     *         category(suite|product|addon|custom|hidden)]
     *        - suite    : the metapackage (rendered as the hero)
     *        - product  : sellable — Tier 2 when installed, Tier 3 upsell when not
     *        - addon    : companion utility — Tier 2 when installed, never upsold
     *        - custom   : bespoke build — shown neutrally when installed, never upsold
     *        - hidden   : dev / infrastructure — never shown
     */
    public function __construct(
        private readonly ModuleListProviderInterface $moduleListProvider,
        private readonly array $products = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getGrouped(): array
    {
        $byPackage = [];
        foreach ($this->products as $product) {
            if (!empty($product['package_name'])) {
                $byPackage[$product['package_name']] = $product;
            }
        }

        $installed = $this->normalize($this->moduleListProvider->getList() ?: []);
        $suiteRaw = $this->moduleListProvider->getSuite();
        $suiteRequire = array_flip($this->moduleListProvider->getSuiteRequire());

        $members = [];
        $addons = [];
        $custom = [];

        foreach ($installed as $packageName => $data) {
            $version = $data['package_version'] ?? '';

            // Skip unreleased / dev-only modules that carry no version.
            if ($version === '' || $version === 'n/a') {
                continue;
            }

            if (isset($suiteRequire[$packageName])) {
                $members[] = [
                    'short' => $this->shortName($packageName),
                    'package_name' => $packageName,
                    'version' => $version,
                ];
                continue;
            }

            $product = $byPackage[$packageName] ?? [];
            $category = $product['category'] ?? 'addon';

            if ($category === 'hidden' || $category === 'suite') {
                continue;
            }

            $entry = [
                'display_name' => $product['display_name'] ?? $this->humanize($packageName),
                'package_name' => $packageName,
                'version' => $version,
                'tagline' => $product['tagline'] ?? ($data['package_description'] ?? ''),
                'status' => 'installed',
            ];

            if ($category === 'custom') {
                $custom[] = $entry;
                continue;
            }

            // product / addon: installed → Tier 2. Link to docs (utility) if present, else product page.
            if (!empty($product['docs_url'])) {
                $entry['link'] = $product['docs_url'];
                $entry['link_label'] = 'Docs';
            } elseif (!empty($product['url'])) {
                $entry['link'] = $product['url'];
                $entry['link_label'] = 'Details';
            } else {
                $entry['link'] = '';
                $entry['link_label'] = '';
            }
            $addons[] = $entry;
        }

        // Tier 3: sellable products (category "product") that are not installed.
        $available = [];
        foreach ($this->products as $product) {
            if (($product['category'] ?? '') !== 'product') {
                continue;
            }
            $packageName = $product['package_name'] ?? '';
            if ($packageName !== ''
                && isset($installed[$packageName])
                && !empty($installed[$packageName]['package_version'])
            ) {
                continue;
            }
            if (empty($product['url'])) {
                continue;
            }
            $available[] = [
                'display_name' => $product['display_name'] ?? '',
                'tagline' => $product['tagline'] ?? '',
                'url' => $product['url'],
            ];
        }

        $suite = null;
        if ($suiteRaw) {
            $suiteProduct = $byPackage[$suiteRaw['package_name']] ?? [];
            $suite = [
                'display_name' => $suiteProduct['display_name'] ?? $this->humanize($suiteRaw['package_name']),
                'package_name' => $suiteRaw['package_name'],
                'version' => $suiteRaw['package_version'] ?? '',
                'status' => 'installed',
            ];
        }

        $this->sortByKey($members, 'short');
        $this->sortByKey($addons, 'display_name');
        $this->sortByKey($custom, 'display_name');

        return [
            'suite' => $suite,
            'members' => $members,
            'addons' => $addons,
            'custom' => $custom,
            'available' => $available,
        ];
    }

    /**
     * Re-key installed packages by their composer package name and dedupe.
     *
     * getList() mixes entries keyed by Magento module name (from the component
     * registrar) with entries keyed by composer package name (from
     * composer.lock), so the same module can appear twice. Collapse both onto
     * the composer package name and keep the richest values.
     *
     * @param array $rawList
     * @return array<string, array>
     */
    private function normalize(array $rawList): array
    {
        $byPackage = [];
        foreach ($rawList as $key => $data) {
            $packageName = $data['package_name'] ?? '';
            if ($packageName === '') {
                $packageName = (string) $key;
            }

            if (!isset($byPackage[$packageName])) {
                $byPackage[$packageName] = [
                    'package_name' => $packageName,
                    'package_version' => $data['package_version'] ?? '',
                    'package_description' => $data['package_description'] ?? '',
                ];
                continue;
            }

            $current = $byPackage[$packageName];
            if (($current['package_version'] === '' || $current['package_version'] === 'n/a')
                && !empty($data['package_version'])
            ) {
                $byPackage[$packageName]['package_version'] = $data['package_version'];
            }
            if ($current['package_description'] === '' && !empty($data['package_description'])) {
                $byPackage[$packageName]['package_description'] = $data['package_description'];
            }
        }

        return $byPackage;
    }

    /**
     * @param array $rows
     * @param string $key
     * @return void
     */
    private function sortByKey(array &$rows, string $key): void
    {
        usort($rows, static fn ($a, $b) => strcmp((string) ($a[$key] ?? ''), (string) ($b[$key] ?? '')));
    }

    /**
     * byte8/module-plenty-item => plenty-item
     *
     * @param string $packageName
     * @return string
     */
    private function shortName(string $packageName): string
    {
        return str_replace(['byte8/module-'], '', $packageName);
    }

    /**
     * byte8/module-plenty-stock-radar => Plenty Stock Radar
     *
     * @param string $packageName
     * @return string
     */
    private function humanize(string $packageName): string
    {
        return ucwords(str_replace('-', ' ', $this->shortName($packageName)));
    }
}
