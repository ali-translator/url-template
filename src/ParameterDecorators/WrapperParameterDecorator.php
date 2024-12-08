<?php

namespace ALI\UrlTemplate\ParameterDecorators;

class WrapperParameterDecorator implements ParameterDecoratorInterface
{
    protected string $prefix;
    protected string $postfix;

    public function __construct(string $prefix = '', string $postfix = '')
    {
        $this->prefix = $prefix;
        $this->postfix = $postfix;
    }

    public function parse(?string $decoratedParameterValue): string
    {
        $regularExpression = '';
        if ($this->prefix) {
            $regularExpression .= '^' . preg_quote($this->prefix, '/');
        }
        $regularExpression .= '(.*)';
        if ($this->postfix) {
            $regularExpression .= preg_quote($this->postfix, '/') . '$';
        }

        return preg_replace('/'.$regularExpression.'/', '$1', $decoratedParameterValue);
    }

    public function generate(?string $clearParameterValue): string
    {
        return $this->prefix . $clearParameterValue . $this->postfix;
    }
}
