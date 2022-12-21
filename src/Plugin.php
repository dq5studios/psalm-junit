<?php

declare(strict_types=1);

namespace DQ5Studios\PsalmJunit;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use const DIRECTORY_SEPARATOR;
use const FILTER_VALIDATE_BOOLEAN;

class Plugin implements PluginEntryPointInterface
{
    /**
     * {@inheritDoc}
     */
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
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
        if (null !== $config) {
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
        if (DIRECTORY_SEPARATOR !== JunitReport::$filepath[0]) {
            JunitReport::$filepath = getcwd() . DIRECTORY_SEPARATOR . JunitReport::$filepath;
        }

        $registration->registerHooksFromClass(JunitReport::class);
    }
}
