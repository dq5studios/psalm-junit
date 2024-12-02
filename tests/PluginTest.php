<?php

declare(strict_types=1);

namespace DQ5Studios\PsalmJunit\Tests;

use DQ5Studios\PsalmJunit\JunitReport;
use DQ5Studios\PsalmJunit\Plugin;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use const DIRECTORY_SEPARATOR;

class PluginTest extends TestCase
{
    use ProphecyTrait;

    /**
     * Test default load.
     */
    public function testHasEntryPoint(): void
    {
        $registration = $this->prophesize(RegistrationInterface::class);
        $default_filename = JunitReport::$filepath;
        $plugin = new Plugin();
        $reg_interface = $registration->reveal();
        $plugin($reg_interface, null);
        $this->assertNotEmpty(JunitReport::$start_time);
        $filepath = getcwd() . DIRECTORY_SEPARATOR . $default_filename;
        $this->assertSame($filepath, JunitReport::$filepath);
    }

    /**
     * Test custom load.
     */
    public function testAcceptsFilePathConfigOption(): void
    {
        $registration = $this->prophesize(RegistrationInterface::class);
        $config = new SimpleXMLElement("<pluginClass><filepath>different_filename.xml</filepath></pluginClass>");
        $plugin = new Plugin();
        $reg_interface = $registration->reveal();
        $plugin($reg_interface, $config);
        $filepath = getcwd() . DIRECTORY_SEPARATOR . (string) $config->filepath;
        $this->assertSame($filepath, JunitReport::$filepath);
    }

    /**
     * Test custom load.
     */
    public function testAcceptsShowInfoConfigOption(): void
    {
        $registration = $this->prophesize(RegistrationInterface::class);
        $config = new SimpleXMLElement("<pluginClass><showInfo>false</showInfo></pluginClass>");
        $plugin = new Plugin();
        $reg_interface = $registration->reveal();
        $plugin($reg_interface, $config);
        $this->assertFalse(JunitReport::$show_info);
    }

    /**
     * Test custom load.
     */
    public function testAcceptsShowSnippetConfigOption(): void
    {
        $registration = $this->prophesize(RegistrationInterface::class);
        $config = new SimpleXMLElement("<pluginClass><showSnippet>false</showSnippet></pluginClass>");
        $plugin = new Plugin();
        $reg_interface = $registration->reveal();
        $plugin($reg_interface, $config);
        $this->assertFalse(JunitReport::$show_snippet);
    }
}
