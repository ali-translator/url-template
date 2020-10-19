# Url Template

Helping on work with templating url. <br>
For example template url for you project was _"gb.example.com/en/london/"_.
In this example you template url has next parameters: "country","language","city".<br>
Lets create for this example temple: "{country}.example.com/{language}/{city}"<br>
And now with code:
```php
use ALI\UrlTemplate\UrlTemplateConfig;
use ALI\UrlTemplate\UrlTemplateResolver;

$urlTemplateConfig = new UrlTemplateConfig(
    '{country}.example.com',
    '{language}/{city}/',
    // Regular expressions that match the parameters  
    [
        'country' => '(uk|ua|gb|pl)',
        'language' => '(en|de)',
        'city' => '(kiev|berlin|paris|london)',
    ],
    // If you have some default parameters that may be empty in url, set them here
    [
        'city' => 'berlin',
        'language' => 'en',
    ],
    true
);
$urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

$url = 'https://gb.example.com/de/london/';

// Parse exist url
$parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($url);
var_dump($parsedUrlTemplate->getParameters());

// Change some parameter on existed url
$parsedUrlTemplate->setParameter('country','pl');
$urlWithAnotherCountry = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
var_dump($urlWithAnotherCountry);

// Get clear url(without template parameters) for application routing
$simplifiedUrl = $urlTemplateResolver->getSimplifiedUrl($parsedUrlTemplate);
var_dump($simplifiedUrl); // -> "https://example.com"

// Generate full url from simplified url(which application return)
$parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://example.com/some-category/item?sale=1',[
    'country' => 'uk',
    'city' => 'london',
     // 'language' => 'en', // Default values may be skipped
]);
$compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
var_dump($compiledUrl); // -> "https://uk.example.com/london/some-category/item?sale=1"

// As you may see, in url was skipped default language value "en"
// If you want their in url, you must set "false" to last parameter "isHideDefaultParameters" on constructor of UrlTemplateConfig 
```

**Warning**: be careful with some free regular expressions, as for language '[a-z]{2}', will be better '(en|de|ua)'

### Tests
In packet exist docker-compose file, with environment for testing.
```bash
docker-compose up -d
docker-compose exec php bash
composer install
./vendor/bin/phpunit
``` 
