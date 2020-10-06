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


use Skyline\HTML\Form\Action\ActionInterface;
use Skyline\Security\CSRF\CSRFToken;
use Skyline\Security\CSRF\CSRFTokenManager;
use Skyline\Security\Exception\AuthenticationException;
use TASoft\Service\ServiceManager;

class SecureFormElement extends FormElement
{
    const CSRF_TOKEN_NAME = 'skyline-csrf-token';

    /** @var CSRFToken */
    private $CSRFToken;
    private $_sentCsrfToken;

    /**
     * SecureFormElement constructor.
     * @param string|ActionInterface $action
     * @param string $method
     * @param null $identifier
     * @param bool $multipart
     */
    public function __construct($action, string $method = 'POST', $identifier = NULL, bool $multipart = false)
    {
        parent::__construct($action, $method, $identifier, $multipart);
        /** @var CSRFTokenManager $csrfMan */
        if($csrfMan = ServiceManager::generalServiceManager()->CSRFManager) {
            $this->CSRFToken = $csrfMan->getToken(static::CSRF_TOKEN_NAME);
        }
    }

    public function setData($data, bool $imports = false)
    {
        if(is_iterable($data)) {
            if($csrf = $this->getCSRFToken()) {
                $csrfID = $csrf->getId();

                foreach($data as $key => $value) {
                    if($csrfID && $key == $csrfID) {
                        $this->_sentCsrfToken = $value;
                        break;
                    }
                }
            }
        }
        parent::setData($data, $imports);
    }

    /**
     * @return CSRFToken
     */
    public function getCSRFToken(): CSRFToken
    {
        return $this->CSRFToken;
    }

    /**
     * @param CSRFToken $CSRFToken
     */
    public function setCSRFToken(CSRFToken $CSRFToken): void
    {
        $this->CSRFToken = $CSRFToken;
    }

    public function validateForm(bool &$valid = NULL): array
    {
        if($csrf = $this->getCSRFToken()) {
            /** @var CSRFTokenManager $csrfMan */
            $csrfMan = ServiceManager::generalServiceManager()->CSRFManager;
            if(!$csrfMan->isTokenValid(new CsrfToken(static::CSRF_TOKEN_NAME, $this->_sentCsrfToken))) {
                (function() {$this->valid = $this->validated = false;})->bindTo($this, FormElement::class)();

                throw new AuthenticationException("CSRF field is not valid", 403);
            }
        }
        return parent::validateForm($valid);
    }

    protected function stringifyStart(int $indention = 0): string
    {
        $html = parent::stringifyStart($indention);
        if($csrf = $this->getCSRFToken()) {
            $ind = $this->formatOutput() ? ($this->getIndentionString($indention) . "\t") : '';
            $nl = $this->formatOutput() ? PHP_EOL : '';

            $html .= sprintf("$ind<input type=\"hidden\" name=\"%s\" value=\"%s\">$nl", htmlspecialchars( $csrf->getId() ), htmlspecialchars($csrf->getValue()));
        }
        return $html;
    }
}