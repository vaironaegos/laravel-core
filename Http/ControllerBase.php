<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http;

use Astrotech\Core\Laravel\Http\Response\AnswerTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class ControllerBase extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use AnswerTrait;
}
