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

/**
 * A stylemap can be used to customize control's style while rendering
 *
 * @package Skyline\HTML\Form
 */
interface StyleMapInterface
{
    const FORM_ELEMENT = 'form';
    const CONTAINER_ELEMENT = 'container';
    const CONTROL_ELEMENT = 'control';
    const LABEL_ELEMENT = 'label';
    const DESCRIPTION_ELEMENT = 'description';

    const FEEDBACK_VALID_ELEMENT = 'valid-feedback';
    const FEEDBACK_INVALID_ELEMENT = 'invalid-feedback';


    /**
     * Style maps implementing this interface are able to customize the whole element even replacing it with something new.
     * Take care to not mixup the structure too much.
     * If $control is null, the form is passed to style up.
     *
     * @param ElementInterface $element
     * @param string $elementName
     * @param ControlInterface|null $control
     * @return ElementInterface
     */
    public function styleUpElement(ElementInterface $element, string $elementName, ?ControlInterface $control): ElementInterface;
}