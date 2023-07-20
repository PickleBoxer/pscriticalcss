<?php

declare(strict_types=1);

namespace PickleBoxer\PsCriticalCss\Css;

use MatthiasMullie\Minify\CSS;

class Minifier implements CssMinifierInterface
{
    public function minify(string $data, $destination = null)
    {
        $minifier = new CSS();
        $minifier->add($data);

        if ($destination !== null) {
            return $minifier->minify($destination);
        } else {
            return $minifier->minify();
        }
    }
}