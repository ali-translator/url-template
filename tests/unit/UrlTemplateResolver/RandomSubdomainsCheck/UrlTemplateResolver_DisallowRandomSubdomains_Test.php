<?php

namespace ALI\UrlTemplateTests\unit\UrlTemplateResolver\RandomSubdomainsCheck;

use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\UrlTemplateConfig;
use ALI\UrlTemplate\UrlTemplateResolver;
use PHPUnit\Framework\TestCase;

class UrlTemplateResolver_DisallowRandomSubdomains_Test extends TestCase
{
    /**
     * ./vendor/bin/phpunit ./tests/unit/UrlTemplateResolver/RandomSubdomainsCheck/UrlTemplateResolver_DisallowRandomSubdomains_Test.php -vvv
     */
    public function testLessDomainLength()
    {
        $compiledUrlParser = $this->generateCompiledParser();

        $this->expectException(InvalidUrlException::class);
        $compiledUrlParser->parseCompiledUrl('http://test.com/');
    }

    public function testCorrectDomain()
    {
        $compiledUrlParser = $this->generateCompiledParser();

        $parsedUrlTemplate = $compiledUrlParser->parseCompiledUrl('http://api.test.com/');
        $this->assertIsObject($parsedUrlTemplate);
    }

    public function testDomainWithRandomSubdomain()
    {
        $compiledUrlParser = $this->generateCompiledParser();

        $this->expectException(InvalidUrlException::class);
        $compiledUrlParser->parseCompiledUrl('http://london.api.test.com/');
    }

    public function generateCompiledParser(): UrlTemplateResolver
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            '{subdomain}.test.com',
            '/{language}/',
            [
                'language' => ['ua', 'en', 'de'],
                'subdomain' => '\w+'
            ],
            ['language' => 'en'],
            true
        );
        $urlTemplateConfig->setIsAllowedSubdomains(false);

        return new UrlTemplateResolver($urlTemplateConfig);
    }
}