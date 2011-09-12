include $(utildir)/PubRule

export update_packaging_dir = $(pubdir)/share/offline/targets/update-packaging

$(pubdir)/share: $(pubdir)
	mkdir -p "$@"

$(pubdir)/share/offline: $(pubdir)/share
	mkdir -p "$@"

$(pubdir)/share/offline/targets: $(pubdir)/share/offline
	mkdir -p "$@"

$(update_packaging_dir): $(pubdir)/share/offline/targets
	mkdir -p "$@"

$(update_packaging_dir)/common.sh: common.sh $(update_packaging_dir)
	cp "$<" "$@"

$(update_packaging_dir)/confvars.sh: confvars.sh $(update_packaging_dir)
	cp "$<" "$@"

$(update_packaging_dir)/createprecomplete.py: createprecomplete.py $(update_packaging_dir)
	cp "$<" "$@"

$(update_packaging_dir)/generatesnippet.py: generatesnippet.py $(update_packaging_dir)
	cp "$<" "$@"
 
$(update_packaging_dir)/make_full_update.sh: make_full_update.sh $(update_packaging_dir)
	cp "$<" "$@"

$(update_packaging_dir)/make_incremental_update.sh: make_incremental_update.sh $(update_packaging_dir)
	cp "$<" "$@"

$(update_packaging_dir)/make_incremental_updates.py: make_incremental_updates.py $(update_packaging_dir)
	cp "$<" "$@"

$(update_packaging_dir)/mar: mar $(update_packaging_dir)
	cp "$<" "$@"

$(update_packaging_dir)/mbsdiff: mbsdiff $(update_packaging_dir)
	cp "$<" "$@"

$(update_packaging_dir)/test_make_incremental_updates.py: test_make_incremental_updates.py $(update_packaging_dir)
	cp "$<" "$@"

$(update_packaging_dir)/unwrap_full_update.pl: unwrap_full_update.pl $(update_packaging_dir)
	cp "$<" "$@"

publish: $(update_packaging_dir)/common.sh $(update_packaging_dir)/confvars.sh $(update_packaging_dir)/createprecomplete.py $(update_packaging_dir)/generatesnippet.py $(update_packaging_dir)/make_full_update.sh $(update_packaging_dir)/make_incremental_update.sh $(update_packaging_dir)/make_incremental_updates.py $(update_packaging_dir)/mar $(update_packaging_dir)/mbsdiff $(update_packaging_dir)/test_make_incremental_updates.py $(update_packaging_dir)/unwrap_full_update.pl
