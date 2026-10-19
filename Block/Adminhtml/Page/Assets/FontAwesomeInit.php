<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Block\Adminhtml\Page\Assets;

use Magento\Backend\Block\Template;

/**
 * @inheritDoc
 */
class FontAwesomeInit extends Template
{
    /**
     * FontAwesome Free bundled with the module (CSS + woff2 webfonts under
     * view/adminhtml/web/fontawesome/). Served through Magento static content,
     * i.e. the 'self' CSP origin - merchants with hardened Content-Security-Policy
     * headers (style-src/font-src without external hosts) would silently lose all
     * grid icons when the stylesheet came from use.fontawesome.com, which not
     * even csp_whitelist.xml can fix when the policy is set at the webserver.
     */
    private const BUNDLED_SOURCE = 'Byte8_Core::fontawesome/css/all.min.css';

    private const XML_PATH_IS_ACTIVE = 'byte8_core/ui/is_active_fontawesome';
    private const XML_PATH_SOURCE = 'byte8_core/ui/fontawesome_resource';

    /**
     * @inheritDoc
     */
    protected function _prepareLayout()
    {
        if (!$this->_scopeConfig->isSetFlag(self::XML_PATH_IS_ACTIVE)) {
            return parent::_prepareLayout();
        }

        // A configured host URL remains supported as an explicit override (e.g.
        // a Pro kit or a merchant-managed CDN); the default is the bundled copy.
        if ($remoteSource = $this->_scopeConfig->getValue(self::XML_PATH_SOURCE)) {
            $this->pageConfig->addRemotePageAsset($remoteSource, 'css');
        } else {
            $this->pageConfig->addPageAsset(self::BUNDLED_SOURCE);
        }

        return parent::_prepareLayout();
    }
}
