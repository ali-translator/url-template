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
     * Test url parsing
     *
     * @throws InvalidUrlException
     */
    public function testUrlParsing()
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
            ],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

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
                $urlTemplateConfig,
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
                'test.{country}.{city}.test.com',
                '/{language}/{param}/some-path-prefix/what/',
                [
                    'country' => 'pl',
                    'city' => 'berlin',
                    'language' => 'en',
                    'param' => 'ssss',
                ],
                $urlTemplateConfig,
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
            ],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://test.test.com/some-path-prefix/what/?s=1&g=2&h', [
            'country' => 'pl',
            'language' => 'en',
            'param' => 'ssssssss',
        ]);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        $expectedCompiledUrl = 'https://test.pl.test.com/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        self::assertEquals($expectedCompiledUrl, $compiledUrl);

        $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://test.test.com/some-path-prefix/what/?s=1&g=2&h', [
            'country' => 'pl',
            'param' => 'ssssssss',
            'city' => 'london',
            'language' => 'de',
        ]);
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
                ],
                true
            );
            $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

            $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://test.test.com/what/?s=1&g=2&h', [
                'country' => 'pl',
                'param' => 'ssssssss',
                'city' => 'london',
                'language' => 'de',
            ]);
            $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
            $expectedCompiledUrl = 'https://test.pl.london.test.com/de/ssssssss/what/?s=1&g=2&h';
            self::assertEquals($expectedCompiledUrl, $compiledUrl);
        }
    }

    /**
     *
     */
    public function testWithoutHiddenDefautlParameters()
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
            ],
            false
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://test.test.com/some-path-prefix/what/?s=1&g=2&h', [
            'country' => 'pl',
            'language' => 'en',
            'param' => 'ssssssss',
        ]);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        $expectedCompiledUrl = 'https://test.pl.berlin.test.com/en/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        self::assertEquals($expectedCompiledUrl, $compiledUrl);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testUrlSimplification()
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
            ],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

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
            ],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $expectCompiledUrl = '/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($expectCompiledUrl);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($expectCompiledUrl, $compiledUrl);

        $parsedUrlTemplate->setParameter('param', 'ss');
        $parsedUrlTemplate->setParameter('language', 'de');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        $expectCompiledUrl = '/de/ss/some-path-prefix/what/?s=1&g=2&h';
        self::assertEquals($expectCompiledUrl, $compiledUrl);
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
            ],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $expectCompiledUrl = '/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($expectCompiledUrl);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($expectCompiledUrl, $compiledUrl);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testFullOptionalityUrlPathTemplate()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            '{country}.test.com',
            '/{language}/{city}/',
            [
                'country' => '(tr)',
                'language' => '(en|tr)',
                'city' => '(istanbul|ankara)',
            ],
            [
                'language' => 'tr',
                'city' => 'istanbul',
            ],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $expectedCompiledUrl = 'https://tr.test.com/go/spa-v-temnote/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($expectedCompiledUrl);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($expectedCompiledUrl, $compiledUrl);

        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl('https://tr.test.com/go/spa-v-temnote/');
        $parsedUrlTemplate->setParameter('language', 'tr');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://tr.test.com/go/spa-v-temnote/', $compiledUrl);
        $parsedUrlTemplate->setParameter('language', 'en');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://tr.test.com/en/go/spa-v-temnote/', $compiledUrl);

        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl('https://tr.test.com/en/istanbul/tt/sss-v-ggg/');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://tr.test.com/en/tt/sss-v-ggg/', $compiledUrl);
    }
}
