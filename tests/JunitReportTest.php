<?php

declare(strict_types=1);

namespace DQ5Studios\PsalmJunit\Tests;

use DQ5Studios\PsalmJunit\JunitReport;
use PHPUnit\Framework\TestCase;
use DOMDocument;
use DOMNamedNodeMap;
use DOMNode;
use Generator;

/**
 * @psalm-type  IssueData = array{
 *     severity: string,
 *     line_from: int,
 *     line_to: int,
 *     type: string,
 *     message: string,
 *     file_name: string,
 *     file_path: string,
 *     snippet: string,
 *     from: int,
 *     to: int,
 *     snippet_from: int,
 *     snippet_to: int,
 *     column_from: int,
 *     column_to: int
 * }
 */

class JunitReportTest extends TestCase
{
    /**
     * Generate issue lists for processing
     *
     * @return Generator<string,array,mixed,void>
     */
    public function generateTestCases(): Generator
    {
        /** @var array<string,IssueData[]> */
        $issue_list = [];
        yield "Empty list" => [
            $issue_list, "Test Case #1", "0.0", ["tests" => 0, "failures" => 0, "children" => 0]
        ];

        /** @var array<string,IssueData[]> */
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
                [
                    "severity" => "error",
                    "line_from" => 10,
                    "line_to" => 10,
                    "type" => "UndefinedVariable",
                    "message" => "Can not find variable",
                    "file_name" => "file1.php",
                    "file_path" => "file1.php",
                    "snippet" => "\$i++",
                    "from" => 0,
                    "to" => 3,
                    "snippet_from" => 10,
                    "snippet_to" => 10,
                    "column_from" => 0,
                    "column_to" => 3
                ],
                [
                    "severity" => "info",
                    "line_from" => 10,
                    "line_to" => 10,
                    "type" => "UndefinedVariable",
                    "message" => "Can not find variable",
                    "file_name" => "file1.php",
                    "file_path" => "file1.php",
                    "snippet" => "\$i++",
                    "from" => 0,
                    "to" => 3,
                    "snippet_from" => 10,
                    "snippet_to" => 10,
                    "column_from" => 0,
                    "column_to" => 3
                ],
            ],
            "file2.php" => [
                [
                    "severity" => "error",
                    "line_from" => 10,
                    "line_to" => 10,
                    "type" => "UndefinedVariable",
                    "message" => "Can not find variable ->",
                    "file_name" => "file1.php",
                    "file_path" => "file1.php",
                    "snippet" => "\$i->i++",
                    "from" => 0,
                    "to" => 3,
                    "snippet_from" => 10,
                    "snippet_to" => 10,
                    "column_from" => 0,
                    "column_to" => 3
                ],
                [
                    "severity" => "error",
                    "line_from" => 10,
                    "line_to" => 10,
                    "type" => "UndefinedVariable",
                    "message" => "Can not find \"variable\"",
                    "file_name" => "file1.php",
                    "file_path" => "file1.php",
                    "snippet" => "\$i[\"i\"]++",
                    "from" => 0,
                    "to" => 3,
                    "snippet_from" => 10,
                    "snippet_to" => 10,
                    "column_from" => 0,
                    "column_to" => 3
                ],
                [
                    "severity" => "error",
                    "line_from" => 10,
                    "line_to" => 10,
                    "type" => "UndefinedVariable",
                    "message" => "Can not find 'variable'",
                    "file_name" => "file1.php",
                    "file_path" => "file1.php",
                    "snippet" => "\$i['i']++",
                    "from" => 0,
                    "to" => 3,
                    "snippet_from" => 10,
                    "snippet_to" => 10,
                    "column_from" => 0,
                    "column_to" => 3
                ]
            ]
        ];
        yield "2 files 4 issues 1 supressed with escaping" => [
            $issue_list, "Test Case #3", "15.5", ["tests" => 5, "failures" => 4, "children" => 2]
        ];
    }

    /**
     * Validate generated xml and check contents
     *
     * @dataProvider generateTestCases
     *
     * @param array<string,IssueData[]> $issue_list
     * @param string                    $suite_name
     * @param string                    $time_taken
     * @param array<string,int>         $expected
     */
    public function testXmlGeneration(array $issue_list, string $suite_name, string $time_taken, array $expected): void
    {
        $xml = JunitReport::createXml($issue_list, $suite_name, $time_taken);
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
