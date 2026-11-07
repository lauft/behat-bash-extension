test:
	composer install
	mkdir -p /tmp/bbe-test
	TMPDIR=/tmp/bbe-test vendor/bin/behat --no-colors

.PHONY: test
