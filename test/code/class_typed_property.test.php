<?php
namespace NS;
class NameOfClass {
    public ?NameOfClass $self;
}
$obj = new NameOfClass();
-----
Block#1
    Stmt_Class
        name: LITERAL('NS\\NameOfClass')
        stmts: Block#2
    Expr_New
        class: LITERAL('NS\\NameOfClass')
        result: Var#1
    Expr_Assign
        var: Var#2<$obj>
        expr: Var#1
        result: Var#3
    Terminal_Return
        expr: LITERAL(1)

Block#2
    Stmt_Property<NS\NameOfClass|null>
        name: LITERAL('self')