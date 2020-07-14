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

namespace Skyline\HTML\Form\Control\Date;


use Skyline\HTML\Form\Control\Text\TextFieldControl;
use Skyline\HTML\Form\Validator\CallbackValidator;

class DateFieldControl extends TextFieldControl
{
    private $dateFormat = 'd.m.Y';
    private $_setValueFailed = false;

    private $dateObjectClass = \DateTime::class;

    public function __construct(string $name, string $id = NULL, string $type = self::TYPE_TEXT)
    {
        parent::__construct($name, $id, $type);
        $this->addValidator(new CallbackValidator(function() {
            return !$this->_setValueFailed;
        }));
    }

    /**
     * @return string
     */
    public function getDateObjectClass(): string
    {
        return $this->dateObjectClass;
    }

    /**
     * @param string $dateObjectClass
	 * @return static
     */
    public function setDateObjectClass(string $dateObjectClass)
    {
        $this->dateObjectClass = $dateObjectClass;
        return $this;
    }

    /**
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * @param string $dateFormat
	 * @return static
     */
    public function setDateFormat(string $dateFormat)
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function setValue($value): void
    {
        if(!($value instanceof \DateTime) && $value) {
            try {
                $class = $this->getDateObjectClass();
                $value = new $class($value);
            } catch (\Exception $e) {
                $this->_setValueFailed = true;
                error_clear_last();
            }
        }
        parent::setValue($value);
    }

    protected function convertValueToHTML($value)
    {
        if($value instanceof \DateTime) {
            $value = $value->format( $this->getDateFormat() );
        }
        return $value;
    }
}