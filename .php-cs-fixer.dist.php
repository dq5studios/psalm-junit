<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude("cache/")
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        "@PHP8x1Migration" => true,
        "@PHP8x0Migration:risky" => true,
        "@Symfony" => true,
        "@Symfony:risky" => true,
        "concat_space" => ["spacing" => "one"],
        "global_namespace_import" => ["import_classes" => true, "import_constants" => true, "import_functions" => true],
        "ordered_imports" => ["imports_order" => ["class", "function", "const"]],
        "single_quote" => false,
        "declare_strict_types" => true,
        "phpdoc_to_comment" => ["ignored_tags" => ["psalm-suppress"]],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
