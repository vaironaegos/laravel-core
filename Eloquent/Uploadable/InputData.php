<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent\Uploadable;

use Astrotech\Core\Base\Adapter\DtoBase;
use Astrotech\Core\Laravel\Eloquent\ModelBase;

final class InputData extends DtoBase
{
    public function __construct(
        public readonly ModelBase $record,
        public readonly string    $field,
        public readonly mixed     $file,
        public readonly string    $path,
        public readonly array     $allowedExtensions = ['jpg', 'jpeg', 'png'],
        public readonly bool      $multiple = false
    ) {
    }
}
