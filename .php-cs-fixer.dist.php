<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude("cache/")
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        "@PHP81Migration" => true,
        "@PHP80Migration:risky" => true,
        "@Symfony" => true,
        "@Symfony:risky" => true,
        "concat_space" => ["spacing" => "one"],
        "global_namespace_import" => ["import_classes" => true, "import_constants" => true, "import_functions" => true],
        "ordered_imports" => ["imports_order" => ["class", "function", "const"]],
        "single_quote" => false,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;
