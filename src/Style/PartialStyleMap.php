<?php
/**
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
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

namespace Skyline\HTML\Form\Style;


use Skyline\HTML\ElementInterface;
use Skyline\HTML\Form\Control\ControlInterface;

class PartialStyleMap implements StyleMapInterface
{
    private $styles = [];
    /** @var StyleMapInterface */
    private $defaultStyle;

    /**
     * PartialStyleMap constructor.
     * @param StyleMapInterface $defaultStyle
     */
    public function __construct(StyleMapInterface $defaultStyle, array $styles = NULL)
    {
        $this->defaultStyle = $defaultStyle;
        $this->styles = $styles;
    }

    /**
     * @inheritDoc
     */
    public function styleUpElement(ElementInterface $element, string $elementName, ?ControlInterface $control): ElementInterface
    {
        if($control) {
            $style = $this->styles[ $control->getName() ] ?? $this->getDefaultStyle();
            return $style->styleUpElement($element, $elementName, $control);
        }
        return $this->getDefaultStyle()->styleUpElement($element, $elementName, $control);
    }

    /**
     * @return StyleMapInterface
     */
    public function getDefaultStyle(): StyleMapInterface
    {
        return $this->defaultStyle;
    }

    /**
     * Defines a style map for a control name
     *
     * @param StyleMapInterface $styleMap
     * @param string $controlName
     */
    public function addStyleMap(StyleMapInterface $styleMap, string $controlName) {
        $this->styles[$controlName] = $styleMap;
    }

    /**
     * Removes a style map from receiver
     *
     * @param string $controlName
     */
    public function removeStyleMap(string $controlName) {
        if(isset($this->styles[$controlName]))
            unset($this->styles[$controlName]);
    }
}