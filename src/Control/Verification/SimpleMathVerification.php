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


use Skyline\HTML\Form\Control\Text\TextFieldControl;
use Skyline\HTML\Form\Validator\MatchRegexValidator;

class SimpleMathVerification extends TextFieldControl implements VerificationControlInterface
{
    /** @var int */
    private $number1;

    /** @var int */
    private $number2;

    public function __construct(string $name, string $id = NULL, string $type = self::TYPE_NUMBER)
    {
        parent::__construct($name, $id, $type);
        $this->addValidator(new MatchRegexValidator("/^\d+$/"));
    }

    public function prepareVerificationOptions(): array
    {
        return [
            $this->getNumber1(),
            $this->getNumber2()
        ];
    }

    public function verifyWithOptions(array $options): bool
    {
        list($num1, $num2) = $options;
        return $this->getValue() == $num1 + $num2 ? true : false;
    }

    /**
     * @return int
     */
    public function getNumber2(): int
    {
        if(!$this->number2)
            $this->number2 = rand(5, 20);
        return $this->number2;
    }

    /**
     * @param int $number2
     */
    public function setNumber2(int $number2): void
    {
        $this->number2 = $number2;
    }

    /**
     * @return int
     */
    public function getNumber1(): int
    {
        if(!$this->number1)
            $this->number1 = rand(5, 20);
        return $this->number1;
    }

    /**
     * @param int $number1
     */
    public function setNumber1(int $number1): void
    {
        $this->number1 = $number1;
    }
}