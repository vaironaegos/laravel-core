<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Http;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Astrotech\Core\Laravel\Http\Response\AnswerTrait;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class ControllerBase extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use AnswerTrait;
}
