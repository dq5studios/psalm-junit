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
    /** @var float $start_time Close enough of a start time */
    public static $start_time = 0.0;

    /**
     * {@inheritDoc}
     */
    public static function afterAnalysis(
        Codebase $codebase,
        array $issues,
        array $build_info,
        SourceControlInfo $source_control_info = null
    ) {
        // Global totals
        $test_count = 0;
        $failure_count = 0;
        if (!empty($issues)) {
            $severity_list = array_count_values(array_column($issues, "severity"));
            $test_count = count($issues);
            if (isset($severity_list["error"])) {
                $failure_count = $severity_list["error"];
            }
        }
        $suite_name = "Psalm " . PSALM_VERSION;
        $time_taken = number_format(microtime(true) - self::$start_time, 2);

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

        $xml = self::createXml($processed_file_list, $suite_name, $test_count, $failure_count, $time_taken);
        file_put_contents(self::$filepath, $xml);
    }

    /**
     * Create an XML string out of the data
     *
     * @param array<string,IssueData[]> $issue_suite
     * @param string                    $suite_name
     * @param int                       $test_count
     * @param int                       $failure_count
     * @param string                    $time_taken
     *
     * @return string
     */
    public static function createXml(
        array $issue_suite,
        string $suite_name,
        int $test_count,
        int $failure_count,
        string $time_taken
    ): string {
        // <testsuites> parent element
        $dom = new DOMDocument("1.0", "UTF-8");
        $dom->formatOutput = true;
        $testsuites = $dom->createElement("testsuites");
        $testsuites->setAttribute("name", $suite_name);
        $testsuites->setAttribute("failures", (string) $failure_count);
        $testsuites->setAttribute("tests", (string) $test_count);
        $testsuites->setAttribute("errors", "0");
        $testsuites->setAttribute("time", $time_taken);
        $dom->appendChild($testsuites);

        foreach ($issue_suite as $file_path => $issue_list) {
            $testsuite = JunitReport::makeTestsuite($issue_list, $dom, $file_path);
            $testsuites->appendChild($testsuite);
        }

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
            $testcase->setAttribute("file", $file_path);
            $testcase->setAttribute("classname", $classname);
            $testsuite->appendChild($testcase);
        }

        // Lots of errors in this file
        foreach ($issue_list as $issue) {
            $testcase = JunitReport::makeTestcase($issue, $dom, $failure_count, $file_path, $classname);
            $testsuite->appendChild($testcase);
        }

        // <testsuite> file report element
        $testsuite->setAttribute("failures", (string) $failure_count);
        $testsuite->setAttribute("tests", (string) $file_test_count);
        $testsuite->setAttribute("errors", "0");
        return $testsuite;
    }

    /**
     * Create testcase element
     *
     * @param IssueData   $issue     Issue info
     * @param DOMDocument $dom       Source DOM
     * @param int         $failures  Number of failures
     * @param string      $file_path File being processed
     * @param string      $classname Classname label
     *
     * @return DOMElement Testcase element
     */
    public static function makeTestcase(
        array $issue,
        DOMDocument $dom,
        int &$failures,
        string $file_path,
        string $classname
    ): DOMElement {
        $testcase = $dom->createElement("testcase");
        $name = "{$issue["type"]} at {$file_path} ({$issue["line_from"]}:{$issue["column_from"]})";
        $testcase->setAttribute("name", $name);
        $testcase->setAttribute("file", $file_path);
        $testcase->setAttribute("classname", $classname);
        $testcase->setAttribute("line", (string) $issue["line_from"]);
        $message = htmlspecialchars($issue["message"], ENT_XML1 | ENT_QUOTES);
        $snippet = "{$issue["severity"]}: {$issue["type"]} - ";
        $snippet .= "{$file_path}:{$issue["line_from"]}:{$issue["column_from"]} - {$message}\n";
        $snippet_lines = explode("\n", $issue["snippet"]);
        $from = (int) $issue["line_from"];
        foreach ($snippet_lines as $line) {
            $snippet .= (string) $from . ":" . htmlspecialchars($line, ENT_XML1 | ENT_QUOTES) . "\n";
            $from++;
        }

        if ($issue["severity"] == "error") {
            $failures++;
            $failure = $testcase->ownerDocument->createElement("failure", $snippet);
            $failure->setAttribute("type", $issue["severity"]);
            $failure->setAttribute("message", $message);
            $testcase->appendChild($failure);
        } else {
            $skipped = $testcase->ownerDocument->createElement("skipped", $snippet);
            $skipped->setAttribute("message", $message);
            $testcase->appendChild($skipped);
        }

        return $testcase;
    }
}
