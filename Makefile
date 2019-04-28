.PHONY: test

test:
	docker run -it --rm -v ${PWD}:/var/www/html/extensions/PubmedParser bovender/pubmedparser

build-test-container:
	docker build -t bovender/pubmedparser .
