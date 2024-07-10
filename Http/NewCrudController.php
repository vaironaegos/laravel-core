<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http;

use Astrotech\Core\Laravel\Http\Actions\NewDelete;
use Astrotech\Core\Laravel\Http\Actions\NewOptions;
use Astrotech\Core\Laravel\Http\Actions\NewRead;
use Astrotech\Core\Laravel\Http\Actions\NewUpdate;
use Illuminate\Database\Eloquent\Model;
use Astrotech\Core\Laravel\Http\Actions\Create;
use Astrotech\Core\Laravel\Http\Actions\NewSearch;

abstract class NewCrudController extends ControllerBase
{
    use Create;
    use NewRead;
    use NewUpdate;
    use NewDelete;
    use NewSearch;
    use NewOptions;

    protected array $requestFields = [];

    abstract protected function modelClassName(): string;

    protected function beforeFill(array &$data): void
    {
    }

    protected function beforeSave(Model $record): void
    {
    }

    protected function afterSave(Model $record): void
    {
    }
}
