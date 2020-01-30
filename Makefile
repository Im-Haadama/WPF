.languages: wp-content/plugins/fresh/languages/wpf-he_IL.mo
	touch $@

%.mo: %.po
	msgfmt $? -o $@
