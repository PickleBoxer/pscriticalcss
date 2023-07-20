<?php

declare(strict_types=1);

namespace PickleBoxer\PsCriticalCss\Css;

interface CssMinifierInterface
{
    public function minify(string $data, $destination);
}