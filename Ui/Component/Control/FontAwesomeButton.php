<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Core\Ui\Component\Control;

use Magento\Ui\Component\Control\Button;

/**
 * Widget for fontawesome button.
 * @inheritDoc
 */
class FontAwesomeButton extends Button
{
    public const FONT_NAME = 'fontawesome_name';

    /**
     * @inheritDoc
     */
    protected function getTemplatePath(): string
    {
        return 'Byte8_Core::control/button/fontawesome.phtml';
    }

    /**
     * @return string
     */
    public function getFontName(): string
    {
        return (string) $this->getData(self::FONT_NAME);
    }
}
