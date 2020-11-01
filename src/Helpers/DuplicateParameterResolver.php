<?php

namespace ALI\UrlTemplate\Helpers;

/**
 * Class
 */
class DuplicateParameterResolver
{
    /**
     * @var int
     */
    protected $incrementKey = 0;

    /**
     * @var array
     */
    protected $parametersAliases = [];

    /**
     * @param $parameterName
     * @return string
     */
    public function getParameterNameAlias($parameterName)
    {
        if (!isset($this->parametersAliases[$parameterName])) {
            $this->parametersAliases[$parameterName] = [];
        }
        $parameterAlias = $parameterName . '_' . $this->incrementKey++;
        $this->parametersAliases[$parameterName][] = $parameterAlias;

        return $parameterAlias;
    }

    /**
     * @param $parameterName
     * @param $matchesValues
     * @return mixed|null
     */
    public function resolverParameterNameValue($parameterName, $matchesValues)
    {
        if (isset($this->parametersAliases[$parameterName])) {
            $parameterAliases = $this->parametersAliases[$parameterName];
        } else {
            $parameterAliases = [$parameterName];
        }

        foreach ($parameterAliases as $parameterAlias) {
            if (!empty($matchesValues[$parameterAlias])) {
                return $matchesValues[$parameterAlias];
            }
        }

        return null;
    }
}
