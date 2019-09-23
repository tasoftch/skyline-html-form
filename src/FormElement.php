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


use Skyline\HTML\AbstractElement;
use Skyline\HTML\ElementInterface;
use Skyline\HTML\Form\Control\ControlInterface;
use Skyline\HTML\Form\Exception\FormValidationException;
use Symfony\Component\HttpFoundation\Request;

class FormElement extends AbstractElement implements ElementInterface
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


    public function __construct(string $actionName, string $method = 'POST', $identifier = NULL, bool $multipart = false)
    {

        parent::__construct("form", true);
        $this->actionName = $actionName;
        $this->method = $method;
        $this->multipart = $multipart;
    }

    public function appendChild(ElementInterface $childElement)
    {
        parent::appendChild($childElement);
        if($childElement instanceof ControlInterface)
            $childElement->setForm($this);
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
     * Validates the form
     *
     * @param bool $valid
     * @return ValidationFeedback[]
     */
    public function validateForm(bool &$valid = NULL): array
    {
        $list = [];
        $valid = $this->valid = true;
        $this->validated = true;

        if($csrf = $this->getCsrfToken()) {
            if(!hash_equals($csrf->getValue(), $this->_sentCsrfToken)) {
                $e = new AuthenticationException("CSRF token missmatch", 403);
                throw $e;
            }
        }

        foreach($this->getChildElements() as $element) {
            if($element instanceof ControlInterface) {
                /** @var ValidationFeedback $feed */
                $feed = NULL;

                try {
                    $feed = $element->validate();
                    if(!($feed instanceof ValidationFeedback)) {
                        $cast = $feed ? true : false;
                        if($feed === NULL)
                            $cast = true;

                        $feed = new ValidationFeedback($cast, $element, 0, is_string($feed) ? $feed : ($cast ? 'Validation succeeded' : "Validation failed"));
                    }
                } catch (FormValidationException $exception) {
                    $feed = $exception->getFeedback();
                    if(!$feed) {
                        $msg = $exception->getMessage();

                        if($info = $this->messages[ $element->getName()  ][get_class($exception->getValidator())] ?? false) {
                            $msg = vsprintf($info, [$exception->getShortInfo()]);
                        } elseif ($info = $this->messages[ $element->getName()  ]['all'] ?? false) {
                            $msg = vsprintf($info, [$exception->getShortInfo()]);
                        }
                        $feed = new ValidationFeedback(false, $element, $exception->getCode(), $msg);
                    }
                }

                if($feed) {
                    if($feed->isSucceeded() == false)
                        $valid = $this->valid = false;

                    $list[ $feed->getControl()->getName() ] = $feed;
                }
                if(method_exists($element, 'setValidationFeedback')) {
                    $element->setValidationFeedback($feed);
                }
            }
        }
        return $list;
    }
}