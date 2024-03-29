# Url Template

Helping on work with url by their "base" template. <br>
For example, base structure of you project url was _"gb.example.com/en/london/"_.
In this example you template url has next parameters: "country","language" and "city".<br>
Lets create for this example temple: "{country}.example.com/{language}/{city}"<br>


## Installation

```bash
$ composer require ali-translator/url-template
```


## Code examle:
```php
use ALI\UrlTemplate\UrlTemplateConfig;
use ALI\UrlTemplate\UrlTemplateResolver;

$urlTemplateConfig = new UrlTemplateConfig(
    '{country}.example.com',
    '{language}/{city}/',
    // Regular expressions that match the parameters  
    [
        'country' => ['uk','ua','pl'],
        'language' => '[a-z]{2}', // be careful with some free regular expressions
        'city' => ['kiev','berlin','paris','london'],
    ],
    // If you have some default parameters that may be empty in url, set them here
    [
        'city' => 'berlin',
        'language' => 'en',
    ],
    // hide defaults parameters from url ?
    true
);
$urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

$url = 'https://gb.example.com/de/london/';

// Parse exist url
$parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($url);
var_dump($parsedUrlTemplate->getFullParameters());

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

#### Optionality default values
You may set optionality default value of parameter. For this you must set callable argument for default value.<br>
**You optionality parameter must be depending only from required argument.**<br>

Example of use:<br>

```php
use ALI\UrlTemplate\UrlTemplateConfig;

$urlTemplateConfig = new UrlTemplateConfig(
    '{country}.test.com',
    '/{language}/',
    [
        'country' => ['tr','gb'],
        'language' => ['en','tr','de'],
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
                    throw new Exception('Invalid country alias');
                break;
            }
        },
    ],
    true
);

``` 

### Parameter decorators
Sometimes you need some decoration for you parameters in url.<br>
For example, if you want next path template to be "/{country}-{language}/", and you decide to hide default language.<br> 
In this case, without decorators, you get the following compiled url :"/country-/"<br>
Excessive character "-" looks bad.<br>
You can use Decorators to solve this problem. <br>
Decorator - class which implement "ParameterDecoratorInterface".<br>

Example of use:
```php
use ALI\UrlTemplate\ParameterDecorators\WrapperParameterDecorator;
use ALI\UrlTemplate\UrlTemplateConfig;

$urlTemplateConfig = new UrlTemplateConfig(
    null,
    '/{country}{language}/',
    [
        'country' => ['ua', 'pl'],
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
```

**For correct decorator works - use array on requirements, with available values, not regular expression.<br>**

### Validate ParsedTemplate object
```php
use \ALI\UrlTemplate\UrlTemplateResolver\ParsedUrlTemplateValidator;
use \ALI\UrlTemplate\ParsedUrlTemplate;

/** @var ParsedUrlTemplate $parsedUrlTemplate */

$urlTemplateValidator = new ParsedUrlTemplateValidator();
$errors = $urlTemplateValidator->validateParameters($parsedUrlTemplate);
// $errors : [key -> (string)'error description']
```

### Additional features
* also you may use templates, where in one "url namespace" placed few parameters, as host "{country}-{language}-{currency}.test.com" and path "/{country}-{language}/"
* If you need only compile "host url" or "path url" ``` $urlTemplateResolver->compileUrl($parsedUrl, $urlTemplateResolver::COMPILE_TYPE_HOST) ```
* If you need skip from url only some of default parameters - you may set array with parameters name for parameter `$hideDefaultParametersFromUrl` of `UrlTemplateConfig` class
* If you have an optional parameter that depends on another parameter, and  this another parameter was in another part of the url(for example the optional parameter is in the "path url part", and it depends on the parameter in the "host url part"), then there can be a problem when you need to process relative url, without the host.<br>
  To be able to leave this possibility, you need to pass a value to the function of determining the optional parameter:<br>
    ```php
    ...
    [
      'language' => function ($requiredParametersValues) use ($currentCountryAlias) {
          $countryAlias = $requiredParametersValues['country'] ?? $currentCountryAlias;
    ...
    ```
* For creating new `UrlTemplateConfig` by existed:
    ```php
    /** @var $urlTemplateConfig ALI\UrlTemplate\UrlTemplateConfig */
    $urlTemplateConfigData = $urlTemplateConfig->generateUrlTemplateConfigData();
    // Change some config data
    $urlTemplateConfigData->setDefaultUrlSchema('https');
    // Create new UrlTemplateConfig
    $newUrlTemplateConfig = $urlTemplateConfigData->generateUrlTemplateConfig();
    ```

### Tests
In packet exist docker-compose file, with environment for testing.
```bash
docker-compose up -d
docker-compose exec php bash
composer install
./vendor/bin/phpunit
``` 
