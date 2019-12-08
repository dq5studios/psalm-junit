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
        // Check if another report is to be run
        $opts = getopt("", ["report"]);
        if (isset($opts["report"]) && !isset($config->always)) {
            return;
        }

        // Set plugin options
        JunitReport::$start_time = microtime(true);

        // Set filepath via config
        if (!is_null($config)) {
            if (!empty($config->filepath)) {
                JunitReport::$filepath = (string) $config->filepath;
            }
        }

        // Set in the cwd if not absolute
        if (JunitReport::$filepath[0] !== DIRECTORY_SEPARATOR) {
            JunitReport::$filepath = getcwd() . DIRECTORY_SEPARATOR . JunitReport::$filepath;
        }

        $psalm->registerHooksFromClass(JunitReport::class);
    }
}
