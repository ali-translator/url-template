<?php

namespace ALI\UrlTemplateTests\unit\UrlTemplateResolver;

use ALI\UrlTemplate\ParsedUrlTemplate;
use ALI\UrlTemplate\UrlTemplateConfig;
use ALI\UrlTemplate\UrlTemplateResolver;
use ALI\UrlTemplate\UrlTemplateResolver\ParsedUrlTemplateValidator;
use PHPUnit\Framework\TestCase;

class UrlTemplateValidatorTest extends TestCase
{
    public function test()
    {
        // With default schema
        $urlTemplateConfig = new UrlTemplateConfig(
            '{country}.test.com',
            '/{language}/{city}/',
            [
                'country' => '(tr)',
                'language' => '(en|tr)',
                'city' => ['istanbul', 'ankara'],
            ],
            [
                'language' => 'tr',
                'city' => 'istanbul',
            ],
            true
        );
        $urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);
        $originalUrl = 'http://tr.test.com/en/';

        $parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($originalUrl);

        $urlTemplateValidator = new ParsedUrlTemplateValidator();

        // Without errors
        $errors = $urlTemplateValidator->validateParsedUrlTemplate($parsedUrlTemplate);
        $this->assertEquals([], $errors);

        // One parameter error
        $parsedUrlTemplate->setParameter('country','invalid_country');
        $errors = $urlTemplateValidator->validateParsedUrlTemplate($parsedUrlTemplate);
        $this->assertEquals(1, count($errors));
        $this->assertEquals(true, isset($errors['country']));

        // And one more parameter error
        $parsedUrlTemplate->setParameter('city','invalid_city');
        $errors = $urlTemplateValidator->validateParsedUrlTemplate($parsedUrlTemplate);
        $this->assertEquals(2, count($errors));
        $this->assertEquals(true, isset($errors['country']));
        $this->assertEquals(true, isset($errors['city']));

        // Add invalid patterned host and path
        $parsedUrlTemplate = new ParsedUrlTemplate(
            $parsedUrlTemplate->getPatternedHost() .'.ua',
            '/invalid_path' . $parsedUrlTemplate->getPatternedPath(),
            $parsedUrlTemplate->getOwnParameters(),
            $parsedUrlTemplate->getUrlTemplateConfig()
        );
        $errors = $urlTemplateValidator->validateParsedUrlTemplate($parsedUrlTemplate);
        $this->assertEquals(4, count($errors));
        $this->assertEquals(true, isset($errors['host']));
        $this->assertEquals(true, isset($errors['path']));
    }
}
