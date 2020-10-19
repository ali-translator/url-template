<?php

namespace ALI\UrlTemplate;

use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use PHPUnit\Framework\TestCase;

/**
 * Class
 */
class UrlTemplateResolverTest extends TestCase
{
    /**
     * @var UrlTemplateConfig
     */
    private $urlTemplateConfig;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $this->urlTemplateConfig = new UrlTemplateConfig(
            '{country}.{city}.test.com',
            '/{language}/{param}/some-path-prefix/',
            [
                'country' => '(uk|ua|gb|pl)',
                'language' => '[a-z]{2}', // be careful with some free regular expressions
                'city' => '(kiev|berlin|paris|london)',
                'param' => 's+',
            ],
            [
                'city' => 'berlin',
                'language' => 'en',
            ]
        );
    }

    /**
     * Test url parsing
     *
     * @throws InvalidUrlException
     */
    public function testUrlParsing()
    {
        $urlTemplateResolver = new UrlTemplateResolver($this->urlTemplateConfig);

        // Testing correct url, with all parameters in url
        {
            $expectParsedUrlTemplate = new ParsedUrlTemplate(
                'test.{country}.{city}.test.com',
                '/{language}/{param}/some-path-prefix/what/',
                [
                    'country' => 'pl',
                    'language' => 'de',
                    'city' => 'paris',
                    'param' => 'ssss',
                ],
                $this->urlTemplateConfig,
                [
                    'scheme' => 'https',
                ]
            );
            $expectedCompileUrl = 'https://test.pl.paris.test.com/de/ssss/some-path-prefix/what/';
            $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($expectedCompileUrl);
            self::assertEquals($expectParsedUrlTemplate, $parsedUrlTemplate);
            $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
            self::assertEquals($expectedCompileUrl, $compiledUrl);
        }

        // Testing correct url with empty some optionality parameters
        {
            $expectParsedUrlTemplate = new ParsedUrlTemplate(
                'test.{country}.test.com',
                '/{param}/some-path-prefix/what/',
                [
                    'country' => 'pl',
                    'city' => 'berlin',
                    'language' => 'en',
                    'param' => 'ssss',
                ],
                $this->urlTemplateConfig,
                [
                    'scheme' => 'https',
                    'query' => 's=1&g=1',
                ]
            );
            $expectedCompileUrl = 'https://test.pl.test.com/ssss/some-path-prefix/what/?s=1&g=1';
            $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($expectedCompileUrl);
            self::assertEquals($expectParsedUrlTemplate, $parsedUrlTemplate);
            $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
            self::assertEquals($expectedCompileUrl, $compiledUrl);
        }

        // Testing Exception. Host url without required parameter "country"
        {
            $exception = null;
            try {
                $urlTemplateResolver->parseCompiledUrl('https://test.paris.test.com/de/ssss/some-path-prefix/what/');
            } catch (InvalidUrlException $exception) {
            }
            self::assertEquals(get_class($exception), InvalidUrlException::class);
        }

        // Testing Exception. Path url without required parameter "city"
        {
            $exception = null;
            try {
                $urlTemplateResolver->parseCompiledUrl('https://test.pl.paris.test.com/de/some-path-prefix/what/');
            } catch (InvalidUrlException $exception) {
            }
            self::assertEquals(get_class($exception), InvalidUrlException::class);
        }
    }

    /**
     *
     */
    public function testGeneratingParsedUrlTemplate()
    {
        $urlTemplateResolver = new UrlTemplateResolver($this->urlTemplateConfig);

        $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://test.test.com/some-path-prefix/what/?s=1&g=2&h', [
            'country' => 'pl',
            'language' => 'en',
            'param' => 'ssssssss',
        ], true);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        $expectedCompiledUrl = 'https://test.pl.test.com/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        self::assertEquals($expectedCompiledUrl, $compiledUrl);

        $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://test.test.com/some-path-prefix/what/?s=1&g=2&h', [
            'country' => 'pl',
            'language' => 'en',
            'param' => 'ssssssss',
        ], false);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        $expectedCompiledUrl = 'https://test.pl.berlin.test.com/en/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        self::assertEquals($expectedCompiledUrl, $compiledUrl);

        $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://test.test.com/some-path-prefix/what/?s=1&g=2&h', [
            'country' => 'pl',
            'param' => 'ssssssss',
            'city' => 'london',
            'language' => 'de',
        ], true);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        $expectedCompiledUrl = 'https://test.pl.london.test.com/de/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        self::assertEquals($expectedCompiledUrl, $compiledUrl);


        // Test path without static prefix
        {
            $urlTemplateConfig = new UrlTemplateConfig(
                '{country}.{city}.test.com',
                '/{language}/{param}',
                [
                    'country' => '(uk|ua|gb|pl)',
                    'language' => '[a-z]{2}', // be careful with some free regular expressions
                    'city' => '(kiev|berlin|paris|london)',
                    'param' => 's+',
                ],
                [
                    'city' => 'berlin',
                    'language' => 'en',
                ]
            );
            $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

            $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://test.test.com/what/?s=1&g=2&h', [
                'country' => 'pl',
                'param' => 'ssssssss',
                'city' => 'london',
                'language' => 'de',
            ], true);
            $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
            $expectedCompiledUrl = 'https://test.pl.london.test.com/de/ssssssss/what/?s=1&g=2&h';
            self::assertEquals($expectedCompiledUrl, $compiledUrl);
        }
    }

    /**
     * @throws InvalidUrlException
     */
    public function testUrlSimplification()
    {
        $urlTemplateResolver = new UrlTemplateResolver($this->urlTemplateConfig);

        $compiledUrl = 'https://test.pl.test.com/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        $parsedUrl = $urlTemplateResolver->parseCompiledUrl($compiledUrl);
        $simplifiedUrl = $urlTemplateResolver->getSimplifiedUrl($parsedUrl);
        self::assertEquals('https://test.test.com/some-path-prefix/what/?s=1&g=2&h', $simplifiedUrl);

        $compiledUrl = 'https://test.pl.berlin.test.com/en/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        $parsedUrl = $urlTemplateResolver->parseCompiledUrl($compiledUrl);
        $simplifiedUrl = $urlTemplateResolver->getSimplifiedUrl($parsedUrl);
        self::assertEquals('https://test.test.com/some-path-prefix/what/?s=1&g=2&h', $simplifiedUrl);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testRelativeUrlsWithAbsoluteConfig()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            '{country}.{city}.test.com',
            '/{language}/{param}/some-path-prefix/',
            [
                'country' => '(uk|ua|gb|pl)',
                'language' => '[a-z]{2}', // be careful with some free regular expressions
                'city' => '(kiev|berlin|paris|london)',
                'param' => 's+',
            ],
            [
                'city' => 'berlin',
                'language' => 'en',
            ]
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $exactCompiledUrl = '/en/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($exactCompiledUrl);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($exactCompiledUrl, $compiledUrl);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testRelativeUrlsWithRelativeUrlConfig()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            null,
            '/{language}/{param}/',
            [
                'country' => '(uk|ua|gb|pl)',
                'language' => '[a-z]{2}', // be careful with some free regular expressions
                'city' => '(kiev|berlin|paris|london)',
                'param' => 's+',
            ],
            [
                'city' => 'berlin',
                'language' => 'en',
            ]
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $exactCompiledUrl = '/en/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($exactCompiledUrl);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($exactCompiledUrl, $compiledUrl);
    }
}
