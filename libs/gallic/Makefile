PROJECT := gallic
VERSION := 0.2-extract

SRC_DIR   ?= src
TESTS_DIR ?= tests

################################################################################

MKDIR := mkdir --parents --
RMDIR := rmdir --parents --ignore-fail-on-non-empty --

################################################################################

datadir ?= /usr/share/php5/lib

################################################################################

.DEFAULT_GOAL: all
.PHONY: all install
.SILENT:

all:
	echo 'Nothing to compile!'
	echo
	echo 'Available commands:'
	echo '- install'

install:
	$(MKDIR) $(datadir)
	cp -r $(SRC_DIR)/* $(datadir)/
