<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http;

use Illuminate\Support\Facades\Cache;
use Astrotech\Core\Laravel\Http\Actions\Create;
use Astrotech\Core\Laravel\Http\Actions\NewRead;
use Astrotech\Core\Laravel\Eloquent\NewModelBase;
use Astrotech\Core\Laravel\Http\Actions\NewDelete;
use Astrotech\Core\Laravel\Http\Actions\NewSearch;
use Astrotech\Core\Laravel\Http\Actions\NewUpdate;
use Astrotech\Core\Laravel\Http\Actions\NewOptions;

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

    protected function beforeSave(NewModelBase $record): void
    {
    }

    protected function afterSave(NewModelBase $record): void
    {
    }

    protected function afterSoftDelete(NewModelBase $record): void
    {
    }
}
