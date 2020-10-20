<?php

namespace ALI\UrlTemplate\ParameterDecorators;

/**
 * Interface
 */
interface ParameterDecoratorInterface
{
    /**
     * @param string $decoratedParameterValue
     * @return string
     */
    public function parse($decoratedParameterValue);

    /**
     * @param string $clearParameterValue
     * @return string
     */
    public function generate($clearParameterValue);
}
