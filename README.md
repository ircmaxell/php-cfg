[![Build Status](https://travis-ci.org/ircmaxell/php-cfg.svg?branch=master)](https://travis-ci.org/ircmaxell/php-cfg)

# PHP-CFG

A Control-Flow-Graph implementation in Pure PHP.

## Usage

To bootstrap the parser, you need to give it a `PhpParser` instance:
```php
$parser = new PHPCfg\Parser(
    new PhpParser\Parser(new PhpParser\Lexer)
); 
```
Then, just call parse on a block of code, giving it a filename:
```php
$block = $parser->parse(file_get_contents(__FILE__), __FILE__);
```
To dump the graph, simply use the built-in dumper:
```php
$dumper = new PHPCfg\Dumper;
echo $dumper->dump($block);
```
