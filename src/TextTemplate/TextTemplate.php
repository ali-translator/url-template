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
     * @param $openTag
     * @param $closeTag
     */
    public function __construct($openTag = '{', $closeTag = '}')
    {
        $this->openTag = $openTag;
        $this->closeTag = $closeTag;
    }

    /**
     * @param $text
     * @return string[]
     */
    public function parseParametersName($text)
    {
        $openTag = preg_quote($this->openTag, '/');
        $closeTag = preg_quote($this->closeTag, '/');
        $matchesPattern = '/' . $openTag . '([^' . $closeTag . ']+)' . $closeTag . '/';
        preg_match_all($matchesPattern, $text, $parametersName);
        $parametersName = $parametersName[1];

        return $parametersName;
    }

    /**
     * @param $text
     * @param $parameters
     * @return string
     */
    public function resolveParameters($text, $parameters)
    {
        $keysForReplacing = [];
        foreach ($parameters as $parameterName => $parameterValue) {
            $keysForReplacing[] = $this->getParameterKey($parameterName);
        }

        return str_replace($keysForReplacing, $parameters, $text);
    }

    /**
     * @param $parameterName
     * @return string
     */
    public function getParameterKey($parameterName)
    {
        return $this->openTag . $parameterName . $this->closeTag;
    }
}
