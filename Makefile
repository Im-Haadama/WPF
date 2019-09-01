fresh: languages

languages: wp-content/languages/plugins/im_haadama-he_IL.mo

%.mo: %.po
	msgfmt $? -o $@

