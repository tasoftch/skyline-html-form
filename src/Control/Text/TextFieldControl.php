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

namespace Skyline\HTML\Form\Control\Text;

use Skyline\HTML\Element;
use Skyline\HTML\ElementInterface;
use Skyline\HTML\Form\Control\AbstractLabelControl;
use Skyline\HTML\Form\Control\DefaultContainerBuilderTrait;

class TextFieldControl extends AbstractLabelControl
{
    use DefaultContainerBuilderTrait;

    const TYPE_TEXT = 'text';
    const TYPE_PASSWORD = 'password';

    const TYPE_EMAIL = 'email';
    const TYPE_URL = 'url';
    const TYPE_NUMBER = 'number';

    const TYPE_SEARCH = 'search';

    const TYPE_TIME = 'time';
    const TYPE_DATE = 'date';

    /** @var string|null */
    private $placeholder;

    private $readonly = false;
    /** @var string  */
    private $type;

	public static function create(string $name, string $identifier = NULL, string $type = self::TYPE_TEXT)
	{
		return new static($name, $identifier, $type);
	}

	public function __construct(string $name, string $id = NULL, string $type = self::TYPE_TEXT)
    {
        parent::__construct($name, $id);
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    /**
     * @param bool $readonly
	 * @return static
     */
    public function setReadonly(bool $readonly)
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * @param null|string $placeholder
	 * @return static
     */
    public function setPlaceholder(?string $placeholder)
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    protected function buildInitialElement(): ?ElementInterface
    {
        return new Element("div", true);
    }

    protected function buildControl(): ElementInterface
    {
        $control = parent::buildControl();
        $control["type"] = $this->getType();
        if($this->isReadonly())
            $control["readonly"] = 'readonly';
        if($p = $this->getPlaceholder())
            $control["placeholder"] = $p;
        return $control;
    }
}