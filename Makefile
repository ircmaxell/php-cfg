.PHONY: build
build: cs-fix test

.PHONY: cs-fix
cs-fix:
	vendor/bin/php-cs-fixer fix

.PHONY: test
test:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text


