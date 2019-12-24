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
        $opts = getopt("", ["report", "report-show-info"]);
        if (isset($opts["report"]) && !isset($config->always)) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }
        if (isset($opts["report-show-info"])) {
            // @codeCoverageIgnoreStart
            JunitReport::$show_info = filter_var($opts["report-show-info"], FILTER_VALIDATE_BOOLEAN);
            // @codeCoverageIgnoreEnd
        }

        // Set plugin options
        JunitReport::$start_time = microtime(true);

        // Set flags via config
        if (!is_null($config)) {
            if (!empty($config->filepath)) {
                JunitReport::$filepath = (string) $config->filepath;
            }
            if (!empty($config->showInfo)) {
                JunitReport::$show_info = filter_var($config->showInfo, FILTER_VALIDATE_BOOLEAN);
            }
            if (!empty($config->showSnippet)) {
                JunitReport::$show_snippet = filter_var($config->showSnippet, FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Set in the cwd if not absolute
        if (JunitReport::$filepath[0] !== DIRECTORY_SEPARATOR) {
            JunitReport::$filepath = getcwd() . DIRECTORY_SEPARATOR . JunitReport::$filepath;
        }

        $psalm->registerHooksFromClass(JunitReport::class);
    }
}
