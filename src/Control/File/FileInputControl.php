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

namespace Skyline\HTML\Form\Control\File;


use Skyline\HTML\ElementInterface;
use Skyline\HTML\Form\Control\Text\TextFieldControl;

class FileInputControl extends TextFieldControl
{
    /** @var bool */
    private $multiple = false;
    /** @var array | null */
    private $allowedTypes;

	public static function create(string $name, string $identifier = NULL, string $type = self::TYPE_TEXT)
	{
		return parent::create($name, $identifier, 'file');
	}

	/**
     * @inheritDoc
     */
    public function __construct(string $name, string $id = NULL)
    {
        parent::__construct($name, $id, 'file');
    }

    /**
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @param bool $multiple
	 * @return static
     */
    public function setMultiple(bool $multiple)
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getAllowedTypes(): ?array
    {
        return $this->allowedTypes;
    }

    /**
     * @param array|null $allowedTypes
	 * @return static
     */
    public function setAllowedTypes(?array $allowedTypes)
    {
        $this->allowedTypes = $allowedTypes;
        return $this;
    }

    protected function buildControl(): ElementInterface
    {
        $control = parent::buildControl();
        if($this->isMultiple())
            $control["multiple"] = 'multiple';
        if($types = $this->getAllowedTypes())
            $control["accept"] = implode(",", $types);
        return $control;
    }
}