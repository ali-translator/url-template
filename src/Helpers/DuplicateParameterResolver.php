<?php

namespace ALI\UrlTemplate\Helpers;

class DuplicateParameterResolver
{
    protected int $incrementKey = 0;
    protected array $parametersAliases = [];

    public function getParameterNameAlias(string $parameterName): string
    {
        if (!isset($this->parametersAliases[$parameterName])) {
            $this->parametersAliases[$parameterName] = [];
        }
        $parameterAlias = $parameterName . '_' . $this->incrementKey++;
        $this->parametersAliases[$parameterName][] = $parameterAlias;

        return $parameterAlias;
    }

    /**
     * @return mixed|null
     */
    public function resolverParameterNameValue(string $parameterName, array $matchesValues)
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
