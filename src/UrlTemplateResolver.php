<?php

namespace ALI\UrlTemplate;

use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\Helpers\DuplicateParameterResolver;
use ALI\UrlTemplate\Helpers\UrlPartsConverter;
use ALI\UrlTemplate\UrlTemplateResolver\ParsedUrlTemplateBySimplifiedGenerator;
use ALI\UrlTemplate\UrlTemplateResolver\ParsedUrlTemplateCompiler;
use ALI\UrlTemplate\UrlTemplateResolver\CompileType;
use ALI\UrlTemplate\UrlTemplateResolver\CompiledUrlParser;
use ALI\UrlTemplate\UrlTemplateResolver\ParsedUrlTemplateToSimplifiedUrlDataConverter;

class UrlTemplateResolver
{
    protected UrlTemplateConfig $urlTemplateConfig;
    protected UrlPartsConverter $urlPartsConverter;
    protected CompiledUrlParser $compiledUrlParser;
    protected ParsedUrlTemplateCompiler $parsedUrlTemplateCompiler;
    protected ParsedUrlTemplateBySimplifiedGenerator $parsedUrlTemplateBySimplifiedGenerator;
    protected ParsedUrlTemplateToSimplifiedUrlDataConverter $parsedUrlTemplateToSimplifiedUrlDataConverter;

    public function __construct(
        UrlTemplateConfig $urlTemplateConfig,
        ?UrlPartsConverter $urlPartsConverter = null,
        ?CompiledUrlParser $compiledUrlParser = null,
        ?ParsedUrlTemplateCompiler $parsedUrlTemplateCompiler = null,
        ?ParsedUrlTemplateBySimplifiedGenerator $parsedUrlTemplateBySimplifiedGenerator = null,
        ?ParsedUrlTemplateToSimplifiedUrlDataConverter $parsedUrlTemplateToSimplifiedUrlDataConverter = null
    )
    {
        $this->urlTemplateConfig = $urlTemplateConfig;
        $this->urlPartsConverter = $urlPartsConverter ?? new UrlPartsConverter();
        $duplicateParameterResolver = new DuplicateParameterResolver();
        $this->compiledUrlParser = $compiledUrlParser ?? new CompiledUrlParser($duplicateParameterResolver);
        $this->parsedUrlTemplateCompiler = $parsedUrlTemplateCompiler ?? new ParsedUrlTemplateCompiler($this->urlPartsConverter);
        $this->parsedUrlTemplateBySimplifiedGenerator = $parsedUrlTemplateBySimplifiedGenerator ?? new ParsedUrlTemplateBySimplifiedGenerator();
        $this->parsedUrlTemplateToSimplifiedUrlDataConverter = $parsedUrlTemplateToSimplifiedUrlDataConverter ?? new ParsedUrlTemplateToSimplifiedUrlDataConverter();
    }

    public function getUrlTemplateConfig(): UrlTemplateConfig
    {
        return $this->urlTemplateConfig;
    }

    public function getUrlPartsConverter(): UrlPartsConverter
    {
        return $this->urlPartsConverter;
    }

    /**
     * @throws InvalidUrlException
     */
    public function parseCompiledUrl(string $compiledUrl): ParsedUrlTemplate
    {
        return $this->compiledUrlParser->parseCompiledUrl($compiledUrl, $this->urlTemplateConfig);
    }

    /** @deprecated Use CompileType */
    const COMPILE_TYPE_ALL = CompileType::COMPILE_TYPE_ALL;
    /** @deprecated Use CompileType */
    const COMPILE_TYPE_HOST = CompileType::COMPILE_TYPE_HOST;
    /** @deprecated Use CompileType */
    const COMPILE_TYPE_HOST_WITH_SCHEME = CompileType::COMPILE_TYPE_HOST_WITH_SCHEME;
    /** @deprecated Use CompileType */
    const COMPILE_TYPE_PATH = CompileType::COMPILE_TYPE_PATH;

    public function compileUrl(
        ParsedUrlTemplate $parsedUrlTemplate,
        string $compileType = CompileType::COMPILE_TYPE_ALL
    ): string
    {
        return $this->parsedUrlTemplateCompiler->compileUrl(
            $parsedUrlTemplate,
            $this->urlTemplateConfig,
            $compileType
        );
    }

    public function generateParsedUrlTemplate(string $simplifiedUrl, array $parameters = []): ParsedUrlTemplate
    {
        return $this->parsedUrlTemplateBySimplifiedGenerator
            ->generateParsedUrlTemplate(
                $simplifiedUrl,
                $this->urlTemplateConfig,
                $parameters
            );
    }

    /**
     * @return string[]
     */
    public function getSimplifiedUrlData(ParsedUrlTemplate $parsedUrlTemplate): array
    {
        return $this->parsedUrlTemplateToSimplifiedUrlDataConverter->getSimplifiedUrlData($parsedUrlTemplate);
    }
}
