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
use Skyline\HTML\Form\Validator\ValidatorInterface;

class AbstractControl implements ControlInterface
{
    private $value;
    /** @var FormElement|null */
    private $form;

    /** @var string */
    private $name;

    private $defaultValue;
    private $defaultValueUsed = true;
    private $validators = [];

    public function __construct(string $name)
    {
        $this->name = $name;
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
     */
    public function setForm(?FormElement $form): void
    {
        $this->form = $form;
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
     */
    public function setValue($value): void
    {
        $this->defaultValueUsed = false;
        $this->value = $value;
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
     */
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @param bool $defaultValueUsed
     */
    public function setDefaultValueUsed(bool $defaultValueUsed): void
    {
        $this->defaultValueUsed = $defaultValueUsed;
    }

    /**
     * @return ValidatorInterface[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    public function addValidator(ValidatorInterface $validator) {
        if(!in_array($validator, $this->validators))
            $this->validators[] = $validator;
    }

    public function removeValidator(ValidatorInterface $validator) {
        if(($idx = array_search($validator, $this->validators)) !== false) {
            unset($this->validators[$idx]);
        }
    }

    public function validate()
    {
        $value = $this->getValue();
        foreach($this->getValidators() as $validator) {
            try {
                if($feedback = $validator->validateValue($value))
                    return $feedback;
            } catch (FormValidationException $e) {
                $e->setValidator($validator);
                throw $e;
            } catch (_InternOptionalCancelException $exception) {
                // Optional validator passed. Cancel further validators
                break;
            }
        }
        return NULL;
    }
}