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

/**
 * FormTest.php
 * skyline-html-form
 *
 * Created on 2019-09-25 19:36 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\HTML\Form\Control\Text\TextFieldControl;
use Skyline\HTML\Form\FormElement;
use Skyline\HTML\Form\Validator\ExactLengthValidator;

class FormTest extends TestCase
{
    public function testForm() {
        $form = new FormElement("", 'post', 'identifier');

        $form->setHiddenValue("info", 78);

        $tf = new TextFieldControl("name");
        $form->appendElement($tf);

        $tf->setValidFeedback("OK");
        $tf->setInvalidFeedback("Not OK");

        $tf->setLabel("Name:");
        $tf->setDescription("Your name");

        $tf->addValidator(new ExactLengthValidator(23));

        $list = $form->validateForm($valid);
        print_r($list);


        $form->focusControl($tf);
        echo $form->getRenderable()(NULL);
    }
}
