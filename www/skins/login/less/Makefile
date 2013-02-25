AUTHENTICATION_LESS = ./style.less
AUTHENTICATION_CSS = ./../css/style.css
DATE=$(shell date +%I:%M%p)
CHECK=\033[32m✔\033[39m
HR=\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#


#
# BUILD DOCS
#

build:
	@echo "\n${HR}"
	@echo "Building Authenticaton Assets..."
	@echo "${HR}\n"
	recess --compile  ${AUTHENTICATION_LESS} > ${AUTHENTICATION_CSS}
	@echo "${CHECK} Done"

#
# WATCH LESS FILES
#

watch:
	echo "Watching less files..."; \
	watchr -e "watch('less/.*\.less') { system 'make' }"
