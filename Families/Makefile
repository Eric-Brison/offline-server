# ============================================
# $Id: Makefile,v 1.3 2007/12/21 14:44:55 eric Exp $
#
# ============================================

SUBDIR= Views
include $(utildir)/PubRule

pages_fdl = $(patsubst %.php,$(pubdir)/$(applib)/%.php,$(wildcard Method*.php))
pages_fdl += $(patsubst %.php,$(pubdir)/$(applib)/%.php,$(wildcard Class*.php))
pages_csv = $(patsubst %.csv,$(pubdir)/$(style)/$(appname)/%.csv,$(filter-out $(pages_not_csv),$(wildcard *.csv)))


$(pubdir)/$(applib):
	mkdir $@

$(pubdir)/$(applib)/%.php: %.php $(pubdir)/$(applib)
	cd $(pubdir)/$(applib); \
	ln -sf ../$(appname)/$< .

$(pubdir)/$(style)/$(appname)/%.csv : %.csv $(pubdir)/$(style)/$(appname)
	$(installcp) $< $@

publish: $(pubdir)/$(applib) $(pages_fdl) $(pages_csv)
