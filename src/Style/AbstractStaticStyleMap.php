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
use Skyline\HTML\Form\FormElement;

abstract class AbstractStaticStyleMap extends AbstractStyleMap
{
    const FORM_VALIDATED_STYLE = 'form.validated';
    const FORM_VALID_STYLE = 'form.valid';
    const FORM_INVALID_STYLE = 'form.invalid';

    const CONTAINER_STYLE = 'container';

    const CONTROL_STYLE = 'control';
    const CONTROL_REQUIRED_STYLE = 'control.required';

    const CONTROL_LABEL_STYLE = 'control.label';
    const CONTROL_DESCRIPTION_STYLE = 'control.description';

    const CONTROL_VALIDATED_STYLE = 'control.validated';
    const CONTROL_VALID_STYLE = 'control.valid';
    const CONTROL_INVALID_STYLE = 'control.invalid';

    const FEEDBACK_VALID_STYLE = 'feedback.valid';
    const FEEDBACK_INVALID_STYLE = 'feedback.invalid';

    public function styleUpElement(ElementInterface $element, string $elementName, ?ControlInterface $control): ElementInterface
    {
        $classes = [];
        $objectClass = get_class($control);

        $cget = function($style) use ($objectClass) {
            return $this->getStyleClass($style, $objectClass);
        };

        if($elementName == static::FORM_ELEMENT) {
            if($this->isFormValidated($element)) {
                $classes[] = $cget(static::FORM_VALIDATED_STYLE);
                $classes[] = $cget($this->isFormValid($element) ? static::FORM_VALID_STYLE : static::FORM_INVALID_STYLE);
            }
        } elseif($elementName == static::CONTAINER_ELEMENT) {
            $classes[] = $cget(static::CONTAINER_STYLE);
        } elseif($elementName == static::CONTROL_ELEMENT) {
            $classes[] = $cget(static::CONTROL_STYLE);

            if($this->isControlRequired($control))
                $classes[] = $cget(static::CONTROL_REQUIRED_STYLE);
            if($this->isControlValidated($control)) {
                $classes[] = $cget(static::CONTROL_VALIDATED_STYLE);
                $classes[] = $cget($this->isControlValid($control) ? static::CONTROL_VALID_STYLE : static::CONTROL_INVALID_STYLE);
            }
        } elseif($elementName == static::LABEL_ELEMENT) {
            $classes[] = $cget(static::CONTROL_LABEL_STYLE);
        } elseif($elementName == static::DESCRIPTION_ELEMENT) {
            $classes[] = $cget(static::CONTROL_DESCRIPTION_STYLE);
        } elseif($elementName == static::FEEDBACK_VALID_ELEMENT) {
            $classes[] = $cget(static::FEEDBACK_VALID_STYLE);
        } elseif($elementName == static::FEEDBACK_INVALID_ELEMENT) {
            $classes[] = $cget(static::FEEDBACK_INVALID_STYLE);
        }

        if($classes = array_filter($classes, function($a) {return $a ? true : false; }))
            $element["class"] = implode(" ", $classes);

        return $element;
    }

    /**
     * get mapped style class
     *
     * @param string $style
     * @param string|null $objectClass
     * @return string|null
     */
    abstract protected function getStyleClass(string $style, string $objectClass = NULL): ?string;
}