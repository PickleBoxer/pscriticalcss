<?php

declare(strict_types=1);

namespace PickleBoxer\PsCriticalCss\Css;

use PickleBoxer\PsCriticalCss\Css\CssMinifierInterface;

class CssMinifier
{
    private $minifier;

    public function __construct(CssMinifierInterface $minifier)
    {
        $this->minifier = $minifier;
    }

    public function minify(string $data, $destination)
    {
        return $this->minifier->minify($data, $destination);
    }
}
