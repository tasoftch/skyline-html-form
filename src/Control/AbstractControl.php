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


use Skyline\HTML\AbstractInlineBuildElement;
use Skyline\HTML\Element;
use Skyline\HTML\ElementInterface;
use Skyline\HTML\Form\Exception\_InternOptionalCancelException;
use Skyline\HTML\Form\Exception\FormValidationException;
use Skyline\HTML\Form\FormElement;
use Skyline\HTML\Form\Transformer\ValueTransformerInterface;
use Skyline\HTML\Form\Validator\Condition\ConditionInterface;
use Skyline\HTML\Form\Validator\ValidatorInterface;
use Skyline\HTML\TextContentElement;
use Skyline\Render\Context\RenderContextInterface;

abstract class AbstractControl extends AbstractInlineBuildElement implements ControlInterface
{
    private $value;
    /** @var FormElement|null */
    private $form;

    /** @var string */
    private $name;

    private $defaultValue;
    private $defaultValueUsed = true;
    private $validators = [];
	private $transformers = [];

    /** @var bool  */
    private $enabled = true;
    private $required = false;


    // During building, this properties are available
    /** @var RenderContextInterface */
    protected $renderContext;

    /** @var mixed|null */
    protected $renderInformation;

    /** @var ElementInterface|null */
    protected $containerElement;

    /** @var ElementInterface */
    protected $controlElement;

    protected $levelIndent = 0;

    private $valid = false;
    private $validated = false;
    /** @var ValidatorInterface|null */
    private $stoppedValidator;
    /** @var null|bool|FormValidationException */
    private $stoppedValidationReason;

    private $validFeedback = "";
    private $invalidFeedback = '';

	/**
	 * Construction helper method for fluent configuration
	 *
	 * @param string $name
	 * @param string|NULL $identifier
	 * @return static
	 */
	public static function create(string $name, string $identifier = NULL)
	{
		return new static($name, $identifier);
	}

    public function __construct(string $name, string $identifier = NULL)
    {
        parent::__construct();
        $this->name = $name;
        $this["id"] = is_string($identifier) ? $identifier : trim(preg_replace("/[^a-z0-9\-\[\]]+/i", '-', $name), "\ \t\n\r\0\x0B[]");
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param bool $required
	 * @return static
     */
    public function setRequired(bool $required)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @return FormElement|null
     */
    public function getForm(): ?FormElement
    {
        return $this->form;
    }

    /**
     * @param FormElement|null $form
	 * @return static
     */
    public function setForm(?FormElement $form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if($this->defaultValueUsed)
            return $this->defaultValue;
        return $this->value;
    }

    /**
     * @param mixed $value
	 * @return static
     */
    public function setValue($value)
    {
        $this->defaultValueUsed = false;
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
	 * @return static
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * @return ValidatorInterface[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

	/**
	 * @return ValueTransformerInterface[]
	 */
	public function getValueTransformers(): array {
		return $this->transformers;
	}

    /**
     * Adds a validator to the control
     *
     * @param ValidatorInterface $validator
     * @param int $tag
	 * @return static
     */
    public function addValidator(ValidatorInterface $validator, int $tag = 0) {
        if(!in_array($validator, $this->validators)) {
            $this->validators[] = $validator;
            if(method_exists($validator, 'setTag'))
                $validator->setTag($tag);
        }
        return $this;
    }

	/**
	 * Removes a validator from control
	 *
	 * @param ValidatorInterface $validator
	 * @return static
	 */
	public function removeValidator(ValidatorInterface $validator) {
		if(($idx = array_search($validator, $this->validators)) !== false) {
			unset($this->validators[$idx]);
		}
		return $this;
	}

	/**
	 * Adds a new transformer to the control.
	 *
	 * @param ValueTransformerInterface $transformer
	 * @return static
	 */
	public function addValueTransformer(ValueTransformerInterface $transformer) {
		if(!in_array($transformer, $this->transformers))
			$this->transformers[] = $transformer;
		return $this;
	}

	public function removeValueTransformer(ValueTransformerInterface $transformer) {
		if(($idx = array_search($transformer, $this->transformers)) !== false) {
			unset($this->transformers[$idx]);
		}
		return $this;
	}

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->validated = false;
        $this->valid = false;
        $this->setValue( NULL );
        $this->defaultValueUsed = true;
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        if($this->skipValidation())
            return true;

        $value = $this->getValue();

		/** @var ValueTransformerInterface $transformer */
		foreach($this->transformers as $transformer) {
			$value = $transformer->getTransformedValue( $value );
			if($transformer->overwritesTransformedValue())
				$this->setValue( $value );
		}

        $this->validated = true;
        $this->valid = true;

        foreach($this->getValidators() as $validator) {
            if(method_exists($validator, 'getCondition') && ($condition = $validator->getCondition())) {
                if($condition instanceof  ConditionInterface && !$condition->isConditionTrue($value)) {
                    trigger_error("Skip Validator of control " . $this->getName() . " because condition is not true", E_USER_NOTICE);
                    continue;
                }
            }
            try {
                if($validator->validateValue($value) === false) {
                    $this->valid = false;
                    $this->stoppedValidator = $validator;
                    return false;
                }
            } catch (FormValidationException $exception) {
                $this->valid = false;
                $this->stoppedValidator = $validator;
                $this->stoppedValidationReason = $exception;
                throw $exception;
            } catch (_InternOptionalCancelException $exception) {
                $this->stoppedValidationReason = $this->valid = $exception->success;
                $this->stoppedValidator = $validator;
                throw $exception;
            }

        }
        return true;
    }

    /**
     * Subclasses may decide if they are able to validate.
     *
     * @return bool
     */
    protected function skipValidation(): bool {
        return false;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return ValidatorInterface|string|null
     */
    public function getStoppedValidator()
    {
        return $this->stoppedValidator;
    }

	/**
	 * @return bool|FormValidationException|null
	 */
	public function getStoppedValidationReason()
	{
		return $this->stoppedValidationReason;
	}

    /**
     * @return string
     */
    public function getInvalidFeedback(): string
    {
        return $this->invalidFeedback;
    }

    /**
     * @param string $invalidFeedback
	 * @return static
     */
    public function setInvalidFeedback(string $invalidFeedback)
    {
        $this->invalidFeedback = $invalidFeedback;
        return $this;
    }

    /**
     * @return string
     */
    public function getValidFeedback(): string
    {
        return $this->validFeedback;
    }

    /**
     * @param string $validFeedback
	 * @return static
     */
    public function setValidFeedback(string $validFeedback)
    {
        $this->validFeedback = $validFeedback;
        return $this;
    }

    /**
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->validated;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
	 * @return static
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isDefaultValueUsed(): bool
    {
        return $this->defaultValueUsed;
    }

    public function toString($indent = 0): string
    {
        $this->levelIndent = $indent;
        return parent::toString();
    }

    /**
     * @inheritDoc
     */
    protected function buildElement(?RenderContextInterface $context, $info)
    {
        $this->renderContext = $context;
        $this->renderInformation = $info;

        $this->containerElement = $element = $this->buildInitialElement();
        if($noEl = $element ? false : true) {
            $element = new Element("d", true);
        }

        $this->controlElement = $control = $this->buildControl();

        if($map = $this->getForm()->getStyleClassMap()) {
            $control = $map->styleUpElement($control, $map::CONTROL_ELEMENT, $this);
        }

        $this->buildFinalContainer($element, $control, $context, $info);
        if($map) {
            $element = $map->styleUpElement($element, $map::CONTAINER_ELEMENT, $this);
        }

        $this->renderContext = NULL;
        $this->renderInformation = NULL;

        if($noEl) {
            $str = "";
            foreach($element->getChildElements() as $child)
                $str .= $child->toString($this->levelIndent);
            return $str;
        } else {
            return $element->toString($this->levelIndent);
        }
    }

    /**
     * Use this method to create a container html element to represent this control.
     *
     * @return ElementInterface|null
     */
    protected function buildInitialElement(): ?ElementInterface {
        return NULL;
    }

    /**
     * Creates only the html element instance to represent the control
     *
     * @return ElementInterface
     */
    protected function buildControlElementInstance(): ElementInterface {
        return new Element("input", false);
    }

    /**
     * Creates a valid feedback html element
     *
     * @return ElementInterface|null
     */
    protected function buildValidFeedback(): ?ElementInterface {
        if($this->containerElement) {
            if($this->getForm()->isAlwaysDisplayValidationFeedbacks() || ($this->isValidated() && $this->isValid())) {
                $element = new TextContentElement("div", $this->getValidFeedback() ?? "");
                if($map = $this->getForm()->getStyleClassMap()) {
                    $element = $map->styleUpElement($element, $map::FEEDBACK_VALID_ELEMENT, $this);
                }
                return $element;
            }
        }
        return NULL;
    }

    /**
     * Creates an invalid feedback html element
     *
     * @return ElementInterface|null
     */
    protected function buildInvalidFeedback(): ?ElementInterface {
        if($this->containerElement) {
            if($this->getForm()->isAlwaysDisplayValidationFeedbacks() || ($this->isValidated() && !$this->isValid())) {
                $element = new TextContentElement("div", $this->getInvalidFeedback() ?? "");
                if($map = $this->getForm()->getStyleClassMap()) {
                    $element = $map->styleUpElement($element, $map::FEEDBACK_INVALID_ELEMENT, $this);
                }
                return $element;
            }
        }
        return NULL;
    }


    /**
     * Build the default control instance buildControlElementInstance for an instance and copies all attributes into it.
     * The built in implementation asks
     *
     * @return ElementInterface
     */
    protected function buildControl(): ElementInterface {
        $control = $this->buildControlElementInstance();
        $control["id"] = $this->getID();

        $control["name"] = $this->getName();

        foreach($this->getAttributes() as $key => $value)
            $control[$key] = $value;

        if($v = $this->getValue())
            $control["value"] = $this->convertValueToHTML($v);

        if(!$this->isEnabled())
            $control["disabled"] = 'disabled';

        return $control;
    }

    /**
     * Converts a value to html if needed
     *
     * @param $value
     * @return mixed
     */
    protected function convertValueToHTML($value) {
        return $value;
    }

    /**
     * This method is called right before rendering the container element to adjust the container if needed
     *
     * @param ElementInterface $container
     * @param ElementInterface $control
     * @param RenderContextInterface $context
     * @param $info
     */
    abstract protected function buildFinalContainer(ElementInterface $container, ElementInterface $control, ?RenderContextInterface $context, $info);
}