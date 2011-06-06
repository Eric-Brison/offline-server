include $(utildir)/PubRule

export arch_name = 32

SUBDIR = l10n

$(targets_dir)/$(os_name)/$(arch_name): $(targets_dir)/$(os_name)
	mkdir -p "$@"

$(targets_dir)/$(os_name)/$(arch_name)/build.sh: build.sh $(targets_dir)/$(os_name)/$(arch_name)
	cp "$<" "$@"

$(targets_dir)/$(os_name)/$(arch_name)/build.nsi: build.nsi $(targets_dir)/$(os_name)/$(arch_name)
	cp "$<" "$@"

$(targets_dir)/$(os_name)/$(arch_name)/dynacase-offline.ico: dynacase-offline.ico $(targets_dir)/$(os_name)/$(arch_name)
	cp "$<" "$@"

$(targets_dir)/$(os_name)/$(arch_name)/LICENSE.txt: ../../../LICENSE $(targets_dir)/$(os_name)/$(arch_name)
	cp "$<" "$@"

publish: $(targets_dir)/$(os_name)/$(arch_name)/build.sh \
	 $(targets_dir)/$(os_name)/$(arch_name)/build.nsi \
	 $(targets_dir)/$(os_name)/$(arch_name)/dynacase-offline.ico \
	 $(targets_dir)/$(os_name)/$(arch_name)/LICENSE.txt
