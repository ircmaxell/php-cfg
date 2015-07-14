#!/bin/bash

vendor/bin/php-cs-fixer fix lib --fixers=linefeed,indentation,elseif,line_after_namespace,lowercase_constants,lowercase_keywords,method_argument_space,single_line_after_imports,visibility,trailing_spaces
vendor/bin/php-cs-fixer fix test --fixers=linefeed,indentation,elseif,line_after_namespace,lowercase_constants,lowercase_keywords,method_argument_space,single_line_after_imports,visibility,trailing_spaces

vendor/bin/phpunit