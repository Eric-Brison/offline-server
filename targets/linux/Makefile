include $(utildir)/PubRule

export os_name = linux

SUBDIR = i686 x86_64

$(targets_dir)/$(os_name): $(targets_dir)
	mkdir -p "$@"

publish: $(targets_dir)/$(os_name)