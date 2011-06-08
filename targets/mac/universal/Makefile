include $(utildir)/PubRule

export arch_name = universal

$(targets_dir)/$(os_name)/$(arch_name): $(targets_dir)/$(os_name)
	mkdir -p "$@"

$(target_dir)/$(os_name)/$(arch_name): $(targets_dir)/$(os_name)
	mkdir -p "$@"

$(targets_dir)/$(os_name)/$(arch_name)/build.sh: build.sh $(targets_dir)/$(os_name)/$(arch_name)
	cp "$<" "$@"

$(targets_dir)/$(os_name)/$(arch_name)/Dynacase\ Offline.app.template: Dynacase\ Offline.app.template $(targets_dir)/$(os_name)/$(arch_name)
	cp -pR "$<" "$@"

publish: $(targets_dir)/$(os_name)/$(arch_name)/build.sh $(targets_dir)/$(os_name)/$(arch_name)/Dynacase\ Offline.app.template
