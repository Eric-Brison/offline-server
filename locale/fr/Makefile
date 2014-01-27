pages_mo = $(patsubst %.po,$(localedir)/fr/LC_MESSAGES/%.mo,$(wildcard *.po))

include $(utildir)/PubRule

$(localedir)/fr/LC_MESSAGES/%.mo:  /var/tmp/%.gmo  $(localedir)/fr/LC_MESSAGES $(wildcard *.po)
	$(installcp) $< $@
