[![Build Status](https://travis-ci.org/ircmaxell/php-cfg.svg?branch=master)](https://travis-ci.org/ircmaxell/php-cfg)

# PHP-CFG

Pure PHP implementation of a control flow graph (CFG) with instructions in static single assignment (SSA) form.

The used SSA construction algorithm is based on "Simple and Efficient Construction of Static Single Assignment Form" by
Braun et al. This algorithm constructs SSA form directly from the abstract syntax tree, without going through a non-SSA
IR first. If you're looking for dominance frontiers, you won't find them here...

The constructed SSA form is minimal and pure (or is supposed to be).

## Usage

To bootstrap the parser, you need to give it a `PhpParser` instance:
```php
$parser = new PHPCfg\Parser(
    (new PhpParser\ParserFactory)->create(PhpParser\ParserFactory::PREFER_PHP7)
);
```
Then, just call parse on a block of code, giving it a filename:
```php
$script = $parser->parse(file_get_contents(__FILE__), __FILE__);
```
To dump the graph, simply use the built-in dumper:
```php
$dumper = new PHPCfg\Printer\Text();
echo $dumper->printScript($script);
```
