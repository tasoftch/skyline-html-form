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

use Skyline\HTML\ElementInterface;
use Skyline\HTML\TextContentElement;

abstract class AbstractLabelControl extends AbstractControl
{
    /** @var string */
    private $label;

    /** @var mixed  */
    private $description = "";

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
	 * @return static
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
	 * @return static
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Builds the label element
     *
     * @return ElementInterface|null
     */
    protected function buildLabelElement(): ?ElementInterface {
        if($this->containerElement && $label = $this->getLabel()) {
            $label = new TextContentElement("label", $label);
            $label->setSkipInlineFormat(true);
            $label["for"] = $this->controlElement["id"];

            if($map = $this->getForm()->getStyleClassMap()) {
                $label = $map->styleUpElement($label, $map::LABEL_ELEMENT, $this);
            }


            return $label;
        }
        return NULL;
    }

    protected function buildControl(): ElementInterface
    {
        $control = parent::buildControl();
        if($this->containerElement && $desc = $this->getDescription()) {
            $control["aria-describedby"] = $control["id"] . "-help";
        }
        return $control;
    }

    /**
     * Builds the description element
     *
     * @param $info
     * @return ElementInterface|null
     */
    protected function buildDescriptionElement(): ?ElementInterface {
        if($this->containerElement && $desc = $this->getDescription()) {
            $element = new TextContentElement("small", $desc);
            $element->setSkipInlineFormat(true);
            $element["id"] = $this->controlElement["id"] . "-help";
            $element["aria-labelledby"] = $this->controlElement["id"];

            if($map = $this->getForm()->getStyleClassMap()) {
                $element = $map->styleUpElement($element, $map::DESCRIPTION_ELEMENT, $this);
            }

            return $element;
        }
        return NULL;
    }
}