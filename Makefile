#######################
## Build the version ##
#######################

.fresh: .languages
	touch $@

.languages: wp-content/languages/plugins/im_haadama-he_IL.mo
	touch $@

############
## Deploy ##
############
UPLOAD=utils/upload_version.sh

.all_deploy: ftp.aglamaz.com.dep
	touch $@

%.dep: .fresh
	$(UPLOAD) $*
	touch $@

#deploy signature.
.%/done: .%/lang
	touch $@

.%/lang: .%/wp-content/languages/plugins/im_haadama-he_IL.mor
	touch $@

%.mor: %.mo
	upload.sh $? -o

%.mo: %.po
	msgfmt $? -o $@

#%.rmo: %.po
#	upload.sh $? || touch $@
#
#
#	 .r.aglamaz.com/wp-content/languages/plugins/im_haadama-he_IL.po
# hosts = aglamaz.com fruity.co.il tasks.work super-organi.co.il
