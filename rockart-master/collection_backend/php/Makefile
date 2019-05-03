SRV=176.9.17.176
TEST_RSH='192.168.122.7.mitte.topoi'

# clear cache (locally)
cc:
	rm -f cache/*
# clear cache on test server
cc-test:
	ssh topoi-et-test 'rm -f /var/www/html/cache/*'
# clear cache on production server
cc-online:
	ssh root@$(SRV) 'rm -f /var/www/editiontopoirepo/cache/*'

# sync files with production server
publish:
	rsync -vbrtc --exclude-from=.rsyncexclude_publish . root@$(SRV):/var/www/editiontopoirepo/

# sync files with test server
test:
	rsync -vbrtc --exclude-from=.rsyncexclude_test . root@$(TEST_RSH):/var/www/html/

