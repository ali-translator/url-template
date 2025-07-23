# URL Template

Helps work with URLs using their "base" template. <br>
For example, the base structure of your project URL is _"gb.example.com/en/london/"_.
In this example, your template URL has the following parameters: "country," "language," and "city." <br>
Let's create a template for this example: "{country}.example.com/{language}/{city}" <br>

## Installation

```bash
$ composer require ali-translator/url-template
```


## Code example:
```php
use ALI\UrlTemplate\UrlTemplateConfig;
use ALI\UrlTemplate\UrlTemplateResolver;

$urlTemplateConfig = new UrlTemplateConfig(
    '{country}.example.com',
    '{language}/{city}/',
    // Regular expressions matching the parameters
    [
        'country' => ['uk', 'ua', 'pl'],
        'language' => '[a-z]{2}', // be careful with some free regular expressions
        'city' => ['kiev', 'berlin', 'paris', 'london'],
    ],
    // If you have some default parameters that may be empty in url, set them here
    [
        'city' => 'berlin',
        'language' => 'en',
    ],
    // "Hide default URL parameters?".
    // Can be an array if you want to hide only some parameters.
    true
);
$urlTemplateResolver = new UrlTemplateResolver($urlTemplateConfig);

$url = 'https://gb.example.com/de/london/';

// Parse existing URL
$parsedUrlTemplate = $urlTemplateResolver->parseCompiledUrl($url);
var_dump($parsedUrlTemplate->getFullParameters());

// Change some parameter on existing URL
$parsedUrlTemplate->setParameter('country', 'pl');
$urlWithAnotherCountry = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
var_dump($urlWithAnotherCountry);

// Get clear URL (without template parameters) for application routing
$simplifiedUrl = $urlTemplateResolver->getSimplifiedUrl($parsedUrlTemplate);
var_dump($simplifiedUrl); // -> "https://example.com"

// Generate full URL from simplified URL (which application returns)
$parsedUrlTemplate = $urlTemplateResolver->generateParsedUrlTemplate('https://example.com/some-category/item?sale=1', [
    'country' => 'uk',
    'city' => 'london',
     // 'language' => 'en', // Default values may be skipped
]);
$compiledUrl = $urlTemplateResolver->compileUrl($parsedUrlTemplate);
var_dump($compiledUrl); // -> "https://uk.example.com/london/some-category/item?sale=1"

// As you may see, the default language value "en" is omitted in the URL.
// If you want it included in the URL, you must set "false" for the last parameter "isHideDefaultParameters" in the constructor of `UrlTemplateConfig`. 
```

**Warning**: Be careful with free-form regular expressions. For language parameters, '[a-z]{2}' is less safe than using an explicit list like '(en|de|ua)'.

#### Optional Default Values
You can set optional default values for parameters by providing a callable argument as the default value.<br>
**Your optional parameter must depend only on required arguments.**<br>

Example of use:<br>

```php
use ALI\UrlTemplate\UrlTemplateConfig;

$urlTemplateConfig = new UrlTemplateConfig(
    '{country}.test.com',
    '/{language}/',
    [
        'country' => ['tr', 'gb'],
        'language' => ['en', 'tr', 'de'],
    ],
    [
        'language' => function ($requiredParameters) {
            $languagesByCountries = ['tr'=>'tr', 'gb'=>'en'];
            
            return $languagesByCountries[$requiredParameters['country']] ?? 
                throw new Exception('Invalid country alias');
        },
    ],
    true
);
``` 

### Parameter Decorators
Sometimes you need to apply decorations to your parameters in a URL.<br>
For example, if you want the following path template to be "/{country}-{language}/" and decide to hide the default language.<br>
In this case, without decorators, you would get the following compiled URL: "/country-/".<br>
The excessive character "-" looks unappealing.<br>
You can use decorators to solve this problem.<br>
A decorator is a class that implements the `ParameterDecoratorInterface`.<br>

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

**For the decorator to work correctly, use an array of requirements with available values, instead of a regular expression.<br>**

### Validate ParsedTemplate Object
```php
use \ALI\UrlTemplate\UrlTemplateResolver\ParsedUrlTemplateValidator;
use \ALI\UrlTemplate\ParsedUrlTemplate;

/** @var ParsedUrlTemplate $parsedUrlTemplate */

$urlTemplateValidator = new ParsedUrlTemplateValidator();
$errors = $urlTemplateValidator->validateParameters($parsedUrlTemplate);
// $errors : [key -> (string)'error description']
```

### Additional Features
* You can use templates where multiple parameters are placed in one "URL namespace," such as the host "{country}-{language}-{currency}.test.com" and the path "/{country}-{language}/".
  * If you need to compile only the "host URL" or "path URL," you can use: 
  ```php
  $urlTemplateResolver->compileUrl($parsedUrl, $urlTemplateResolver::COMPILE_TYPE_HOST);
  ```
* If you need to skip only some default parameters in the URL, you can pass an array of parameter names to the $hideDefaultParametersFromUrl parameter of the UrlTemplateConfig class.
* If you have an optional parameter that depends on another parameter, and this other parameter is in a different part of the URL (e.g., the optional parameter is in the "path URL part" and depends on a parameter in the "host URL part"), there may be an issue when processing a relative URL without the host.<br> To handle this scenario, pass a value to the function that determines the optional parameter:<br>
    ```php
    ...
    [
      'language' => function ($requiredParametersValues) use ($currentCountryAlias) {
          $countryAlias = $requiredParametersValues['country'] ?? $currentCountryAlias;
    ...
    ```
* To create a new `UrlTemplateConfig` based on an existing one:
    ```php
    /** @var $urlTemplateConfig ALI\UrlTemplate\UrlTemplateConfig */
    $urlTemplateConfigData = $urlTemplateConfig->generateUrlTemplateConfigData();
    // Modify some config data
    $urlTemplateConfigData->setDefaultUrlSchema('https');
    // Create a new UrlTemplateConfig
    $newUrlTemplateConfig = $urlTemplateConfigData->generateUrlTemplateConfig();
    ```
* By default, the system allows the use of all subdomains for a given domain template.  
  For example, if we have a template `{city}.test.com`, it will correctly handle the domain `www.lviv.test.com`.  
  However, if we need the system to restrict handling of subdomains, we can specify in `UrlTemplateConfig` that subdomains should not be supported:
  ```php
  $urlTemplateConfig->setIsAllowedSubdomains(false);
  ```


### Tests
Included in the package is a docker-compose file, with an environment for testing.
```bash
docker-compose up -d
docker-compose exec php bash
composer install
./vendor/bin/phpunit
./vendor/bin/phpstan analyse src tests
```