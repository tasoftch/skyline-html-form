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


use InvalidArgumentException;
use PDO;
use Skyline\HTML\Form\Validator\Condition\ConditionInterface;
use TASoft\Service\ServiceManager;

class UniqueSQLColumnValidator extends AbstractConditionalValidator
{
    /** @var PDO */
    private $PDO;
    /** @var string */
    private $fieldName;
    /** @var string */
    private $tableName;

    /** @var array  */
    private $ignoredFields = [];

    public function __construct($PDO, string $tableName, string $fieldName, ConditionInterface $condition = NULL)
    {
        parent::__construct($condition);
        if(is_string($PDO)) {
            $PDO = ServiceManager::generalServiceManager()->get($PDO);
        }

        if($PDO instanceof PDO)
            $this->PDO = $PDO;
        else
            throw new InvalidArgumentException(__METHOD__ . " requires a PDO object");

        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
    }

    public function validateValue($value)
    {
        $filter = 1;
        if($ignored = $this->getIgnoredFields()) {
            $filter = [];
            foreach($ignored as $fieldName => $values) {
                if(is_iterable($values)) {
                    foreach($values as $value)
                        $filter[] = "`$fieldName` != " . $this->getPDO()->quote($value);
                } else {
                    $filter[] = "`$fieldName` != " . $this->getPDO()->quote($values);
                }
            }
            $filter = implode(" AND ", $filter);
        }

        $sql = sprintf("SELECT count(*) AS CNT FROM %s WHERE %s AND `%s` = ?", $this->getTableName(), $filter, $this->getFieldName());
        $stmt = $this->getPDO()->prepare($sql);
        if($stmt->execute([$value])) {
            if(($stmt->fetch(PDO::FETCH_ASSOC)["CNT"] ?? 0) == 0)
                return true;
        }
        return false;
    }

    /**
     * @param string $fieldName
     * @return UniqueSQLColumnValidator
     */
    public function setFieldName(string $fieldName): UniqueSQLColumnValidator
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Sets columns with values that should be excluded
     * Example: ['id' => 'my-id'] or ['id' => ["id-1", "id-2"]]
     *
     * Use ignored fields for editing because then one record has a duplicate
     *
     * @param array $ignoredFields
     * @return UniqueSQLColumnValidator
     */
    public function setIgnoredFields(array $ignoredFields): UniqueSQLColumnValidator
    {
        $this->ignoredFields = $ignoredFields;
        return $this;
    }

    /**
     * @return array
     */
    public function getIgnoredFields(): array
    {
        return $this->ignoredFields;
    }

    /**
     * @param string $tableName
     * @return UniqueSQLColumnValidator
     */
    public function setTableName(string $tableName): UniqueSQLColumnValidator
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->PDO;
    }

}