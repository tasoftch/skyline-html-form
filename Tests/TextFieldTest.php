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
 * TextFieldTest.php
 * skyline-html-form
 *
 * Created on 2019-09-24 17:17 by thomas
 */

use PHPUnit\Framework\TestCase;
use Skyline\HTML\Form\Control\Option\PopUpControl;
use Skyline\HTML\Form\Control\Text\TextAreaControl;
use Skyline\HTML\Form\Control\Text\TextFieldControl;
use Skyline\Render\Context\DefaultRenderContext;

class TextFieldTest extends TestCase
{
    public function tes_tLabelledTextField() {
        $ctx = new DefaultRenderContext();
        $tf = new TextFieldControl("test", 'he', TextFieldControl::TYPE_PASSWORD);

        $tf->setDescription("My description");
        $tf->setDefaultValue("23");
        $tf->setPlaceholder("The placeholder");
        $tf->setValue("zz");

        $tf->setEnabled(false);

        $tf->setLabel("My Text: ");


        $this->assertFalse($tf->shouldBindToContext($ctx));
        echo $tf->getRenderable()(NULL);
    }

    public function tes_tTextArea() {
        $ctx = new DefaultRenderContext();
        $tv = new TextAreaControl("test", 'he');

        $tv->setValue("Hello < Here world");
        $tv->setPlaceholder("Hehe");
        $tv->setLabel("Test: ");
        $tv->setDescription("Hehe");

        $this->assertFalse($tv->shouldBindToContext($ctx));
        echo $tv->getRenderable()(NULL);
    }

    public function tes_tOptionsList() {
        $ctx = new DefaultRenderContext();
        $tv = new PopUpControl("test", 'he');

        $tv->setOption('tt', "Thomas T");

        $this->assertFalse($tv->shouldBindToContext($ctx));
        echo $tv->getRenderable()(NULL);
    }
}
