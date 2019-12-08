<?php

declare(strict_types=1);

namespace DQ5Studios\PsalmJunit\Tests;

use DQ5Studios\PsalmJunit\JunitReport;
use DQ5Studios\PsalmJunit\Plugin;
use SimpleXMLElement;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psalm\Plugin\RegistrationInterface;

class PluginTest extends TestCase
{
    /**
     * Test default load
     */
    public function testHasEntryPoint(): void
    {
        $registration = $this->prophesize(RegistrationInterface::class);
        $default_filename = JunitReport::$filepath;
        $plugin = new Plugin();
        /** @var RegistrationInterface */
        $reg_interface = $registration->reveal();
        $plugin($reg_interface, null);
        $this->assertNotEmpty(JunitReport::$start_time);
        $filepath = getcwd() . DIRECTORY_SEPARATOR . $default_filename;
        $this->assertSame($filepath, JunitReport::$filepath);
    }

    /**
     * Test custom load
     */
    public function testAcceptsConfigOption(): void
    {
        $registration = $this->prophesize(RegistrationInterface::class);
        $config = new SimpleXMLElement("<pluginClass><filepath>different_filename.xml</filepath></pluginClass>");
        $plugin = new Plugin();
        /** @var RegistrationInterface */
        $reg_interface = $registration->reveal();
        $plugin($reg_interface, $config);
        $this->assertNotEmpty(JunitReport::$start_time);
        $filepath = getcwd() . DIRECTORY_SEPARATOR . (string) $config->filepath;
        $this->assertSame($filepath, JunitReport::$filepath);
    }
}
