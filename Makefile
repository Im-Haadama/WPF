
FOLDER_LIST=tools,niver

# major version
VERSION := $(shell cat version)
# the current tar file
BUILD_VERSION := $(shell ./build_version_number)
# the next file
PREVIOUS_TAR := $(shell ./last_build_file)

all: .languages

#######################
## Build the version ##
#######################

.fresh: .languages
	touch $@

.languages: wp-content/languages/plugins/im_haadama-he_IL.mo
	touch $@

%.mo: %.po
	msgfmt $? -o $@

############
## Deploy ##
############

UPLOAD=utils/upload_version.sh

.all_deploy: ftp.aglamaz.com.dep
	touch $@

%.dep: .fresh versions/$BUILD_VERSION.tar
	$(UPLOAD) $*
	touch $@

%.tar:

