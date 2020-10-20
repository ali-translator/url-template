<?php

namespace ALI\UrlTemplate\ParameterDecorators;

/**
 * Class
 */
class WrapperParameterDecorator implements ParameterDecoratorInterface
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $postfix;

    /**
     * @param string $prefix
     * @param string $postfix
     */
    public function __construct($prefix = '', $postfix = '')
    {
        $this->prefix = $prefix;
        $this->postfix = $postfix;
    }

    /**
     * @inheritDoc
     */
    public function parse($decoratedParameterValue)
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

    /**
     * @inheritDoc
     */
    public function generate($clearParameterValue)
    {
        return $this->prefix . $clearParameterValue . $this->postfix;
    }
}
