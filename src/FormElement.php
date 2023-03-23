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
use Skyline\HTML\Form\Action\ActionInterface;
use Skyline\HTML\Form\Control\AbstractControl;
use Skyline\HTML\Form\Control\ActionControlInterface;
use Skyline\HTML\Form\Control\ControlInterface;
use Skyline\HTML\Form\Control\ExportControlInterface;
use Skyline\HTML\Form\Control\ImportControlInterface;
use Skyline\HTML\Form\Control\Render\LiveFormControlRenderInterface;
use Skyline\HTML\Form\Control\Verification\VerificationControlInterface;
use Skyline\HTML\Form\Exception\_InternOptionalCancelException;
use Skyline\HTML\Form\Exception\FormValidationException;
use Skyline\HTML\Form\Feedback\ManualFeedbackInterface;
use Skyline\HTML\Form\Style\StyleMapInterface;
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

    /** @var ActionControlInterface[]|null */
    private $actionControls = [];

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

	/** @var LiveFormControlRenderInterface|null */
	private $liveFormRender;

    /**
     * FormElement constructor.
     * @param string|ActionInterface $action
     * @param string $method
     * @param null $identifier
     * @param bool $multipart
     */
    public function __construct($action, string $method = 'POST', $identifier = NULL, bool $multipart = false)
    {
        parent::__construct("form", true);
        if($identifier)
            $this->setID($identifier);

        if($action instanceof ActionInterface)
            $this->actionName = $action->makeAction($this);
        else
            $this["action"] = $this->actionName = (string) $action;
        $this["method"] = $this->method = $method;
        if(($this->multipart = $multipart))
            $this["enctype"] = 'multipart/form-data';
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
        foreach($this->getActionControls() as $control) {
            if($request->request->has($control->getName())) {
                $this->setDataFromRequest($request);
                $feedbacks = $this->validateForm($valid);
                return $valid ? self::FORM_STATE_VALID : self::FORM_STATE_INVALID;
            }
        }
        return self::FORM_STATE_NONE;
    }

    /**
     * Performs the required action, if the form is valid
     *
     * @param Request $request
     * @return bool
     */
    public function performAction(Request $request): bool {
        if($this->isValidated() && $this->isValid()) {
            foreach($this->getActionControls() as $control) {
                if($request->request->has($control->getName())) {
                    return $control->performAction( $this->getData(true) );
                }
            }
        }
        return false;
    }

	/**
	 * Calling this method validates the form and if valid, it performs the action by the pressed action button.
	 *
	 *
	 * @param Request $request
	 * @param iterable|callable|array|null $defaultValuesHandler Invoked, if the form was loaded the first time, still without passed values yet. Signature: function(): array => expecting the form data.
	 * @param callable|null $failedHandler
	 */
    public function evaluateWithRequest(Request $request, $defaultValuesHandler = NULL, callable $failedHandler = NULL) {
    	switch ( $this->prepareWithRequest($request, $feedbacks) ) {
			case static::FORM_STATE_VALID:
				return $this->performAction($request);
			case static::FORM_STATE_INVALID:
				if($failedHandler)
					return call_user_func($failedHandler, $this->getData(), $feedbacks) ? true : false;
				return false;
			default:
				repeat:
				if($defaultValuesHandler) {
					if(is_callable($defaultValuesHandler)) {
						$defaultValuesHandler = call_user_func($defaultValuesHandler);
						goto repeat;
					}

					if(is_iterable($defaultValuesHandler))
						$this->setData($defaultValuesHandler, true);
				}
		}
		return true;
	}

    /**
     * Reads the data from a request that was submitted by the form
     *
     * @param Request $request
     */
    public function setDataFromRequest(Request $request) {
        $data = [];
        foreach($request->request as $key => $value) {
            if($key == 'hv://__skyline_verification__') {
                $this->verificationControlOptions = unserialize( base64_decode( $value ) );
                continue;
            }

            if(strpos($key, "hv://") === 0) {
                // Is hidden value
                $this->setHiddenValue(substr($key, 5), $value);
                continue;
            }
            $data[$key] = $value;
        }

        $this->setData($data);
    }

    /**
     * Use this method to reset the form to default, as like the client would see it the first time.
     */
    public function resetForm() {
        $this->hiddenValues = [];
        $this->valid = false;
        $this->validated = false;
        $this->verified = false;

        foreach($this->getChildElements() as $control) {
            if($control instanceof ControlInterface) {
                $control->reset();
            }
        }
    }

    /**
     * @return ActionControlInterface|null
     */
    public function getActionControl(): ?ActionControlInterface
    {
        return reset($this->actionControls);
    }

    /**
     * @param ActionControlInterface|null $actionControl
     */
    public function setActionControl(?ActionControlInterface $actionControl): void
    {
        $this->actionControls = [$actionControl];
    }

    /**
     * @param ActionControlInterface $actionControl
     */
    public function addActionControl(ActionControlInterface $actionControl) {
        $this->actionControls[] = $actionControl;
    }

    /**
     * @return ActionControlInterface[]
     */
    public function getActionControls(): array {
        return $this->actionControls;
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

        $invalidate = function(ControlInterface $element) use (&$list, &$valid ) {
            $valid = $this->valid = false;
            $list[ $element->getName() ] = $element;
        };

        foreach($this->getChildElements() as $element) {
            if($element instanceof ControlInterface && !($element instanceof ActionControlInterface)) {
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
	 * @param bool $imports
	 */
    public function setData($data, bool $imports = false) {
        if(is_iterable($data)) {
            foreach($data as $key => $value) {
                if($element = $this->getControlByName($key)) {
                	if($imports && $element instanceof ImportControlInterface && $element->importValue($value))
                		continue;
                    $element->setValue($value);
                }
                elseif($this[ $key ] ?? NULL) {
                    $this[ $key ] = $value;
                }
            }
        }
    }

	/**
	 * @param bool $exports
	 * @return array
	 */
    public function getData(bool $exports = false) {
        $list = [];
        foreach($this->getAttributes() as $name => $value) {
            $list[$name] = $value;
        }

        foreach($this->getChildElements() as $name => $element) {
        	if($exports && $element instanceof ExportControlInterface)
				$list[ $element->getName() ] = $element->exportValue();
            elseif($element instanceof ControlInterface)
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
        $this->setHiddenValue("__skyline_verification__", NULL);

        if($vc = $this->getVerificationControl()) {
            $values = $vc->prepareVerificationOptions();
            if($values) {
                $data = base64_encode(serialize($values));
                $this->setHiddenValue('__skyline_verification__', $data);
            }
        }

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
    public function manualBuildControl(string $name, array $additionalAttributes = ['class' => 'form-control'], array $validationClasses = ["valid" => 'is-valid', 'invalid' => 'is-invalid']) {
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

			$lfr = $this->getLiveFormRender();
			if($lfr && $lfr->hasTemplateAvailable()) {
				$lfr->renderTemplateUsingControl($control);
			} else {
				(function()use($control){
					/** @noinspection Annotator */
					echo $control->buildControl();})->bindTo($control, get_class($control))();
			}

            foreach($old as $name => $attrs) {
                $control[$name] = $attrs;
            }
            return $control;
        }
        return NULL;
    }

    /**
     * Helper method to select a feedback pattern
     *
     * @param string $controlName
     * @param ManualFeedbackInterface ...$feedbacks
     */
    public function manualBuildValidationFeedback(string $controlName, ManualFeedbackInterface ...$feedbacks) {
        if(($control = $this->getControlByName($controlName)) && $control instanceof AbstractControl) {
            if($control->isValidated()) {
                foreach ($feedbacks as $feedback) {
                    if(
                        ($feedback->isValidFeedback() && $control->isValid() && $feedback->matchForValidator( $control->getStoppedValidator() )) ||
                        (!$feedback->isValidFeedback() && !$control->isValid() && $feedback->matchForValidator( $control->getStoppedValidator() ))
                    ) {
                        $feedback->makeOutput();
                        break;
                    }
                }
            }
        }
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

        foreach($this->hiddenValues as $attr => $value) {
            $attr = "hv://$attr";
            $html .= sprintf("$ind<input type=\"hidden\" name=\"%s\" value=\"%s\">$nl", htmlspecialchars($attr), htmlspecialchars($value));
        }

		// This is a workaround because some browsers do not send the name of pressed submit button.
		if(count($this->getActionControls()) == 1) {
			$html .= sprintf("$ind<input type='hidden' value='%s' value='1'>", htmlspecialchars($this->getActionControls()[0]->getName()));
		}

        return $html;
    }

    /**
     * @inheritDoc
     */
    protected function stringifyEnd(int $indention = 0): string
    {
		$html = "";
		if(strpos($this->actionName, '@cb-') === 0) {
			$controls = [];

			static $submit_count = 1;

			$fn = "submit_$submit_count";
			$submit_count++;

			foreach($this->getActionControls() as $control) {
				$controls[] = sprintf("$('#%s, [name=\"%s\"]')[0].onclick = function() {return $fn(this)};", $control->getID(), htmlspecialchars($control->getName()));
			}

			if($controls) {
				$controls = join("\n", $controls);

				$cb = substr($this->actionName, 4);

				$html .=<<<EOT
<script type="application/javascript">
	function $fn(sender) {
        const fd = new FormData( sender.form );
        fd.append(sender.name, '');
        $cb(fd, sender);
        return false;
	}
$controls
</script>

EOT;

			}

		}

        $html .= parent::stringifyEnd($indention);
        if($focus = $this->getControlInFocus()) {
            $html .= "<script type='application/javascript'>document.getElementById('". $focus->getID() . "').focus();</script>";
        }



        return $html;
    }

	/**
	 * @return LiveFormControlRenderInterface|null
	 */
	public function getLiveFormRender(): ?LiveFormControlRenderInterface
	{
		return $this->liveFormRender;
	}

	/**
	 * @param LiveFormControlRenderInterface|null $liveFormRender
	 * @return static
	 */
	public function setLiveFormRender(?LiveFormControlRenderInterface $liveFormRender)
	{
		$this->liveFormRender = $liveFormRender;
		return $this;
	}
}