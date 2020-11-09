.languages: wp-content/plugins/fresh/languages/fresh-he_IL.mo
	touch $@

%.mo: %.po
	msgfmt $? -o $@
