<?php

namespace ALI\UrlTemplate;

use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\ParameterDecorators\WrapperParameterDecorator;
use PHPUnit\Framework\TestCase;

/**
 * Class
 */
class UrlTemplateResolverTest extends TestCase
{
    /**
     * Test url parsing
     *
     * ./vendor/bin/phpunit --filter testUrlParsing ./tests/unit/UrlTemplateResolverTest.php -vvv
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

        $compiledUrl = 'https://test.pl.berlin.test.com/en/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        $parsedUrl = $urlTemplateResolver->parseCompiledUrl($compiledUrl);
        $simplifiedUrlData = $urlTemplateResolver->getSimplifiedUrlData($parsedUrl);
        self::assertEquals('/what/', $simplifiedUrlData['path']);

        $compiledUrl = 'https://test.pl.berlin.test.com/en/ssssssss/some-path-prefix/what/?s=1&g=2&h';
        $parsedUrl = $urlTemplateResolver->parseCompiledUrl($compiledUrl);
        $compileUrl = $urlTemplateResolver->compileUrl($parsedUrl, $urlTemplateResolver::COMPILE_TYPE_HOST);
        self::assertEquals('test.pl.test.com', $compileUrl);
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
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate, $urlTemplateResolver::COMPILE_TYPE_PATH);
        self::assertEquals($expectCompiledUrl, $compiledUrl);

        $parsedUrlTemplate->setParameter('country', 'uk');
        $parsedUrlTemplate->setParameter('city', 'berlin');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('//uk.test.com' . $expectCompiledUrl, $compiledUrl);

        $expectCompiledUrl = '/de/ss/some-path-prefix/what/?s=1&g=2&h';
        $parsedUrlTemplate->setParameter('param', 'ss');
        $parsedUrlTemplate->setParameter('language', 'de');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate, $urlTemplateResolver::COMPILE_TYPE_PATH);
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

    /**
     * @throws InvalidUrlException
     */
    public function testOptionalityDefaultParameters()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            '{country}.test.com',
            '/{language}/',
            [
                'country' => '(tr|gb)',
                'language' => '(en|tr|de)',
            ],
            [
                'language' => function ($requiredParameters) {
                    switch ($requiredParameters['country']) {
                        case 'tr':
                            return 'tr';
                            break;
                        case 'gb':
                            return 'en';
                            break;
                        default:
                            throw new \Exception('Invalid country alias');
                            break;
                    }
                },
            ],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $originalUrl = 'https://tr.test.com/tt/sss-v-ggg/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($originalUrl);
        self::assertEquals('tr', $parsedUrlTemplate->getParameter('language'));
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($originalUrl, $compiledUrl);
        // default language stay unchanged
        $parsedUrlTemplate->setParameter('country', 'gb');
        self::assertEquals('tr', $parsedUrlTemplate->getParameter('language'));
        // new compiled url
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://gb.test.com/tr/tt/sss-v-ggg/', $compiledUrl);

        $originalUrl = 'https://gb.test.com/tt/sss-v-ggg/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($originalUrl);
        self::assertEquals('en', $parsedUrlTemplate->getParameter('language'));
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($originalUrl, $compiledUrl);
        // default language stay unchanged
        $parsedUrlTemplate->setParameter('country', 'tr');
        self::assertEquals('en', $parsedUrlTemplate->getParameter('language'));
        // new compiled url
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://tr.test.com/en/tt/sss-v-ggg/', $compiledUrl);
    }

    /**
     * ./vendor/bin/phpunit --filter testFewParametersOnOneNameSpacePath ./tests/unit/UrlTemplateResolverTest.php -vvv
     *
     * @throws InvalidUrlException
     */
    public function testFewParametersOnOneNameSpacePath()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            null,
            '/{country}{language}/',
            [
                'country' => '(tr|gb)',
                'language' => '(-en|-tr|-de)',
            ],
            [
                'language' => function ($requiredParameters) {
                    switch ($requiredParameters['country']) {
                        case 'tr':
                            return '-tr';
                            break;
                        case 'gb':
                            return '-en';
                            break;
                        default:
                            throw new \Exception('Invalid country alias');
                            break;
                    }
                },
            ],
            ['language']
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        // Path
        $originalUrl = 'https://test.com/gb-de/tt/sss-v-ggg/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($originalUrl);
        $parsedUrlTemplate->setParameter('language', '-tr');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://test.com/gb-tr/tt/sss-v-ggg/', $compiledUrl);

        $parsedUrlTemplate->setParameter('language', '-en');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://test.com/gb/tt/sss-v-ggg/', $compiledUrl);
    }

    /**
     * @throws InvalidUrlException
     */
    public function testFewParametersOnOneNameSpacePathAndOneOnTheMiddle()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            null,
            '/{country}{language}-{param}/',
            [
                'country' => '(tr|gb)',
                'language' => '(\-en|\-tr|\-de)',
                'param' => '[a-z]{2}',
            ],
            [
                'language' => function ($requiredParameters) {
                    switch ($requiredParameters['country']) {
                        case 'tr':
                            return '-tr';
                            break;
                        case 'gb':
                            return '-en';
                            break;
                        default:
                            throw new \Exception('Invalid country alias');
                            break;
                    }
                },
            ],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        // Path
        $originalUrl = 'https://test.com/gb-de-ss/tt/sss-v-ggg/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($originalUrl);
        $parsedUrlTemplate->setParameter('language', '-tr');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://test.com/gb-tr-ss/tt/sss-v-ggg/', $compiledUrl);

        $parsedUrlTemplate->setParameter('language', '-en');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://test.com/gb-ss/tt/sss-v-ggg/', $compiledUrl);

        $urlTemplateConfig = new UrlTemplateConfig(
            null,
            '/{country}{language}-{param}/',
            [
                'country' => '(tr|gb)',
                'language' => '(-en|-tr|-de)',
                'param' => '[a-z]{2}',
            ],
            [],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        // Without default parameter
        $originalUrl = 'https://test.com/gb-de-ss/tt/sss-v-ggg/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($originalUrl);
        $parsedUrlTemplate->setParameter('language', '-tr');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://test.com/gb-tr-ss/tt/sss-v-ggg/', $compiledUrl);

        $parsedUrlTemplate->setParameter('language', '-en');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://test.com/gb-en-ss/tt/sss-v-ggg/', $compiledUrl);
    }


    /**
     * ./vendor/bin/phpunit --filter testFewParametersOnOneNameSpaceHost ./tests/unit/UrlTemplateResolverTest.php -vvv
     *
     * @throws InvalidUrlException
     */
    public function testFewParametersOnOneNameSpaceHost()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            '{country}{language}.test.com',
            null,
            [
                'country' => '(tr|gb)',
                'language' => '(-en|-tr|-de)',
            ],
            [
                'language' => function ($requiredParameters) {
                    switch ($requiredParameters['country']) {
                        case 'tr':
                            return '-tr';
                            break;
                        case 'gb':
                            return '-en';
                            break;
                        default:
                            throw new \Exception('Invalid country alias');
                            break;
                    }
                },
            ],
            ['language']
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        // Path
        $originalUrl = 'https://gb-de.test.com/tt/sss-v-ggg/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($originalUrl);
        $parsedUrlTemplate->setParameter('language', '-tr');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://gb-tr.test.com/tt/sss-v-ggg/', $compiledUrl);

        $parsedUrlTemplate->setParameter('language', '-en');
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals('https://gb.test.com/tt/sss-v-ggg/', $compiledUrl);
    }

    /**
     * ./vendor/bin/phpunit --filter testListedDefaultParameters ./tests/unit/UrlTemplateResolverTest.php -vvv
     *
     * @throws InvalidUrlException
     */
    public function testListedDefaultParameters()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            '{country}.{domain}.com',
            '/{language}/{city}/',
            [
                'country' => '(tr)',
                'domain' => '[a-z]+',
                'language' => '(en|tr)',
                'city' => '(istanbul|ankara)',
            ],
            [
                'language' => 'tr',
                'city' => 'istanbul',
                'domain' => 'test',
            ],
            ['domain', 'language']
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $expectedCompiledUrl = 'https://tr.com/istanbul/go/spa-v-temnote/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($expectedCompiledUrl);
        $compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
        self::assertEquals($expectedCompiledUrl, $compiledUrl);

        // Testing Exception. Try put url with skipped "city" parameter
        {
            $exception = null;
            try {
                $urlTemplateResolver->parseCompiledUrl('https://tr.com/go/spa-v-temnote/');
            } catch (InvalidUrlException $exception) {
            }
            self::assertEquals(get_class($exception), InvalidUrlException::class);
        }
    }

    /**
     * ./vendor/bin/phpunit --filter testGenerateParsedUrlTemplate ./tests/unit/UrlTemplateResolverTest.php -vvv
     */
    public function testGenerateParsedUrlTemplate()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            '{country}.test.com',
            '/{language}/{city}/',
            [
                'country' => '(tr|uk)',
                'language' => '(en|tr)',
                'city' => '(istanbul|ankara)',
            ],
            [
                'language' => 'tr',
                'city' => 'istanbul',
            ],
            ['city']
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        // Without end slash
        $simplifiedUrl = 'http://test.com/';
        $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate($simplifiedUrl, []);
        $parsedUrlTemplate->setParameters([
            'country' => 'tr',
            'language' => 'en',
        ]);
        $compiledUrlPath = $urlTemplateResolver->compileUrl($parsedUrlTemplate, $urlTemplateResolver::COMPILE_TYPE_HOST);
        self::assertEquals('tr.test.com', $compiledUrlPath);
        $compiledUrlHost = $urlTemplateResolver->compileUrl($parsedUrlTemplate, $urlTemplateResolver::COMPILE_TYPE_PATH);
        self::assertEquals('/en/', $compiledUrlHost);

        // Without end slash
        $simplifiedUrl = 'http://test.com';
        $parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate($simplifiedUrl, []);
        $parsedUrlTemplate->setParameters([
            'country' => 'tr',
            'language' => 'en',
            'city' => 'ankara',
        ]);
        $compiledUrlPath = $urlTemplateResolver->compileUrl($parsedUrlTemplate, $urlTemplateResolver::COMPILE_TYPE_HOST);
        self::assertEquals('tr.test.com', $compiledUrlPath);
        $compiledUrlHost = $urlTemplateResolver->compileUrl($parsedUrlTemplate, $urlTemplateResolver::COMPILE_TYPE_PATH);
        self::assertEquals('/en/ankara/', $compiledUrlHost);
    }

    /**
     * ./vendor/bin/phpunit --filter testUrlsWithBugs ./tests/unit/UrlTemplateResolverTest.php -vvv
     */
    public function testUrlsWithBugs()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            'www.test.com',
            '/{country}{language}/{city}/{a}{b}{c}{d}/',
            [
                'country' => ['gb'],
                'language' => ['en'],
                'city' => 'london',
                'a' => ['a'],
                'b' => ['b'],
                'c' => ['c'],
                'd' => ['d'],
            ],
            [
                'language' => 'en',
                'city' => 'london',
            ],
            ['language', 'city', 'a', 'b', 'c', 'd'],
            [
                'language' => new WrapperParameterDecorator('-'),
            ]
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        // filled some of optionality parameters on one namespace
        $compiledUrl = 'http://www.test.com/gb-en/d/some-path/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($compiledUrl);
        self::assertEquals(null, $parsedUrlTemplate->getParameter('a'));
        self::assertEquals('d', $parsedUrlTemplate->getParameter('d'));

        // without exceptions
        $compiledUrl = 'http://www.test.com/gb-en/some-path/';
        $urlTemplateResolver->parseCompiledUrl($compiledUrl);

        // Testing Exception. Host url without required parameter "country"
        {
            $exception = null;
            try {
                $urlTemplateResolver->parseCompiledUrl('http://www.test.com/ua-ss/some-path/');
            } catch (InvalidUrlException $exception) {
            }
            self::assertEquals(get_class($exception), InvalidUrlException::class);
        }
    }

    /**
     * ./vendor/bin/phpunit --filter testExcessiveOwnParameters ./tests/unit/UrlTemplateResolverTest.php -vvv
     */
    public function testExcessiveOwnParameters()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            '{city}.test.com',
            '/{country}/{language}/',
            [
                'country' => ['gb'],
                'language' => ['en'],
                'city' => 'london',
            ],
            [
                'language' => 'en',
                'city' => 'london',
            ],
            ['language', 'city', 'a', 'b']
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $url = 'https://london.test.com/gb/en/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($url);
        $excessiveOwnParameters = $parsedUrlTemplate->getExcessiveOwnParameters();
        $this->assertEquals([
            'city' => 'london',
            'language' => 'en',
        ], $excessiveOwnParameters);

        $url = 'https://test.com/gb/en/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($url);
        $excessiveOwnParameters = $parsedUrlTemplate->getExcessiveOwnParameters();
        $this->assertEquals([
            'language' => 'en',
        ], $excessiveOwnParameters);

        $url = 'https://test.com/gb/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($url);
        $excessiveOwnParameters = $parsedUrlTemplate->getExcessiveOwnParameters();
        $this->assertEquals([], $excessiveOwnParameters);

        $url = 'https://test.com/gb/test/';
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($url);
        $excessiveOwnParameters = $parsedUrlTemplate->getExcessiveOwnParameters();
        $this->assertEquals([], $excessiveOwnParameters);
    }
}
