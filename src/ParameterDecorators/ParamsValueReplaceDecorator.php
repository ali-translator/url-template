<?php

namespace ALI\UrlTemplate\ParameterDecorators;

class ParamsValueReplaceDecorator implements ParameterDecoratorInterface
{
    /**
     * @var array
     */
    protected $valuesOnUrlWithReturnValues;

    protected $returnValuesWithUrlValue;

    public function __construct(array $valuesOnUrlWithReturnValues)
    {
        $this->valuesOnUrlWithReturnValues = $valuesOnUrlWithReturnValues;
        $this->returnValuesWithUrlValue = array_combine($valuesOnUrlWithReturnValues, array_keys($valuesOnUrlWithReturnValues));
    }

    /**
     * @param string $decoratedParameterValue
     * @return string
     */
    public function parse($decoratedParameterValue)
    {
        return $this->valuesOnUrlWithReturnValues[$decoratedParameterValue] ?? $decoratedParameterValue;
    }

    /**
     * @param string $clearParameterValue
     * @return string
     */
    public function generate($clearParameterValue)
    {
        return $this->returnValuesWithUrlValue[$clearParameterValue] ?? $clearParameterValue;
    }
}
