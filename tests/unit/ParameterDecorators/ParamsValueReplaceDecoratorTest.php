<?php

namespace ALI\UrlTemplate\Tests\unit\ParameterDecorators;

use ALI\UrlTemplate\ParameterDecorators\ParamsValueReplaceDecorator;
use PHPUnit\Framework\TestCase;

/**
 * Class
 */
class ParamsValueReplaceDecoratorTest extends TestCase
{
    public function test()
    {
        $parameterDecorator = new ParamsValueReplaceDecorator([
            'ua' => 'uk'
        ]);

        $decoratedParameterValue = $parameterDecorator->parse('ua');
        self::assertEquals('uk', $decoratedParameterValue);

        $clearParameterName = $parameterDecorator->generate('uk');
        self::assertEquals('ua', $clearParameterName);

        $decoratedParameterValue = $parameterDecorator->parse('en');
        self::assertEquals('en', $decoratedParameterValue);

        $clearParameterName = $parameterDecorator->generate('en');
        self::assertEquals('en', $clearParameterName);
    }
}
