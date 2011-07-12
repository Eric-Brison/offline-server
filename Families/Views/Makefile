# ============================================
# $Id: Makefile,v 1.1 2010/01/15 15:16:39 eric Exp $
#
# ============================================


include $(utildir)/PubRule
pages_html = $(patsubst %html,$(pubdir)/$(appname)/Layout/%html,$(wildcard *html))

$(pubdir)/$(appname)/Layout:
	mkdir $@

$(pubdir)/$(appname)/Layout/%html: %html $(pubdir)/$(appname)/Layout
	cp $< $@

publish : $(pages_html)
