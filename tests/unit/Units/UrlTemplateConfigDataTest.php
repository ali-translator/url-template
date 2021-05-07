<?php

namespace ALI\UrlTemplate\Tests\unit\Units;

use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\UrlTemplateConfig;
use ALI\UrlTemplate\UrlTemplateResolver;
use PHPUnit\Framework\TestCase;

class UrlTemplateConfigDataTest extends TestCase
{
    /**
     * ./vendor/bin/phpunit ./tests/unit/Units/UrlTemplateConfigDataTest.php -vvv --verbose
     */
    public function test()
    {
        $urlTemplateConfig = new UrlTemplateConfig(
            'test.com',
            '/{country}/',
            [
                'country' => '(uk|ua)',
            ],
            [],
            true,
            []
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl('http://test.com/uk/');
        self::assertEquals('uk', $parsedUrlTemplate->getParameter('country'));

        // Incorrect country
        {
            $exception = null;
            try {
                $urlTemplateResolver->parseCompiledUrl('http://test.com/gb/');
            } catch (InvalidUrlException $exception) {
            }
            self::assertEquals(get_class($exception), InvalidUrlException::class);
        }

        // Change country requirements
        $urlTemplateConfigData = $urlTemplateConfig->generateUrlTemplateConfigData();
        $urlTemplateConfigData->setParametersRequirements([
                'country' => '(gb|ru)'
        ] + $urlTemplateConfigData->getParametersRequirements());

        // Incorrect country on previous config (check that we change only new object)
        {
            $exception = null;
            try {
                $urlTemplateResolver->parseCompiledUrl('http://test.com/gb/');
            } catch (InvalidUrlException $exception) {
            }
            self::assertEquals(get_class($exception), InvalidUrlException::class);
        }

        // Create new resolver by new configData
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfigData->generateUrlTemplateConfig());
        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl('http://test.com/gb/');
        self::assertEquals('gb', $parsedUrlTemplate->getParameter('country'));
    }
}
