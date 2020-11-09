.languages: wp-content/plugins/flavor/languages/e-fresh-he_IL.mo
	touch $@

%.mo: %.po
	msgfmt $? -o $@
