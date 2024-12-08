<?php

namespace ALI\UrlTemplate\ParameterDecorators;

interface ParameterDecoratorInterface
{
    public function parse(?string $decoratedParameterValue): string;
    public function generate(?string $clearParameterValue): string;
}
