<?php

declare(strict_types=1);

namespace DQ5Studios\PsalmJunit\Tests;

use DG\BypassFinals;
use DQ5Studios\PsalmJunit\JunitReport;
use PHPUnit\Framework\TestCase;
use DOMDocument;
use DOMNamedNodeMap;
use DOMNode;
use Generator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Promise\ReturnPromise;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Codebase\Analyzer;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;

BypassFinals::enable();

class JunitReportTest extends TestCase
{
    use ProphecyTrait;

    /**
     * Generate issue lists for processing
     *
     * @return Generator<string,array,mixed,void>
     */
    public function generateTestCases(): Generator
    {
        $issue_list = [];
        yield "Empty list" => [
            $issue_list, "Test Case #1", "0.0", ["tests" => 0, "failures" => 0, "children" => 0]
        ];

        $issue_list = [
            "file1.php" => [],
            "file2.php" => [],
            "file3.php" => []
        ];
        yield "3 files 0 issues" => [
            $issue_list, "Test Case #2", "10.0", ["tests" => 3, "failures" => 0, "children" => 3]
        ];

        $issue_list = [
            "file1.php" => [
                new IssueData(
                    "error",
                    10,
                    10,
                    "UndefinedVariable",
                    "Can not find &amp;variable",
                    "file1.php",
                    "file1.php",
                    "\$i++&amp;",
                    "",
                    0,
                    3,
                    10,
                    10,
                    0,
                    3
                ),
                new IssueData(
                    "info",
                    10,
                    10,
                    "UndefinedVariable",
                    "Can not find variable",
                    "file1.php",
                    "file1.php",
                    "\$i++",
                    "",
                    0,
                    3,
                    10,
                    10,
                    0,
                    3
                ),
            ],
            "file2.php" => [
                new IssueData(
                    "error",
                    10,
                    10,
                    "UndefinedVariable",
                    "Can not find variable ->",
                    "file2.php",
                    "file2.php",
                    "\$i->i++",
                    "",
                    0,
                    3,
                    10,
                    10,
                    0,
                    3
                ),
                new IssueData(
                    "error",
                    10,
                    10,
                    "UndefinedVariable",
                    "Can not find \"variable\"",
                    "file2.php",
                    "file2.php",
                    "\$i[\"i\"]++",
                    "",
                    0,
                    3,
                    10,
                    10,
                    0,
                    3
                ),
                new IssueData(
                    "error",
                    10,
                    10,
                    "UndefinedVariable",
                    "Can not find 'variable'",
                    "file2.php",
                    "file2.php",
                    "\$i['i']++ && \$i['i']++",
                    "",
                    0,
                    3,
                    10,
                    10,
                    0,
                    3
                )
            ]
        ];
        yield "2 files 4 issues 1 supressed needs escaping" => [
            $issue_list, "Test Case #3", "15.5", ["tests" => 5, "failures" => 4, "children" => 2]
        ];
    }

    /**
     * Validate generated xml and check contents
     *
     * @dataProvider generateTestCases
     *
     * @param array<string,array<int,IssueData>> $issue_list
     * @param string                             $suite_name
     * @param string                             $time_taken
     * @param array<string,int>                  $expected
     */
    public function testXmlGeneration(array $issue_list, string $suite_name, string $time_taken, array $expected): void
    {
        $xml = JunitReport::createXml($issue_list, $suite_name, $time_taken);
        $this->xmlFileAsserts($xml, $expected);
    }

    /**
     * Test generating report via psalm input
     *
     * @dataProvider generateTestCases
     *
     * @param array<string,array<int,IssueData>> $issue_list
     * @param string                             $suite_name
     * @param string                             $time_taken
     * @param array<string,int>                  $expected
     */
    public function testPsalmInput(array $issue_list, string $suite_name, string $time_taken, array $expected): void
    {
        // Setup vfs
        $filename = uniqid("junit", true) . "xml";
        $vfs = vfsStream::setup(getcwd());
        JunitReport::$filepath = $vfs->url() . DIRECTORY_SEPARATOR . $filename;

        // Setup some variables
        if (!defined("PSALM_VERSION")) {
            define("PSALM_VERSION", "1.2.3");
        }
        JunitReport::$start_time = microtime(true);

        // Mock up the typed parameter
        $prophecy = $this->prophesize(Codebase::class);
        /** @var Codebase */
        $codebase = $prophecy->reveal();
        $other_prophecy = $this->prophesize(Analyzer::class);
        $other_prophecy->getMixedCounts()->will(new ReturnPromise([$issue_list]));
        /** @var Analyzer */
        $analyzer = $other_prophecy->reveal();
        $codebase->analyzer = $analyzer;

        // Reformat input
        $values = array_values($issue_list);
        if (version_compare(PHP_VERSION, "7.4.0", "<")) {
            if (empty($values)) {
                $values = [[]];
            }
        }
        $issue_list = ["key" => array_merge(...$values)];

        // Go
        JunitReport::afterAnalysis(new AfterAnalysisEvent($codebase, $issue_list, [], null));

        // Read the file output
        $this->assertTrue($vfs->hasChild($filename));
        $test = $vfs->getChild($filename);
        assert($test instanceof vfsStreamFile);
        $xml = $test->getContent();
        $this->xmlFileAsserts($xml, $expected);
    }

    /**
     * Asserts on processing the XML that both entry points need
     *
     * @param array<string,array<int,IssueData>> $issue_list
     * @param array<string,int>                  $expected
     */
    public function xmlFileAsserts(string $xml, array $expected): void
    {
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);

        // Validate against xsd
        $valid = $dom->schemaValidate(getcwd() . DIRECTORY_SEPARATOR . "tests" . DIRECTORY_SEPARATOR . "junit.xsd");
        $this->assertTrue($valid, "Output did not validate against XSD");

        // Check against expected values
        $testsuites = $dom->firstChild;
        assert($testsuites instanceof DOMNode);
        $attributes = $testsuites->attributes;
        /** @psalm-suppress TypeDoesNotContainType */
        assert($attributes instanceof DOMNamedNodeMap);
        $attr = $attributes->getNamedItem("tests");
        assert($attr instanceof DOMNode);
        $this->assertEquals($expected["tests"], $attr->nodeValue, "Incorrect total number of tests");
        $attr = $attributes->getNamedItem("failures");
        assert($attr instanceof DOMNode);
        $this->assertEquals($expected["failures"], $attr->nodeValue, "Incorrect total number of test failures");

        // Check children
        $this->assertCount($expected["children"], $testsuites->childNodes, "Child nodes don't match number of files");
    }
}
