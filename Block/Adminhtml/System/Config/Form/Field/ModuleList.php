<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Byte8\Core\Model\ModuleCatalogInterface;
use Byte8\Core\Model\ModuleListProviderInterface;

/**
 * @inheritDoc
 */
class ModuleList extends Field
{
    protected $_template = 'Byte8_Core::module-list.phtml';

    /**
     * @param ModuleListProviderInterface $moduleListProvider
     * @param ModuleCatalogInterface $moduleCatalog
     * @param Template\Context $context
     * @param array $data
     */
    public function __construct(
        private readonly ModuleListProviderInterface $moduleListProvider,
        private readonly ModuleCatalogInterface $moduleCatalog,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * Raw installed package list (retained for backward compatibility).
     *
     * @return array
     */
    public function getList(): array
    {
        return $this->moduleListProvider->getList();
    }

    /**
     * Grouped view model: suite, members, add-ons, custom, available.
     *
     * @return array
     */
    public function getGrouped(): array
    {
        return $this->moduleCatalog->getGrouped();
    }
}
