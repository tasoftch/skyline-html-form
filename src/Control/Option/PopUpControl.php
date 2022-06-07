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

class PopUpControl extends AbstractLabelControl implements OptionValuesInterface
{
    use DefaultContainerBuilderTrait;

    const NULL_VALUE_MARKER = '--0--';

    private $options = [];
    /** @var string|null */
    private $nullPlaceholder;
    /** @var OptionProviderInterface|null */
    private $optionProvider;

    private $shouldOrderOptions = false;

	/**
	 * @return bool
	 */
	public function shouldOrderOptions(): bool
	{
		return $this->shouldOrderOptions;
	}

	/**
	 * @param bool $shouldOrderOptions
	 * @return static
	 */
	public function setShouldOrderOptions(bool $shouldOrderOptions)
	{
		$this->shouldOrderOptions = $shouldOrderOptions;
		return $this;
	}

	/**
	 * @param string $id
	 * @param $optionValue
	 * @param null $optionGroup
	 * @return static
	 */
    public function setOption(string $id, $optionValue, $optionGroup = NULL)
    {
        $this->options[$id] = [
            $optionValue,
            $optionGroup
        ];
        return $this;
    }

    public function getOption(string $id, &$optionGroup = NULL)
    {
        if($opt = $this->options[$id] ?? NULL) {
            $optionGroup = $opt[1];
            return $opt[0];
        }
        return NULL;
    }

    /**
     * @return string|null
     */
    public function getNullPlaceholder(): ?string
    {
        return $this->nullPlaceholder;
    }

    /**
     * @param string|null $nullPlaceholder
	 * @return static
     */
    public function setNullPlaceholder(?string $nullPlaceholder)
    {
        $this->nullPlaceholder = $nullPlaceholder;
        return $this;
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

    protected function getGroupedOptions(): array {
        $options = [];

        if($op = $this->getOptionProvider()) {
            foreach($op->yieldOptions($group) as $id => $option) {
                $options[$group][$id] = $option;
            }
        } else {
            foreach($this->options as $id => $option) {
                $label = $option[0];
                $group = $option[1];

                $options[$group][$id] = $label;
            }
        }

		if($this->shouldOrderOptions()) {
			foreach($options as $group => &$option) {
				if($group)
					asort($option);
			}
		}


        return $options;
    }

    public function getValue()
    {
        $value = parent::getValue();
        return $value == static::NULL_VALUE_MARKER ? NULL : $value;
    }

    public function setValue($value): void
    {
        parent::setValue($value === NULL ? static::NULL_VALUE_MARKER : $value);
    }

    protected function buildControlElementInstance(): ElementInterface
    {
        return new Element("select");
    }

    protected function buildControl(): ElementInterface
    {
        $control = parent::buildControl();

        if($null = $this->getNullPlaceholder()) {
            $e = $this->buildOptionElement(static::NULL_VALUE_MARKER, $null);
            $control->appendElement($e);
        }
        foreach($this->getGroupedOptions() as $group => $options) {
            if($group) {
                $e = $this->buildOptionGroup($group, $options);
                $control->appendElement($e);
            } else {
                foreach($options as $optID => $option) {
                    $e = $this->buildOptionElement($optID, $option);
                    $control->appendElement($e);
                }
            }
        }

        return $control;
    }

    /**
     * Called for each group to create html elements
     *
     * @param $groupName
     * @param $options
     * @return ElementInterface
     */
    protected function buildOptionGroup($groupName, $options): ElementInterface {
        $g = new Element("optgroup");
        $g["label"] = $groupName;
        foreach($options as $optID => $option) {
            $e = $this->buildOptionElement($optID, $option);
            $g->appendElement($e);
        }
        return $g;
    }

    /**
     * Called for each menu item to create html elements
     *
     * @param $optionID
     * @param $optionValue
     * @return ElementInterface
     */
    protected function buildOptionElement($optionID, $optionValue): ElementInterface {
        $e = new TextContentElement("option", $optionValue);
        $e->setSkipInlineFormat(true);
        $e["value"] = $optionID;

        if($this->getValue() == $optionID)
            $e["selected"] = 'selected';

        return $e;
    }
}