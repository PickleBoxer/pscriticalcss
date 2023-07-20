<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

declare(strict_types=1);

namespace PickleBoxer\PsCriticalCss;

use CSSFromHTMLExtractor\CssFromHTMLExtractor;
use Masterminds\HTML5;
use PickleBoxer\PsCriticalCss\Css\CssMinifier;
use Symfony\Component\Filesystem\Filesystem;

/**
 * The CriticalCss class is responsible for processing raw HTML content and extracting the critical CSS for above-the-fold content.
 */
class CriticalCss
{
    /**
     * The path to the cache directory.
     *
     * @var string
     */
    private $cacheDir;

    /**
     * The Symfony filesystem object.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Creates a new CriticalCss object.
     *
     * @param string $cacheDir the path to the cache directory
     * @param Filesystem $filesystem the Symfony filesystem object
     */
    public function __construct(string $cacheDir, Filesystem $filesystem)
    {
        $this->cacheDir = $cacheDir;
        $this->filesystem = $filesystem;

        // Create the cache directory if it does not exist
        if (!$this->filesystem->exists($this->cacheDir)) {
            $this->filesystem->mkdir($this->cacheDir);
        }
    }

    /**
     * Processes the raw HTML content and extracts the critical CSS for above-the-fold content.
     *
     * @param string $rawHtml the raw HTML content that needs to be processed
     * @param string $controllerName the name of the controller
     *
     * @return string returns the extracted critical CSS content if successful, otherwise returns the original HTML content
     */
    public function process(string $rawHtml, string $controllerName): string
    {
        try {
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

    /**
     * Returns an array of URLs for all stylesheet link elements in the given HTML document.
     *
     * @param \DOMDocument $document the HTML document to search for stylesheet link elements
     *
     * @return array returns an array of URLs for all stylesheet link elements in the given HTML document
     */
    private function getStylesheetUrls(\DOMDocument $document): array
    {
        $urls = [];

        // Loop through all 'link' tags in the HTML document
        foreach ($document->getElementsByTagName('link') as $linkTag) {
            /** @var \DOMElement $linkTag */
            // Check if the current link tag is for a stylesheet
            if ($linkTag->getAttribute('rel') === 'stylesheet') {
                // Get the URL of the current stylesheet
                $tokenisedStylesheet = explode('?', $linkTag->getAttribute('href'));
                $urls[] = reset($tokenisedStylesheet);
            }
        }

        return $urls;
    }

    /**
     * Extracts the CSS rules for the above-the-fold content from the given HTML document.
     *
     * @param \DOMDocument $document the HTML document to extract the CSS rules from
     *
     * @return CssFromHTMLExtractor returns a CssFromHTMLExtractor object containing the extracted CSS rules
     */
    private function extractCssFromHtml(\DOMDocument $document): CssFromHTMLExtractor
    {
        /** @var CssFromHTMLExtractor $cssFromHTMLExtractor */
        // Create a new CssFromHTMLExtractor object
        $cssFromHTMLExtractor = new CssFromHTMLExtractor();

        // Loop through all 'link' tags in the HTML document
        foreach ($document->getElementsByTagName('link') as $linkTag) {
            /** @var \DOMElement $linkTag */

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

    /**
     * Saves the given critical CSS to a file at the given path.
     *
     * @param string $criticalCss the critical CSS to save to a file
     * @param string $filePath the path to the file to save the critical CSS to
     *
     * @return void
     */
    private function saveCriticalCssToFile(string $criticalCss, string $filePath): void
    {
        // Add destinationPath as a comment to the beginning of the CSS
        $criticalCss = "CriticalCSS{path: $filePath;}\n" . $criticalCss;

        // Minify the extracted CSS and save it to a file
        if (!$this->filesystem->exists($filePath)) {
            CssMinifier::minify($criticalCss, $filePath);
        }
    }

    /**
     * Adds a link element for the critical CSS to the given HTML document.
     *
     * @param \DOMDocument $document the HTML document to add the link element to
     * @param string $controllerName the name of the controller
     * @param string $filenameIdentifier the unique identifier for the extracted CSS file
     *
     * @return void
     */
    private function addCriticalCssLinkElement(\DOMDocument $document, string $controllerName, string $filenameIdentifier): void
    {
        // Create the critical css link element
        $linkCritical = $document->createElement('link');
        $linkCritical->setAttribute('fetchpriority', 'high');
        $linkCritical->setAttribute('href', __PS_BASE_URI__ . 'themes/' . _THEME_NAME_ . '/assets/cache/' . $controllerName . '-' . $filenameIdentifier . '.css');
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

    /**
     * Adds onload attributes to all stylesheet link elements in the given HTML document.
     *
     * @param \DOMDocument $document the HTML document to add the onload attributes to
     *
     * @return void
     */
    private function addOnloadAttributesToStylesheetLinkElements(\DOMDocument $document): void
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

    /**
     * Generates a unique identifier for a list of file URLs.
     *
     * @param array $files an array of file URLs
     *
     * @return string returns a unique identifier for the given list of file URLs
     */
    private function getFileNameIdentifierFromList(array $files): string
    {
        return substr(sha1(implode('|', $files)), 0, 6);
    }
}
