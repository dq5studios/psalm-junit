<?php

declare(strict_types=1);

namespace DQ5Studios\PsalmJunit\Tests;

use DQ5Studios\PsalmJunit\JunitReport;
use PHPUnit\Framework\TestCase;
use DOMDocument;

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
    public function testXmlGeneration(): void
    {
        $suite_name = "Psalm";
        $failure_count = 0;
        $time_taken = "0";
        $file_name = basename(__FILE__);
        /** @var array<string,IssueData[]> */
        $filelist = [
            $file_name => [
                [
                    "severity" => "error",
                    "line_from" => 10,
                    "line_to" => 10,
                    "type" => "UndefinedVariable",
                    "message" => "Can not find variable",
                    "file_name" => $file_name,
                    "file_path" => $file_name,
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
                    "file_name" => $file_name,
                    "file_path" => $file_name,
                    "snippet" => "\$i++",
                    "from" => 0,
                    "to" => 3,
                    "snippet_from" => 10,
                    "snippet_to" => 10,
                    "column_from" => 0,
                    "column_to" => 3
                ]
            ]
        ];
        $test_count = count($filelist);
        $xml = JunitReport::createXml($filelist, $suite_name, $test_count, $failure_count, $time_taken);
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->loadXML($xml);
        $valid = $dom->schemaValidate(getcwd() . DIRECTORY_SEPARATOR . "tests" . DIRECTORY_SEPARATOR . "junit.xsd");
        $this->assertTrue($valid);
    }
}
