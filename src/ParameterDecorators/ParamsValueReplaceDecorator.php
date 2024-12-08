<?php

namespace ALI\UrlTemplate\ParameterDecorators;

class ParamsValueReplaceDecorator implements ParameterDecoratorInterface
{
    protected array $valuesOnUrlWithReturnValues;
    protected array $returnValuesWithUrlValue;

    public function __construct(array $valuesOnUrlWithReturnValues)
    {
        $this->valuesOnUrlWithReturnValues = $valuesOnUrlWithReturnValues;
        $this->returnValuesWithUrlValue = array_combine($valuesOnUrlWithReturnValues, array_keys($valuesOnUrlWithReturnValues));
    }

    public function parse(?string $decoratedParameterValue): string
    {
        return (string)($this->valuesOnUrlWithReturnValues[$decoratedParameterValue] ?? $decoratedParameterValue);
    }

    public function generate(?string $clearParameterValue): string
    {
        return (string)($this->returnValuesWithUrlValue[$clearParameterValue] ?? $clearParameterValue);
    }
}
