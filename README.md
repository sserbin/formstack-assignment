# How to run
* `vagrant up`
* `echo 192.168.59.76   testbox.dev www.testbox.dev | sudo tee -a /etc/hosts`
* `vagrant ssh`
* `sudo sed -i 's/root \/vagrant;/root \/vagrant\/public;/' /etc/nginx/sites-available/default`
* `cd /vagrant`
* `composer install`
* `vendor/bin/phinx migrate`
* `sudo mkdir /tmp/avatar && sudo chmod -R a+rw /tmp/avatar && ln -s /tmp/avatar public/avatar`
* to run tests/static-analysis: `composer ci-check`

## Design choices / implementation notes
* Conformance to psr-2 is asserted with phpcs
* Phinx: phinx migrations are used as per requirements
* league/container: a simple lightweight DI container
* Uuids as ids: a popular choice for entity ids in DDD.
  Allows constructing domain entities without being dependant on infrastructure concerns (e.g. sequence generators) or entities being unusable (missing ids) until the moment they are actually persisted (e.g. with auto-increment ids). As a side benefit, uuids do not require central coordination (like auto-increment ids do) in a distributed environment.
  Storage considerations: ids are stored as binary(16) as a storage optimization. A further optimization would be a use of ordered-time uuid modification as discussed in ([percona blog post](https://www.percona.com/blog/2014/12/19/store-uuid-optimized-way/)). For simplicity reasons though this is not implemented.
* Data mapper: a naive implementation of data mapper pattern is implemented as data access layer abstraction. Data mapper's goal is to mediate (and map) between domain model and data store represantation.
  Possible future improvements to this particular implementation would be: identity map, repositories on top of data mapper and unit of work.
* Slim: lightweight psr-message/router micro-framework for handling api endpoints
* Integration test: in addition to entity unit tests, there're integration tests for api endpoints that assert the endpoints conform to specification. These are almost functional-level apart from mocking http stack.
