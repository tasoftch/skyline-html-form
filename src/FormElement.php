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
use Skyline\HTML\Form\Control\AbstractControl;
use Skyline\HTML\Form\Control\ControlInterface;
use Skyline\HTML\Form\Control\Verification\VerificationControlInterface;
use Skyline\HTML\Form\Exception\_InternOptionalCancelException;
use Skyline\HTML\Form\Exception\FormValidationException;
use Skyline\HTML\Form\Style\StyleMapInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationExpiredException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use TASoft\Service\ServiceManager;

class FormElement extends Element implements ElementInterface
{
    const CSRF_TOKEN_NAME = 'skyline-csrf-token';


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
    private $verified = false;

    private $alwaysDisplayValidationFeedbacks = false;

    /** @var VerificationControlInterface */
    private $verificationControl;
    private $verificationControlOptions;

    /**
     * @var StyleMapInterface|null
     */
    private $styleClassMap;

    /** @var CsrfToken */
    private $CSRFToken;
    private $_sentCsrfToken;


    public function __construct(string $actionName, string $method = 'POST', $identifier = NULL, bool $multipart = false)
    {
        parent::__construct("form", true);
        $this["action"] = $this->actionName = $actionName;
        $this["method"] = $this->method = $method;
        if(($this->multipart = $multipart))
            $this["enctype"] = 'multipart/form-data';

        /** @var CsrfTokenManagerInterface $csrfMan */
        if($csrfMan = ServiceManager::generalServiceManager()->CSRFManager) {
            $this->CSRFToken = $csrfMan->getToken(static::CSRF_TOKEN_NAME);
        }
    }

    public function appendElement(ElementInterface $childElement)
    {
        if($childElement instanceof VerificationControlInterface) {
            if($this->verificationControl) {
                trigger_error("Only accepts one verification control", E_USER_WARNING);
                return;
            }

            $this->verificationControl = $childElement;
        }

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
        foreach($request->request as $key => $value) {
            if($key == '__skyline_verification__') {
                $this->verificationControlOptions = unserialize( base64_decode( $value ) );
                continue;
            }
            $data[$key] = $value;
        }

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

        if($csrf = $this->getCSRFToken()) {
            /** @var CsrfTokenManagerInterface $csrfMan */
            $csrfMan = ServiceManager::generalServiceManager()->CSRFManager;
            if(!$csrfMan->isTokenValid(new CsrfToken(static::CSRF_TOKEN_NAME, $this->_sentCsrfToken))) {
                $valid = $this->valid = false;
                throw new AuthenticationException("CSRF field is not valid", 403);
            }
        }

        $invalidate = function(ControlInterface $element) use (&$list, &$valid ) {
            $valid = $this->valid = false;
            $list[ $element->getName() ] = $element;
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

        if(!$list && ($vc = $this->getVerificationControl())) {
            $this->verified = $vc->verifyWithOptions( $this->verificationControlOptions ?? [] );
            if(!$this->verified) {
                if($vc instanceof AbstractControl)
                    /** @var \stdClass $vc */
                    (function() use ($vc) {$vc->valid = false;})->bindTo($vc, AbstractControl::class)();

                $invalidate($vc);
            }
        }

        if($map = $this->getStyleClassMap()) {
            $map->styleUpElement($this, $map::FORM_ELEMENT, NULL);
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
            $csrfID = NULL;
            if($csrf = $this->getCSRFToken()) {
                $csrfID = $csrf->getId();
            }

            foreach($data as $key => $value) {
                if($csrfID && $key == $csrfID) {
                    $this->_sentCsrfToken = $value;
                    continue;
                }

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
     * @return StyleMapInterface|null
     */
    public function getStyleClassMap(): ?StyleMapInterface
    {
        return $this->styleClassMap;
    }

    /**
     * @param StyleMapInterface|null $styleClassMap
     */
    public function setStyleClassMap(?StyleMapInterface $styleClassMap): void
    {
        $this->styleClassMap = $styleClassMap;
    }

    /**
     * @return VerificationControlInterface|null
     */
    public function getVerificationControl(): ?VerificationControlInterface
    {
        return $this->verificationControl;
    }

    /**
     * @return bool
     */
    public function isVerified(): bool
    {
        return $this->verified;
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

    /**
     * Define values that are not visible for the user, but sent with the form
     *
     * @param string $name
     * @param $value
     */
    public function setHiddenValue(string $name, $value) {
        if($value === NULL)
            unset($this->hiddenValues[$name]);
        else
            $this->hiddenValues[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getHiddenValue(string $name) {
        return $this->hiddenValues[$name] ?? NULL;
    }

    /**
     * If you want to design your own form, just use its basic features.
     * Calling this method requires a callback that outputs html code including the form controls.
     * The following workflow is done:
     * 1.  The CSRF token is printed if available
     * 2.  The hidden values are listed in <input type="hidden" .... /> html tags
     * 3.  Your callback is invoked
     * 4.  The focus is set using javascript
     *
     * Inside the callback, use the manualBuildControl method to insert only the controls in your code.
     *
     * @param callable $contentBlock
     * @param int $indention
     * @see FormElement::manualBuildControl()
     */
    public function manualBuildForm(callable $contentBlock, int $indention = 0) {
        echo $this->stringifyStart($indention);
        call_user_func($contentBlock);
        echo $this->stringifyEnd($indention);
    }

    /**
     * Manually builds a control html and outputs it.
     * Use this method to place your controls directly in the html code used in the callback from manualBuildForm
     *
     * @param string $name
     * @param array $additionalAttributes
     * @param array $validationClasses
     * @return AbstractControl|null
     * @see FormElement::manualBuildForm()
     */
    public function manualBuildControl(string $name, array $additionalAttributes = [], array $validationClasses = []) {
        if(($control = $this->getControlByName($name)) && $control instanceof AbstractControl) {
            $old = [];
            foreach($additionalAttributes as $name => $attrs) {
                $old[$name] = $control[$name];
                $control[$name] = is_array($attrs) ? implode(" ", $attrs) : $attrs;
            }

            if($control->isValidated()) {
                if($control->isValid()) {
                    $vc = $validationClasses["valid"] ?? 'valid';
                    $control["class"] .= " $vc";
                } else {
                    $vc = $validationClasses["invalid"] ?? 'invalid';
                    $control["class"] .= " $vc";
                }
            }

            (function()use($control){
                /** @noinspection Annotator */
                echo $control->buildControl();})->bindTo($control, get_class($control))();

            foreach($old as $name => $attrs) {
                $control[$name] = $attrs;
            }
            return $control;
        }
        return NULL;
    }

    /**
     * @inheritDoc
     */
    public function toString(int $indention = 0): string
    {
        $this->setHiddenValue("__skyline_verification__", NULL);

        if($vc = $this->getVerificationControl()) {
            $values = $vc->prepareVerificationOptions();
            if($values) {
                $data = base64_encode(serialize($values));
                $this->setHiddenValue('__skyline_verification__', $data);
            }
        }

        return parent::toString($indention);
    }

    /**
     * @inheritDoc
     */
    protected function stringifyStart(int $indention = 0): string
    {
        $ind = $this->formatOutput() ? ($this->getIndentionString($indention) . "\t") : '';
        $nl = $this->formatOutput() ? PHP_EOL : '';

        $html = parent::stringifyStart($indention);

        if($csrf = $this->getCSRFToken()) {
            $html .= sprintf("$ind<input type=\"hidden\" name=\"%s\" value=\"%s\">$nl", htmlspecialchars( $csrf->getId() ), htmlspecialchars($csrf->getValue()));
        }

        foreach($this->hiddenValues as $attr => $value) {
            $html .= sprintf("$ind<input type=\"hidden\" name=\"%s\" value=\"%s\">$nl", htmlspecialchars($attr), htmlspecialchars($value));
        }
        return $html;
    }

    /**
     * @inheritDoc
     */
    protected function stringifyEnd(int $indention = 0): string
    {
        $html = parent::stringifyEnd($indention);
        if($focus = $this->getControlInFocus()) {
            $html .= "<script type='application/javascript'>document.getElementById('". $focus->getID() . "').focus();</script>";
        }
        return $html;
    }

    /**
     * @return CsrfToken|null
     */
    public function getCSRFToken(): ?CsrfToken
    {
        return $this->CSRFToken;
    }

    /**
     * @param CsrfToken $CSRFToken
     */
    public function setCSRFToken(CsrfToken $CSRFToken): void
    {
        $this->CSRFToken = $CSRFToken;
    }
}