<?php

declare(strict_types=1);

namespace PickleBoxer\PsCriticalCss;

use Masterminds\HTML5;
use Symfony\Component\Filesystem\Filesystem;
use CSSFromHTMLExtractor\CssFromHTMLExtractor;
use PickleBoxer\PsCriticalCss\Css\CssMinifier;

/**
 * The CriticalCss class is responsible for processing raw HTML content and extracting the critical CSS from it.
 */
class CriticalCss
{
    /**
     * The path to the cache directory.
     *
     * @var string
     */
    private $cacheDir = _PS_THEME_DIR_ . 'assets/cache/';

    /**
     * The Symfony filesystem object.
     *
     * @var Filesystem
     */
    private $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();

        // Create the cache directory if it does not exist
        if (!$this->filesystem->exists($this->cacheDir)) {
            $this->filesystem->mkdir($this->cacheDir);
        }
    }

    public function process(string $rawHtml, string $controllerName): string
    {
        // Create a new HTML5 object
        $html5 = new HTML5();

        // Load the raw HTML content into the HTML5 document
        $document = $html5->loadHTML($rawHtml);

        // Get the URLs of all stylesheets in the HTML document
        $files = $this->getStylesheetUrls($document);

        // Generate a unique identifier for the extracted CSS file
        $filenameIdentifier = $this->getFileNameIdentifierFromList($files);

        // Get the path to the extracted CSS file
        $filePath = $this->cacheDir . $controllerName . '-' . $filenameIdentifier . '.css';

        // Check if the extracted CSS file exists in cache
        if ($this->filesystem->exists($filePath)) {
            // Add onload attributes to all stylesheet link elements in the HTML document
            $this->addOnloadAttributesToStylesheetLinkElements($document);

            // Add a link element for the critical CSS to the HTML document
            $this->addCriticalCssLinkElement($document, $controllerName, $filenameIdentifier);

            // Return the modified HTML document
            return $html5->saveHTML($document);
        }

        try {
            // Extract the CSS rules for the above-the-fold content
            $cssFromHTMLExtractor = $this->extractCssFromHtml($document);

            // Add the raw HTML content to the CssFromHTMLExtractor object
            $cssFromHTMLExtractor->addHtmlToStore($rawHtml);

            // Build the extracted CSS rule set into a string
            $criticalCss = $cssFromHTMLExtractor->buildExtractedRuleSet();

            // Minify the extracted CSS and save it to a file
            $this->saveCriticalCssToFile($criticalCss, $filePath);

            // Add onload attributes to all stylesheet link elements in the HTML document
            $this->addOnloadAttributesToStylesheetLinkElements($document);

            // Add a link element for the critical CSS to the HTML document
            $this->addCriticalCssLinkElement($document, $controllerName, $filenameIdentifier);

            // Return the modified HTML document
            return $html5->saveHTML($document);
        } catch (\Exception $exception) {
            // Catch and log any exceptions that occur during processing
            error_log($exception->getMessage());
            return $rawHtml;
        }
    }

    private function getStylesheetUrls($document): array
    {
        $urls = [];

        // Loop through all 'link' tags in the HTML document
        foreach ($document->getElementsByTagName('link') as $linkTag) {
            // Check if the current link tag is for a stylesheet
            if ($linkTag->getAttribute('rel') === 'stylesheet') {
                // Get the URL of the current stylesheet
                $tokenisedStylesheet = explode('?', $linkTag->getAttribute('href'));
                $urls[] = reset($tokenisedStylesheet);
            }
        }

        return $urls;
    }

    private function extractCssFromHtml($document): CssFromHTMLExtractor
    {
        /** @var CssFromHTMLExtractor $cssFromHTMLExtractor */
        // Create a new CssFromHTMLExtractor object
        $cssFromHTMLExtractor = new CssFromHTMLExtractor();

        // Loop through all 'link' tags in the HTML document
        foreach ($document->getElementsByTagName('link') as $linkTag) {

            // Check if the current link tag is for a stylesheet
            if ($linkTag->getAttribute('rel') === 'stylesheet') {

                // Get the URL of the current stylesheet
                $tokenisedStylesheet = explode('?', $linkTag->getAttribute('href'));
                $stylesheet = reset($tokenisedStylesheet);
                $css = parse_url($stylesheet);

                // If the stylesheet was not found in cache, try to fetch it from the local path
                if (($content = @file_get_contents(_PS_ROOT_DIR_ . $css['path'])) !== false) {
                    $cssFromHTMLExtractor->addBaseRules($content);
                    continue;
                }

                // If the stylesheet was not found in cache, try to fetch it from the server
                if (($content = @\Tools::file_get_contents($stylesheet)) !== false) {
                    $cssFromHTMLExtractor->addBaseRules($content);
                    continue;
                }
            }
        }

        return $cssFromHTMLExtractor;
    }

    private function saveCriticalCssToFile(string $criticalCss, string $filePath): void
    {
        // Add destinationPath as a comment to the beginning of the CSS
        $criticalCss = "CriticalCSS{path: $filePath;}\n" . $criticalCss;

        // Minify the extracted CSS and save it to a file
        if (!$this->filesystem->exists($filePath)) {
            //CssMinifier::minify($criticalCss, $filePath);
            $minifier = new CssMinifier();
            $minifier->minify($criticalCss, $filePath);
        }
    }

    private function addCriticalCssLinkElement($document, string $controllerName, string $filenameIdentifier): void
    {
        // Create the critical css link element
        $linkCritical = $document->createElement('link');
        $linkCritical->setAttribute('fetchpriority', 'high');
        $linkCritical->setAttribute('href', _PS_THEME_URI_ . 'assets/cache/' . $controllerName . '-' . $filenameIdentifier . '.css');
        $linkCritical->setAttribute('rel', 'stylesheet');
        $linkCritical->setAttribute('type', 'text/css');
        $linkCritical->setAttribute('media', 'all');

        // Find all the stylesheet link elements
        $firstLink = true;
        foreach ($document->getElementsByTagName('link') as $existingLink) {
            if ($existingLink->getAttribute('rel') === 'stylesheet') {
                // if first stylesheet, add the critical css link element before it
                if ($firstLink) {
                    $firstLink = false;
                    $existingLink->parentNode->insertBefore($linkCritical, $existingLink);
                    // end foreach loop
                    continue;
                }
            }
        }
    }

    private function addOnloadAttributesToStylesheetLinkElements($document): void
    {
        // Find all the stylesheet link elements
        foreach ($document->getElementsByTagName('link') as $existingLink) {
            if ($existingLink->getAttribute('rel') === 'stylesheet') {
                $media = $existingLink->getAttribute('media');
                // Add an onload attribute with the $stylesheet.media value
                $onloadValue = "this.media='$media'";
                $existingLink->setAttribute('onload', $onloadValue);
                $existingLink->setAttribute('media', 'print');
            }
        }
    }

    private function getFileNameIdentifierFromList(array $files): string
    {
        return substr(sha1(implode('|', $files)), 0, 6);
    }
}
