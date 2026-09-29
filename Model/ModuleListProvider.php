<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * @inheritDoc
 */
class ModuleListProvider implements ModuleListProviderInterface
{
    /**
     * @var array|null
     */
    private ?array $data = null;

    /**
     * @var array|null
     */
    private ?array $suite = null;

    /**
     * @var string[]
     */
    private array $suiteRequire = [];

    /**
     * @param ComponentRegistrar $componentRegistrar
     * @param DirectoryList $directoryList
     * @param ReadFactory $readDirFactory
     * @param Reader $reader
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar,
        private readonly DirectoryList $directoryList,
        private readonly ReadFactory $readDirFactory,
        private readonly Reader $reader,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getList(?string $moduleName = null, ?string $metadata = null)
    {
        if (null === $this->data) {
            $this->initData();
        }

        if (null === $moduleName) {
            return $this->data;
        }

        return null !== $metadata
            ? ($this->data[$moduleName][$metadata] ?? null)
            : ($this->data[$moduleName] ?? null);
    }

    /**
     * @inheritDoc
     */
    public function getSuite(): ?array
    {
        if (null === $this->data) {
            $this->initData();
        }

        return $this->suite;
    }

    /**
     * @inheritDoc
     */
    public function getSuiteRequire(): array
    {
        if (null === $this->data) {
            $this->initData();
        }

        return $this->suiteRequire;
    }

    /**
     * @return void
     * @throws FileSystemException
     * @throws ValidatorException
     */
    private function initData(): void
    {
        $this->initMetapackageData();

        $jsonData = $this->reader->getComposerJsonFiles()->toArray();
        foreach ($this->componentRegistrar->getPaths(ComponentRegistrar::MODULE) as $moduleName => $moduleDir) {
            $index = "$moduleDir/composer.json";
            if (!$this->isVendorPackage($moduleName) || !$packageData = $jsonData[$index] ?? []) {
                continue;
            }

            try {
                $packageData = $this->serializer->unserialize($packageData);
            } catch (\InvalidArgumentException $e) {
                continue;
            }

            $this->data[$moduleName] = [
                'name' => $moduleName,
                'package_name' => $packageData['name'] ?? '',
                'package_description' => $packageData['description'] ?? '',
                'package_version' => $packageData['version'] ?? '',
            ];
        }
    }

    /**
     * @return void
     * @throws FileSystemException
     * @throws ValidatorException
     */
    private function initMetapackageData(): void
    {
        $rootDirectory = $this->directoryList->getPath(DirectoryList::ROOT);
        $readDirectory = $this->readDirFactory->create($rootDirectory);

        if (!$readDirectory->isExist('composer.lock')
            || !$composerJsonFile = $readDirectory->readFile('composer.lock')
        ) {
            return;
        }

        try {
            $rawData = $this->serializer->unserialize($composerJsonFile);
        } catch (\InvalidArgumentException) {
            $rawData = [];
        }

        foreach ($rawData['packages'] ?? [] as $package) {
            $packageName = $package['name'] ?? '';
            if (!$this->isVendorPackage($packageName)) {
                continue;
            }

            // Capture the suite metapackage and the modules it requires so the
            // admin can distinguish "part of your suite" from standalone add-ons.
            if (($package['type'] ?? '') === 'metapackage' && null === $this->suite) {
                $this->suite = [
                    'name' => $packageName,
                    'package_name' => $packageName,
                    'package_version' => $package['version'] ?? 'n/a',
                ];
                foreach (array_keys($package['require'] ?? []) as $requiredName) {
                    if ($this->isVendorPackage($requiredName)) {
                        $this->suiteRequire[] = $requiredName;
                    }
                }
                continue;
            }

            $this->data[$packageName] = [
                'name' => $packageName,
                'package_name' => $packageName,
                'package_description' => '',
                'package_version' => $package['version'] ?? 'n/a',
            ];
        }
    }

    /**
     * @param string $packageName
     * @return bool
     */
    private function isVendorPackage(string $packageName): bool
    {
        return str_starts_with(strtolower($packageName), 'byte8_')
            || str_starts_with(strtolower($packageName), 'byte8/');
    }
}
