[![Build Status](https://travis-ci.com/dq5studios/psalm-junit.svg?branch=master)](https://travis-ci.com/dq5studios/psalm-junit)
[![codecov](https://codecov.io/gh/dq5studios/psalm-junit/branch/master/graph/badge.svg)](https://codecov.io/gh/dq5studios/psalm-junit)

# psalm-junit

A Junit report plugin for [Psalm](https://github.com/vimeo/psalm) (requires Psalm v3).

Installation:

```console
composer require --dev dq5studios/psalm-junit
vendor/bin/psalm-plugin enable dq5studios/psalm-junit
```

## Usage

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
