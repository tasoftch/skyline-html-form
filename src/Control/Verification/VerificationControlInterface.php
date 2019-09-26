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

namespace Skyline\HTML\Form\Control\Verification;


use Skyline\HTML\Form\Control\ControlInterface;


/**
 * Verification controls can be used to verify if the user is a human.
 * Like captcha or others.
 * A verification control is allowed to add or remove hidden values to a form element to identify the verification.
 *
 * The verification control is evaluated at end of whole form validation and is able to make a form invalid again.
 *
 * @package Skyline\HTML\Form
 */
interface VerificationControlInterface extends ControlInterface
{
    /**
     * This method is called before displaying a html form.
     * It must prepare the verification and return a keyed array with hidden values that is available to verify
     * Note that you may only return serializable values!
     *
     * @return array
     */
    public function prepareVerificationOptions(): array ;

    /**
     * Called after the form validation.
     *
     * If this method returns false, the verification has failed and the form still gets invalid
     * This method receives the prepared options as argument.
     *
     * @param array $options
     * @return bool
     */
    public function verifyWithOptions(array $options): bool;
}