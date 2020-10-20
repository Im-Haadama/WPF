.languages: wp-content/plugins/finance/languages/finance-he_IL.mo \
	wp-content/plugins/flavor/languages/e-fresh-he_IL.mo
	touch $@

%.mo: %.po
	msgfmt $? -o $@
