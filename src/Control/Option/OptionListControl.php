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

namespace Skyline\HTML\Form\Control\Option;

use Skyline\HTML\Element;
use Skyline\HTML\ElementInterface;
use Skyline\HTML\Form\Control\AbstractLabelControl;
use Skyline\HTML\Form\Control\DefaultContainerBuilderTrait;
use Skyline\HTML\Form\Control\Option\Provider\OptionProviderInterface;
use Skyline\HTML\TextContentElement;


/**
 * The Option list is designed to represent a bunch of radio input elements where the user can select one single option
 * @package Skyline\HTML\Form
 */
class OptionListControl extends AbstractLabelControl implements OptionValuesInterface
{
    use DefaultContainerBuilderTrait;

    private $options = [];

    /** @var OptionProviderInterface|null */
    private $optionProvider;

    public $classMap = [
    	'option-list' => 'form-control pt-1',
		'option-container' => '',
		'input' => ''
	];

	/**
	 * @param string $id
	 * @param $optionValue
	 * @param null $optionGroup
	 * @return static
	 */
    public function setOption(string $id, $optionValue, $optionGroup = NULL)
    {
        $this->options[$id] = $optionValue;
        return $this;
    }

    public function getOption(string $id, &$optionGroup = NULL)
    {
        return $this->options[$id] ?? NULL;
    }

    /**
     * @return OptionProviderInterface|null
     */
    public function getOptionProvider(): ?OptionProviderInterface
    {
        return $this->optionProvider;
    }

    /**
     * @param OptionProviderInterface|null $optionProvider
	 * @return static
     */
    public function setOptionProvider(?OptionProviderInterface $optionProvider)
    {
        $this->optionProvider = $optionProvider;
        return $this;
    }

    protected function buildControl(): ElementInterface
    {
        $control = new Element("div");
		$control["class"] = $this["class"] ?: $this->classMap["option-list"];

		if($op = $this->getOptionProvider())
            $options = $op->yieldOptions($grp);
        else
            $options = $this->options;

        foreach($options as $optID => $option) {
            $e = $this->buildOptionElement($optID, $option);
            $control->appendElement($e);
        }

        return $control;
    }

    protected function buildOptionElement($optionID, $optionValue): ElementInterface {
        $e = new Element("div");
        $e["class"] = $this->classMap["option-container"] ?? '';

        $e->appendElement($this->buildOptionInput($optionID, $optionValue));
        $e->appendElement($this->buildOptionLabel($optionID, $optionValue));

        return $e;
    }

    protected function buildOptionInput($optionID, $optionValue): ElementInterface {
        $input = new Element("input", false);
        $input["class"] = $this->classMap["input"] ?? '';

        if(!$this->isEnabled())
            $input["disabled"] = 'disabled';

        $input["name"] = $this->getName();
        $input["id"] = $this->getID() . "-$optionID";
        $input["type"] = 'radio';
        $input["value"] = $optionID;
        if($this->getValue() == $optionID)
            $input["checked"] = 'checked';

        return $input;
    }

    protected function buildOptionLabel($optionID, $optionValue): ElementInterface {
        $label = new TextContentElement("label", $optionValue);
        $label->setSkipInlineFormat(true);
        $label["for"] = $this->getID() . "-$optionID";
        $label["class"] = "mb-0 ml-1";
        return $label;
    }
}