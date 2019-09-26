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

namespace Skyline\HTML\Form\Style;


abstract class AbstractStyleMap implements StyleMapInterface
{
    protected $styles;

    /**
     * @param string $style
     * @return string|null
     */
    public function getStyleClass(string $style): ?string
    {
        return $this->getStyles()[$style] ?? NULL;
    }

    /**
     * @param array $styles
     * @return string|null
     */
    public function getStyleClasses(array $styles): ?string
    {
        foreach($styles as &$style) {
            $style = $this->getStyleClass($style);
        }
        $styles = array_filter($styles, function($v) { return $v ? true : false; });
        return implode(" ", $styles);
    }

    /**
     * Checks if a style exists in the map
     *
     * @param $name
     * @return bool
     */
    public function hasStyle($name) {
        return isset($this->styles[$name]);
    }

    /**
     * Get all styles
     * @return array
     */
    public function getStyles(): array {
        if(NULL === $this->styles)
            $this->styles = $this->loadStyles();
        return $this->styles;
    }

    /**
     * Loads the build-in styles
     * This method is only called once
     *
     * @return array
     */
    abstract protected function loadStyles(): array;
}