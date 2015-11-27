.PHONY: i18n

PO_FILES := $(wildcard locale/*/LC_MESSAGES/phraseanet.po)
PO_FILES += $(wildcard locale/*/LC_MESSAGES/test.po)

i18n: $(PO_FILES:po=mo)

%.mo: %.po
	msgfmt $< -o $@

