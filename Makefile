test:
	composer install
	TMPDIR=/tmp/bbe-test vendor/bin/behat --no-colors

.PHONY: test
