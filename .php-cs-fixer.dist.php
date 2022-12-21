<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude("cache/")
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        "@PHP74Migration" => true,
        "@PHP74Migration:risky" => true,
        "@Symfony" => true,
        "@Symfony:risky" => true,
        "class_definition" => ["space_before_parenthesis" => true],
        "concat_space" => ["spacing" => "one"],
        "global_namespace_import" => ["import_classes" => true, "import_constants" => true, "import_functions" => true],
        "ordered_imports" => ["imports_order" => ["class", "function", "const"]],
        "phpdoc_to_comment" => ["ignored_tags" => ["var"]],
        "single_quote" => false,
    ])
    ->setFinder($finder)
;
