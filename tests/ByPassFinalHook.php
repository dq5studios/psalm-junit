<?php

declare(strict_types=1);

namespace DQ5Studios\PsalmJunit\Tests;

use DG\BypassFinals;
use PHPUnit\Runner\BeforeTestHook;

class ByPassFinalHook implements BeforeTestHook
{
    public function executeBeforeTest(string $test): void
    {
        // mutate final classes into non final on-the-fly
        BypassFinals::enable();
    }
}
