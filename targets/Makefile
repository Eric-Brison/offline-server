include $(utildir)/PubRule

SUBDIR = linux mac win update-packaging

export targets_dir = $(pubdir)/share/offline/targets

$(pubdir)/share: $(pubdir)
	mkdir -p "$@"

$(pubdir)/share/offline: $(pubdir)/share
	mkdir -p "$@"

$(targets_dir): $(pubdir)/share/offline
	mkdir -p "$@"

$(targets_dir)/common.sh: common.sh $(targets_dir)
	cp "$<" "$@"

publish: $(targets_dir)/common.sh
