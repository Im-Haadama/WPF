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

