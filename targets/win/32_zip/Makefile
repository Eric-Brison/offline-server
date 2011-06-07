include $(utildir)/PubRule

export arch_name = 32_zip

$(targets_dir)/$(os_name)/$(arch_name): $(targets_dir)/$(os_name)/$(arch_name)
	mkdir -p "$@"

$(targets_dir)/$(os_name)/$(arch_name)/build.sh: build.sh $(targets_dir)/$(os_name)/$(arch_name)
	cp "$<" "$@"

publish: \
	$(targets_dir)/$(os_name)/$(arch_name)/build.sh
