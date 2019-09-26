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

namespace Skyline\HTML\Form;

use Skyline\HTML\Element;
use Skyline\HTML\ElementInterface;
use Skyline\HTML\Form\Control\ControlInterface;
use Skyline\HTML\Form\Exception\_InternOptionalCancelException;
use Skyline\HTML\Form\Exception\FormValidationException;
use Symfony\Component\HttpFoundation\Request;

class FormElement extends Element implements ElementInterface
{
    /** @var string */
    private $actionName;
    /** @var string */
    private $method;
    /** @var bool */
    private $multipart;

    const FORM_STATE_INVALID = -1;
    const FORM_STATE_NONE = 0;
    const FORM_STATE_VALID = 1;

    /** @var ControlInterface|null */
    private $actionControl;

    /** @var ControlInterface|null */
    private $controlFocus;

    private $hiddenValues = [];

    private $valid = false;
    private $validated = false;
    private $alwaysDisplayValidationFeedbacks = false;


    public function __construct(string $actionName, string $method = 'POST', $identifier = NULL, bool $multipart = false)
    {

        parent::__construct("form", true);
        $this["action"] = $this->actionName = $actionName;
        $this["method"] = $this->method = $method;
        if(($this->multipart = $multipart))
            $this["enctype"] = 'multipart/form-data';
    }

    public function appendElement(ElementInterface $childElement)
    {
        parent::appendElement($childElement);
        if($childElement instanceof ControlInterface)
            $childElement->setForm($this);
    }

    /**
     * Searches for a control with given name
     *
     * @param string $anID
     * @return ControlInterface|null
     */
    public function getControlByName(string $aName): ?ControlInterface {
        foreach($this->getChildElements() as $control) {
            if($control instanceof ControlInterface) {
                if($control->getName() == $aName)
                    return $control;
            }
        }
        return NULL;
    }

    /**
     * @return string
     */
    public function getActionName(): string
    {
        return $this->actionName;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return bool
     */
    public function isMultipart(): bool
    {
        return $this->multipart;
    }

    /**
     * Prepares the form by using contents of a request. If the action button is recognized, the request's data will be validated and valid or not is returned using FORM_STATE_INVALID or FORM_STATE_VALID.
     * If the request does not contain the form's action button name, it will return FORM_STATE_NONE.
     *
     * @param Request $request
     * @param array|NULL $feedbacks
     * @return int
     *
     * @see FormElement::FORM_STATE_INVALID
     * @see FormElement::FORM_STATE_NONE
     * @see FormElement::FORM_STATE_VALID
     *
     * @see FormElement::getActionButton()
     */
    public function prepareWithRequest(Request $request, array &$feedbacks = NULL): int {
        if($button = $this->getActionControl()) {
            if($request->request->has($button->getName())) {
                $this->setDataFromRequest($request);
                $feedbacks = $this->validateForm($valid);
                return $valid ? self::FORM_STATE_VALID : self::FORM_STATE_INVALID;
            }
        }
        return self::FORM_STATE_NONE;
    }

    public function setDataFromRequest(Request $request) {
        $data = [];
        foreach($request->request as $key => $value) $data[$key] = $value;

        $this->setData($data);
    }

    /**
     * @return ControlInterface|null
     */
    public function getActionControl(): ?ControlInterface
    {
        return $this->actionControl;
    }

    /**
     * @param ControlInterface|null $actionControl
     */
    public function setActionControl(?ControlInterface $actionControl): void
    {
        $this->actionControl = $actionControl;
    }

    /**
     * Validates the form and retuns all invalid controls
     *
     * @param bool $valid
     * @return ControlInterface[]
     */
    public function validateForm(bool &$valid = NULL): array
    {
        $list = [];
        $valid = $this->valid = true;
        $this->validated = true;

        $this["class"] = 'was-validated is-valid';

        $invalidate = function(ControlInterface $element) use (&$list, &$valid ) {
            $valid = $this->valid = false;
            $list[ $element->getName() ] = $element;
            $this->removeClass("is-valid");
        };

        foreach($this->getChildElements() as $element) {
            if($element instanceof ControlInterface) {
                try {
                    if(false === $element->validate())
                        $invalidate($element);
                } catch (FormValidationException $exception) {
                    $invalidate($element);
                } catch (_InternOptionalCancelException $exception) {
                    if(!$exception->success)
                        $invalidate($element);
                    break;
                }
            }
        }
        return $list;
    }

    /**
     * Sets the form data
     *
     * @param iterable $data
     */
    public function setData($data) {
        if(is_iterable($data)) {
            // $csrf = $this->csrfToken ? $this->csrfToken->getId() : "";

            foreach($data as $key => $value) {
                //if($csrf && $key == $csrf) {
                //    $this->_sentCsrfToken = $value;
                //    continue;
                //}

                if($element = $this->getControlByName($key)) {
                    $element->setValue($value);
                }
                elseif($this[ $key ] ?? NULL) {
                    $this[ $key ] = $value;
                }
            }
        }
    }

    public function getData() {
        $list = [];
        foreach($this->getAttributes() as $name => $value) {
            $list[$name] = $value;
        }

        foreach($this->getChildElements() as $name => $element) {
            if($element instanceof ControlInterface)
                $list[ $element->getName() ] = $element->getValue();
        }
        return $list;
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
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * @return bool
     */
    public function isAlwaysDisplayValidationFeedbacks(): bool
    {
        return $this->alwaysDisplayValidationFeedbacks;
    }

    /**
     * Defines, if the valid and invalid feedbacks always are displayed or only if the validation was failed or succeeded
     *
     * @param bool $alwaysDisplayValidationFeedbacks
     */
    public function setAlwaysDisplayValidationFeedbacks(bool $alwaysDisplayValidationFeedbacks): void
    {
        $this->alwaysDisplayValidationFeedbacks = $alwaysDisplayValidationFeedbacks;
    }

    /**
     * @return ControlInterface|null
     */
    public function getControlInFocus(): ?ControlInterface
    {
        return $this->controlFocus;
    }

    /**
     * @param ControlInterface|null $controlFocus
     */
    public function focusControl(?ControlInterface $controlFocus): void
    {
        $this->controlFocus = $controlFocus;
    }

    public function setHiddenValue(string $name, $value) {
        if($value === NULL)
            unset($this->hiddenValues[$name]);
        else
            $this->hiddenValues[$name] = $value;
    }

    public function getHiddenValue(string $name) {
        return $this->hiddenValues[$name] ?? NULL;
    }

    protected function stringifyStart(int $indention = 0): string
    {
        $ind = $this->formatOutput() ? ($this->getIndentionString($indention) . "\t") : '';
        $nl = $this->formatOutput() ? PHP_EOL : '';

        $html = parent::stringifyStart($indention);

        foreach($this->hiddenValues as $attr => $value) {
            $html .= sprintf("$ind<input type=\"hidden\" name=\"%s\" value=\"%s\">$nl", htmlspecialchars($attr), htmlspecialchars($value));
        }
        return $html;
    }

    protected function stringifyEnd(int $indention = 0): string
    {
        $html = parent::stringifyEnd($indention);
        if($focus = $this->getControlInFocus()) {
            $html .= "<script type='application/javascript'>document.getElementById('". $focus->getID() . "').focus();</script>";
        }
        return $html;
    }
}