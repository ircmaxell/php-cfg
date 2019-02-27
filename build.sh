#!/bin/bash

vendor/bin/php-cs-fixer fix --allow-risky=true
vendor/bin/phpunit
