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

namespace Skyline\HTML\Form\Control\Button;


use Skyline\HTML\ElementInterface;
use Skyline\HTML\Form\Control\AbstractControl;
use Skyline\HTML\Form\Control\ActionControlInterface;
use Skyline\HTML\HTMLContentElement;
use Skyline\Render\Context\RenderContextInterface;

class ButtonControl extends AbstractControl implements ActionControlInterface
{
    const TYPE_SUBMIT = 'submit';
    const TYPE_RESET = 'reset';
    const TYPE_CUSTOM = 'button';

    /** @var string */
    private $type;

	public static function create(string $name, string $identifier = NULL)
	{
		return new static($name, self::TYPE_CUSTOM, $identifier);
	}

	public function __construct(string $name, string $type = self::TYPE_CUSTOM, string $identifier = NULL)
    {
        parent::__construct($name, $identifier);
        $this->type = $type;
    }

    /**
     * @var mixed
     */
    private $content;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
	 * @return static
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    protected function buildControlElementInstance(): ElementInterface
    {
        return new HTMLContentElement("button");
    }

    protected function buildControl(): ElementInterface
    {
        /** @var HTMLContentElement $control */
        $control = parent::buildControl();
        $control["type"] = $this->getType();
        $control->setContent( $this->getContent() );
        return $control;
    }

    protected function buildFinalContainer(ElementInterface $container, ElementInterface $control, ?RenderContextInterface $context, $info)
    {
        $container->appendElement($control);
    }

    protected function skipValidation(): bool
    {
        return true;
    }

    public function performAction($data): bool
    {
        trigger_error("You need to subclass the ButtonControl or use ActionButtonControl instead", E_USER_WARNING);
        return false;
    }
}