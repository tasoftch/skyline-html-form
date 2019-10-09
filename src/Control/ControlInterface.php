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

namespace Skyline\HTML\Form\Control;

use Skyline\HTML\Form\FormElement;

interface ControlInterface
{
    /**
     * Form elements are normally identified by it's names
     *
     * @return string
     */
    public function getName();

    /**
     * For precise identification and html element compatibility, ids are used as well
     *
     * @return string
     */
    public function getID();

    /**
     * Set the controls value
     *
     * @param $value
     * @return void
     */
    public function setValue($value);

    /**
     * Get the control's value
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Validates the value of the control.
     *
     * @return false if validation fails
     */
    public function validate();

    /**
     * Puts the control to its default, so default value if available and reset validation.
     */
    public function reset();

    /**
     * Gets the form
     * @return FormElement|null
     */
    public function getForm(): ?FormElement;

    /**
     * Sets the form
     * Is done by build-in implementation of FormElement on appending as child.
     *
     * @param FormElement $formElement
     * @return void
     */
    public function setForm(FormElement $formElement);
}