PROJECT := tequila
VERSION := 0.1

SRC_DIR   ?= src
TESTS_DIR ?= tests

################################################################################

PHPDOC  ?= phpdoc --sourcecode
PHPUNIT ?= phpunit --colors

MKDIR := mkdir --parents --
RMDIR := rmdir --parents --ignore-fail-on-non-empty --

################################################################################

prefix      ?= /usr/local
exec_prefix ?= $(prefix)
bindir      ?= $(exec_prefix)/bin
datarootdir ?= $(prefix)/share
sysconfdir  ?= /etc
docrootdir  ?= $(datarootdir)/doc

confdir ?= $(sysconfdir)/$(PROJECT)
datadir ?= $(datarootdir)/$(PROJECT)
docdir  ?= $(docrootdir)/$(PROJECT)

################################################################################

.DEFAULT_GOAL: all
.PHONY: all codecoverage distcheck doc install
.SILENT:

all:
	echo 'Nothing to compile!'
	echo
	echo 'Available commands:'
	echo '- codecoverage'
	echo '- distcheck'
	echo '- doc'
	echo '- install'

codecoverage:
	$(PHPUNIT) --bootstrap $(TESTS_DIR)/bootstrap.php --coverage-text $(TESTS_DIR)

distcheck:
	$(PHPUNIT) --bootstrap $(TESTS_DIR)/bootstrap.php --verbose -- $(TESTS_DIR)

doc:
	$(PHPDOC) --directory $(SRC_DIR),$(TESTS_DIR) --target $(docdir)\
		--defaultpackagename $(PROJECT) | awk /WARNING/

install:
	$(MAKE) -C libs/gallic install
	$(MKDIR) $(datadir)
	cp -r $(SRC_DIR)/* $(datadir)/
	$(MKDIR) $(bindir)
	ln -s --target-directory=$(bindir) $(datadir)/tequila
	$(MKDIR) $(confdir)
	cp config.ini $(confdir)
	cp -r commands/ $(confdir)
