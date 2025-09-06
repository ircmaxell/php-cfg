SHELL := /bin/bash

targets=$(shell for file in `find . -name '*Test.php' -type f -printf "%P\n"`; do echo "$$file "; done;)


.PHONY: build
build: cs-fix testbuild

.PHONY: cs-fix
cs-fix:
	vendor/bin/php-cs-fixer fix

.PHONY: testbuild
testbuild:
	XDEBUG_MODE=coverage vendor/bin/phpunit --display-deprecations

.PHONY: test
test:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html ./coverage --display-deprecations

.PHONY: t
t:

all: $(targets)

%Test.php: t
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --display-deprecations $@