include $(utildir)/PubRule

patches_dir = $(pubdir)/share/offline/patches

patch_files = $(patsubst %.patch,$(patches_dir)/%.patch,$(wildcard *.patch))

$(pubdir)/share: $(pubdir)
	mkdir -p "$@"

$(pubdir)/share/offline: $(pubdir)/share
	mkdir -p "$@"

$(patches_dir): $(pubdir)/share/offline
	mkdir -p "$@"

$(patches_dir)/%.patch: %.patch $(patches_dir)
	cp -pR "$<" "$@"

publish: $(patch_files)