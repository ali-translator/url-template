<?php

namespace ALI\UrlTemplateTests\unit\UrlTemplateResolver;

use ALI\UrlTemplate\ParameterDecorators\WrapperParameterDecorator;
use ALI\UrlTemplate\UrlTemplateConfig;
use ALI\UrlTemplate\UrlTemplateResolver;
use PHPUnit\Framework\TestCase;

/**
 * Class
 */
class UrlTemplateResolver_WithDecorators_Test extends TestCase
{
    /**
     * ./vendor/bin/phpunit --filter testPath ./tests/unit/UrlTemplateResolver/UrlTemplateResolver_WithDecorators_Test.php -vvv
     */
    public function testPath()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            null,
            '/{country}{language}/',
            [
                'country' => '(uk|ua|gb|pl)',
                'language' => ['ua', 'en', 'de'],
            ],
            [
                'city' => 'berlin',
                'language' => 'en',
            ],
            true,
            [
                'language' => new WrapperParameterDecorator('-'),
            ]
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $originalUrl = 'http://test.com/gb/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($originalUrl);

        $parsedUrlTemplate->setParameter('language', 'de');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('http://test.com/gb-de/', $compiledUrl);

        $parsedUrlTemplate->setParameter('language', 'en');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($originalUrl, $compiledUrl);
    }

    /**
     * ./vendor/bin/phpunit --filter testHost ./tests/unit/UrlTemplateResolver_WithDecorators_Test.php -vvv
     *
     * @throws \ALI\UrlTemplate\Exceptions\InvalidUrlException
     */
    public function testHost()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            '{country}{language}.test.com',
            null,
            [
                'country' => '(uk|ua|gb|pl)',
                'language' => ['ua', 'en', 'de'],
            ],
            [
                'city' => 'berlin',
                'language' => 'en',
            ],
            true,
            [
                'language' => new WrapperParameterDecorator('-'),
            ]
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $originalUrl = 'http://gb.test.com/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($originalUrl);

        $parsedUrlTemplate->setParameter('language', 'de');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('http://gb-de.test.com/', $compiledUrl);

        $parsedUrlTemplate->setParameter('language', 'en');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($originalUrl, $compiledUrl);
    }
}
