<?php

namespace ALI\UrlTemplate\TextTemplate;

/**
 * Class
 */
class TextTemplate
{
    /**
     * @var string
     */
    private $openTag;

    /**
     * @var string
     */
    private $closeTag;

    /**
     * @param string $openTag
     * @param string $closeTag
     */
    public function __construct(string $openTag = '{', string $closeTag = '}')
    {
        $this->openTag = $openTag;
        $this->closeTag = $closeTag;
    }

    /**
     * @param null|string $text
     * @return string[]
     */
    public function parseParametersName($text): array
    {
        if (!$text) {
            return [];
        }
        $openTag = preg_quote($this->openTag, '/');
        $closeTag = preg_quote($this->closeTag, '/');
        $matchesPattern = '/' . $openTag . '([^' . $closeTag . ']+)' . $closeTag . '/';
        preg_match_all($matchesPattern, $text, $parametersName);

        return $parametersName[1];
    }

    /**
     * @param null|string $text
     * @param array $parameters
     * @return string
     */
    public function resolveParameters($text, array $parameters): string
    {
        if (!$text) {
            return '';
        }

        $keysForReplacing = [];
        foreach ($parameters as $parameterName => $parameterValue) {
            $keysForReplacing[] = $this->getParameterKey($parameterName);
        }

        return str_replace($keysForReplacing, $parameters, $text);
    }

    /**
     * @param string $parameterName
     * @return string
     */
    public function getParameterKey(string $parameterName): string
    {
        return $this->openTag . $parameterName . $this->closeTag;
    }
}
