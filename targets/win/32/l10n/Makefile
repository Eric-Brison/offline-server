include $(utildir)/PubRule

nsh_files = $(patsubst %.nsh,$(targets_dir)/$(os_name)/$(arch_name)/l10n/%.nsh,$(wildcard *.nsh))

$(targets_dir)/$(os_name)/$(arch_name)/l10n: $(targets_dir)/$(os_name)/$(arch_name)
	mkdir $p "$@"

$(targets_dir)/$(os_name)/$(arch_name)/l10n/%.nsh: %.nsh $(targets_dir)/$(os_name)/$(arch_name)/l10n
	cp -pR "$<" "$@"

publish: $(nsh_files)
