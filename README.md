[![codecov](https://codecov.io/gh/dq5studios/psalm-junit/branch/master/graph/badge.svg)](https://codecov.io/gh/dq5studios/psalm-junit)
[![shepherd](https://shepherd.dev/github/dq5studios/psalm-junit/coverage.svg)](https://shepherd.dev/github/dq5studios/psalm-junit)
![Packagist Version](https://img.shields.io/packagist/v/dq5studios/psalm-junit)
![PHP from Packagist](https://img.shields.io/packagist/php-v/dq5studios/psalm-junit)
![Packagist](https://img.shields.io/packagist/dm/dq5studios/psalm-junit)

# psalm-junit

A Junit report plugin for [Psalm](https://github.com/vimeo/psalm).

Installation:

```console
composer require --dev dq5studios/psalm-junit
vendor/bin/psalm-plugin enable dq5studios/psalm-junit
```

## Usage

While enabled, the plugin will generate a JUnit formatted file named `psalm_junit_report.xml` every time Psalm
is run without the `--report` commandline option.

## Configuration

The plugin defaults to writing `psalm_junit_report.xml` in the current working directory. To change this, edit the
plugin's settings in `psalm.xml` to add a `filepath` element.  All paths are relative to the current working directory.

```xml
<plugins>
    <pluginClass class="DQ5Studios\PsalmJunit\Plugin">
        <filepath>./reports/junit.xml</filepath>
    </pluginClass>
</plugins>
```

The report will not be written if another report is being requested with the `--report` commandline option.  To override
this and always generate the report, edit the plugin's settings in `psalm.xml` to add an `always` element.

```xml
<plugins>
    <pluginClass class="DQ5Studios\PsalmJunit\Plugin">
        <always />
    </pluginClass>
</plugins>
```

If the command line option `--report-show-info` is set or `showInfo` is defined in the config, then the value is used
to control if info level issues will be included in the output nor in the issue totals.  The default is to include them.

```xml
<plugins>
    <pluginClass class="DQ5Studios\PsalmJunit\Plugin">
        <showInfo>false</showInfo>
    </pluginClass>
</plugins>
```

If `showSnippet` is defined in the config, the value is used to control if snippets will be included in the report.
The default is to include them.

```xml
<plugins>
    <pluginClass class="DQ5Studios\PsalmJunit\Plugin">
        <showSnippet>false</showSnippet>
    </pluginClass>
</plugins>
```
