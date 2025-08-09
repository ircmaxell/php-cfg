[![Build Status](https://github.com/ircmaxell/php-cfg/actions/workflows/main.yml/badge.svg)](https://github.com/ircmaxell/php-cfg/actions)
[![Latest Stable Version](https://poser.pugx.org/ircmaxell/php-cfg/v/stable)](https://packagist.org/packages/ircmaxell/php-cfg)

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
    (new PhpParser\ParserFactory)->createForNewestSupportedVersion()
);
```
Then, just call parse on a block of code, giving it a filename:
```php
$script = $parser->parse(file_get_contents(__FILE__), __FILE__);
```
While not strictly necessary, you likely should also run the Simplifier Visitor via the Traverser to optimize the CFG (remove redundant jumps and blocks, simplify Phi nodes as much as possible, etc). Other visitors exist to help find function and class declarations (`PHPCfg\Visitor\DeclarationFinder`), find function and method calls (`PHPCfg\Visitor\CallFinder`), and find all variables (`PHPCfg\Visitor\VariableFinder`). 

You can also implement your own custom `PHPCfg\Visitor` and add it to the traverser in order to apply analysis or transforms to the CFG to achieve different results. 

```php
$traverser = new PHPCfg\Traverser();
$traverser->addVisitor(new PHPCfg\Visitor\Simplifier());
$traverser->traverse($script);
```
To dump the graph, simply use the built-in dumper:
```php
$dumper = new PHPCfg\Printer\Text();
echo $dumper->printScript($script);
```
