<?php

declare(strict_types=1);

namespace DQ5Studios\PsalmJunit;

use SimpleXMLElement;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

class Plugin implements PluginEntryPointInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(RegistrationInterface $psalm, ?SimpleXMLElement $config = null)
    {
        class_exists(JunitReport::class);
        $psalm->registerHooksFromClass(JunitReport::class);

        JunitReport::$start_time = microtime(true);

        JunitReport::$path = getcwd();
        if (!is_null($config)) {
            if (!empty($config->file)) {
                JunitReport::$file = (string) $config->file;
            }
            if (!empty($config->path)) {
                JunitReport::$path = (string) $config->path;
            }
        }
    }
}
