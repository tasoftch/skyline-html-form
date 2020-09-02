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

namespace Skyline\HTML\Form\Validator;


use Skyline\HTML\Form\Validator\Condition\ConditionInterface;

class InRangeValidator extends IsNumericValidator
{
	private $minimum;
	private $maximum;

	/**
	 * InRangeValidator constructor.
	 * @param int $minimum
	 * @param int $maximum
	 * @param ConditionInterface|null $condition
	 */
	public function __construct($minimum = 0, $maximum = 1, ConditionInterface $condition = NULL)
	{
		parent::__construct($condition);
		$this->minimum = $minimum * 1;
		$this->maximum = $maximum * 1;
	}

	public function validateValue($value)
	{
		if(parent::validateValue($value)) {
			if($value >= $this->getMinimum() && $value <= $this->getMaximum())
				return true;
		}
		return false;
	}

	/**
	 * @return float|int
	 */
	public function getMinimum()
	{
		return $this->minimum;
	}

	/**
	 * @return float|int
	 */
	public function getMaximum()
	{
		return $this->maximum;
	}
}