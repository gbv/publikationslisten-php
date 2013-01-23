PHONY=phpdoc-installed

doc: phpdoc-installed
	@phpdoc -f publikationsliste.php -d beispiele -t doc --title Publikationslisten --template=new-black

phpdoc-installed:
	@command -v phpdoc > /dev/null
