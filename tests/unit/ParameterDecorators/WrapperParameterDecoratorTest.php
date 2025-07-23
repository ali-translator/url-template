<?php

namespace ALI\UrlTemplateTests\unit\ParameterDecorators;

use ALI\UrlTemplate\ParameterDecorators\WrapperParameterDecorator;
use PHPUnit\Framework\TestCase;

class WrapperParameterDecoratorTest extends TestCase
{
    public function testDecoratorWithPrefixAndPostfix()
    {
        $parameterDecorator = new WrapperParameterDecorator('-', '_');

        $parameterName = '-_en-_';
        $decoratedParameterValue = $parameterDecorator->generate($parameterName);
        self::assertEquals('-' . $parameterName . '_', $decoratedParameterValue);

        $clearParameterName = $parameterDecorator->parse($decoratedParameterValue);
        self::assertEquals($parameterName, $clearParameterName);
    }

    public function testDecoratorWithPrefix()
    {
        $parameterDecorator = new WrapperParameterDecorator('-', '');

        $parameterName = '-_en-_';
        $decoratedParameterValue = $parameterDecorator->generate($parameterName);
        self::assertEquals('-' . $parameterName, $decoratedParameterValue);

        $clearParameterName = $parameterDecorator->parse($decoratedParameterValue);
        self::assertEquals($parameterName, $clearParameterName);
    }

    public function testDecoratorWithPostfix()
    {
        $parameterDecorator = new WrapperParameterDecorator('', '_');

        $parameterName = '-_en-_';
        $decoratedParameterValue = $parameterDecorator->generate($parameterName);
        self::assertEquals($parameterName . '_', $decoratedParameterValue);

        $clearParameterName = $parameterDecorator->parse($decoratedParameterValue);
        self::assertEquals($parameterName, $clearParameterName);
    }
}
