<?php

declare(strict_types=1);

namespace DQ5Studios\PsalmJunit;

use DOMDocument;
use DOMElement;
use Psalm\Codebase;
use Psalm\Plugin\Hook\AfterAnalysisInterface;
use Psalm\SourceControl\SourceControlInfo;

use const PSALM_VERSION;

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

class JunitReport implements AfterAnalysisInterface
{
    /** @var string $file Output filepath */
    public static $filepath = "psalm_junit_report.xml";
    /** @var bool $show_info Include info level issues */
    public static $show_info = true;
    /** @var bool $show_info Include snippets */
    public static $show_snippet = true;
    /** @var float $start_time Close enough of a start time */
    public static $start_time = 0.0;
    /** @var int $test_count Total count of tests */
    public static $test_count = 0;
    /** @var int $failure_count Total failed tests */
    public static $failure_count = 0;

    /**
     * {@inheritDoc}
     */
    public static function afterAnalysis(
        Codebase $codebase,
        array $issues,
        array $build_info,
        SourceControlInfo $source_control_info = null
    ) {
        // Reformat the data to group by file
        /** @psalm-suppress InternalMethod */
        $analyzer_list = $codebase->analyzer->getMixedCounts();
        $analyzer_list = array_keys($analyzer_list);
        $cwd = getcwd();
        $analyzer_list = array_map(function (string $file_path) use ($cwd) {
            return str_replace($cwd . DIRECTORY_SEPARATOR, "", $file_path);
        }, $analyzer_list);
        /** @var array<string,IssueData[]> */
        $processed_file_list = array_fill_keys($analyzer_list, []);
        foreach ($issues as $issue_detail) {
            $key = $issue_detail["file_name"];
            if (!array_key_exists($key, $processed_file_list)) {
                $processed_file_list[$key] = [];
            }
            array_push($processed_file_list[$key], $issue_detail);
        }

        $suite_name = "Psalm " . PSALM_VERSION;
        $time_taken = number_format(microtime(true) - self::$start_time, 2);

        $xml = self::createXml($processed_file_list, $suite_name, $time_taken);
        file_put_contents(self::$filepath, $xml);
    }

    /**
     * Create an XML string out of the data
     *
     * @param array<string,IssueData[]> $issue_suite
     * @param string                    $suite_name
     * @param string                    $time_taken
     *
     * @return string
     */
    public static function createXml(
        array $issue_suite,
        string $suite_name,
        string $time_taken
    ): string {
        // Initialize counters
        self::$test_count = 0;
        self::$failure_count = 0;
        // <testsuites> parent element
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->formatOutput = true;
        $testsuites = $dom->createElement("testsuites");
        $testsuites->setAttribute("name", $suite_name);
        $testsuites->setAttribute("time", $time_taken);
        $dom->appendChild($testsuites);

        foreach ($issue_suite as $file_path => $issue_list) {
            $testsuite = self::makeTestsuite($issue_list, $dom, $file_path);
            $testsuites->appendChild($testsuite);
        }
        $testsuites->setAttribute("failures", (string) self::$failure_count);
        $testsuites->setAttribute("tests", (string) self::$test_count);
        $testsuites->setAttribute("errors", "0");

        return $dom->saveXML();
    }

    /**
     * Create testsuite element
     *
     * @param IssueData[] $issue_list All issues for this file
     * @param DOMDocument $dom        Source DOM
     * @param string      $file_path  File being processed
     *
     * @return DOMElement Testsuite element
     */
    public static function makeTestsuite(array $issue_list, DOMDocument $dom, string $file_path): DOMElement
    {
        $failure_count = 0;
        $file_test_count = count($issue_list);
        $classname = pathinfo(str_replace(DIRECTORY_SEPARATOR, ".", $file_path), PATHINFO_FILENAME);

        // Build <testcase> elements
        $testsuite = $dom->createElement("testsuite");
        $testsuite->setAttribute("name", $file_path);

        // No errors in this file
        if (empty($issue_list)) {
            $file_test_count = 1;
            $testcase = $testsuite->ownerDocument->createElement("testcase");
            $testcase->setAttribute("name", $file_path);
            $testcase->setAttribute("classname", $classname);
            $testsuite->appendChild($testcase);
        }

        // Lots of errors in this file
        foreach ($issue_list as $issue) {
            $testcase = self::makeTestcase($issue, $dom, $failure_count, $file_path);
            $testsuite->appendChild($testcase);
        }

        if (!self::$show_info && !empty($issue_list)) {
            $file_test_count = $failure_count;
        }

        // <testsuite> file report element
        $testsuite->setAttribute("failures", (string) $failure_count);
        $testsuite->setAttribute("tests", (string) $file_test_count);
        $testsuite->setAttribute("errors", "0");
        self::$test_count += $file_test_count;
        self::$failure_count += $failure_count;
        return $testsuite;
    }

    /**
     * Create testcase element
     *
     * @param IssueData   $issue     Issue info
     * @param DOMDocument $dom       Source DOM
     * @param int         $failures  Number of failures
     * @param string      $file_path File being processed
     *
     * @return DOMElement Testcase element
     */
    public static function makeTestcase(
        array $issue,
        DOMDocument $dom,
        int &$failures,
        string $file_path
    ): DOMElement {
        $classname = pathinfo(str_replace(DIRECTORY_SEPARATOR, ".", $file_path), PATHINFO_FILENAME);
        $testcase = $dom->createElement("testcase");
        $name = "{$issue["type"]} at {$file_path} ({$issue["line_from"]}:{$issue["column_from"]})";
        $testcase->setAttribute("name", $name);
        $testcase->setAttribute("classname", $classname);
        $message = htmlspecialchars($issue["message"], ENT_XML1 | ENT_QUOTES);
        $snippet = "{$issue["severity"]}: {$issue["type"]} - ";
        $snippet .= "{$file_path}:{$issue["line_from"]}:{$issue["column_from"]} - {$message}\n";
        if (self::$show_snippet) {
            $snippet_lines = explode("\n", $issue["snippet"]);
            $from = (int) $issue["line_from"];
            foreach ($snippet_lines as $line) {
                $snippet .= (string) $from . ":" . htmlspecialchars($line, ENT_XML1 | ENT_QUOTES) . "\n";
                $from++;
            }
        }

        if ($issue["severity"] == "error") {
            $failures++;
            $failure = $testcase->ownerDocument->createElement("failure", $snippet);
            $failure->setAttribute("type", $issue["severity"]);
            $failure->setAttribute("message", $message);
            $testcase->appendChild($failure);
        } elseif (self::$show_info) {
            $skipped = $testcase->ownerDocument->createElement("skipped", $snippet);
            $testcase->appendChild($skipped);
        }

        return $testcase;
    }
}
