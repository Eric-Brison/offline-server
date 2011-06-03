
#include $(utildir)/PubRule

pages_js = $(patsubst %.js,$(libdir)/offline/%.js,$(wildcard *.js))

offline_js=  offline-core.js offline-domain.js offline-sync.js offline-view.js

$(libdir):
	mkdir -p $@

$(libdir)/offline: $(libdir)
	mkdir -p $@

$(libdir)/offline/%.js: %.js $(libdir)/offline
	cp $< $@
$(libdir)/offline/offline-debug.js: $(offline_js)
	cat $(offline_js) > $@

$(libdir)/offline/offline.js: $(libdir)/offline/offline-debug.js 
	cp $< $@
	if [ -x $(utildir)/jsSquish/bin/js_compactor ]; then \
	  cd $(utildir)/jsSquish/lib; \
	  /bin/rm -f $@; \
	  ../bin/js_compactor --force --src $(libdir)/offline/offline-debug.js --dest $@; \
	fi

publish: $(libdir)/offline $(pages_js) $(libdir)/offline/offline-debug.js $(libdir)/offline/offline.js
