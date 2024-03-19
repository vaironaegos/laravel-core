<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http;

use Astrotech\Core\Laravel\Http\Actions\Create;
use Astrotech\Core\Laravel\Http\Actions\Delete;
use Astrotech\Core\Laravel\Http\Actions\Options;
use Astrotech\Core\Laravel\Http\Actions\Read;
use Astrotech\Core\Laravel\Http\Actions\Search;
use Astrotech\Core\Laravel\Http\Actions\Update;
use Illuminate\Database\Eloquent\Model;

abstract class CrudController extends ControllerBase
{
    use Create;
    use Read;
    use Update;
    use Delete;
    use Search;
    use Options;

    protected array $requestFields = [];

    abstract protected function modelClassName(): string;

    protected function beforeFill(array &$data): void
    {
    }

    protected function beforeSave(Model $record): void
    {
    }
}
