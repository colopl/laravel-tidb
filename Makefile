BASE_COMMAND=docker-compose -p $(shell basename $(CURDIR))

build:
	$(BASE_COMMAND) build

up:
	$(BASE_COMMAND) run test /bin/sh

down:
	$(BASE_COMMAND) down --remove-orphans

test: build
	$(BASE_COMMAND) run test
	$(BASE_COMMAND) down --remove-orphans

update:
	$(BASE_COMMAND) run test composer update
	$(BASE_COMMAND) down --remove-orphans

bash:
	$(BASE_COMMAND) run test /bin/sh

tidb:
	$(BASE_COMMAND) exec tidb /bin/bash

logs:
	$(BASE_COMMAND) logs
