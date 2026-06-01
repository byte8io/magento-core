<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Full-width informational banner rendered at the top of the
 * Byte8 Core Configuration section. Integration-neutral —
 * module-core is the shared foundation for any Byte8 Magento extension,
 * so this banner does not advertise any specific integration.
 */
class IntroSection extends Field
{
    protected $_template = 'Byte8_Core::system/config/intro_section.phtml';

    /**
     * Render as a full-row banner — no label / value / scope columns.
     */
    public function render(AbstractElement $element): string
    {
        return '<tr id="row_' . $element->getHtmlId() . '">'
            . '<td colspan="5">' . $this->toHtml() . '</td>'
            . '</tr>';
    }
}
