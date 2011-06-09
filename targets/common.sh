
function _check_env {
    if [ -z "$XULRUNTIMES_DIR" ]; then
	XULRUNTIMES_DIR="$wpub/share/offline/xulruntimes"
    fi
    if [ ! -d "$XULRUNTIMES_DIR" ]; then
	echo "Error: XULRUNTIMES_DIR '$XULRUNTIMES_DIR' is not a valid directory."
	exit 1
    fi

    if [ -z "$XULAPP_DIR" ]; then
	XULAPP_DIR="$wpub/share/offline/xulapp"
    fi
    if [ ! -d "$XULAPP_DIR" ]; then
	echo "Error: XULAPP_DIR '$XULAPP_DIR' is not a valid directory."
	exit 1
    fi
}

function _prepare_xulapp {
    local DEST_DIR=$1

    if [ -z "$DEST_DIR" ]; then
	echo "Error: undefined or empty DEST_DIR in _prepare_xulapp."
	return 1
    fi
    if [ ! -d "$DEST_DIR" ]; then
	printf "Error: DEST_DIR '%s' is not a valid directory." "$DEST_DIR"
	return 1
    fi

    # -- Copy XULAPP --
    tar -C "$XULAPP_DIR" -cf - . | tar -C "$DEST_DIR" -xf -

    # -- Copy CKEditor --
    local CKEDITOR_DEST_DIR="$DEST_DIR/chrome/ckeditor/content/ckeditor"
    mkdir -p "$CKEDITOR_DEST_DIR"
    tar -C "$wpub/ckeditor" -cf - . | tar -C "$CKEDITOR_DEST_DIR" -xf -

    # -- Apply patches on XULAPP --
    if [ -d "$wpub/share/offline/patches" ]; then
	(
	    shopt -s nullglob
	    for PATCH in "$wpub/share/offline/"*.patch; do
		patch -p1 -d "$DEST_DIR" -i "$PATCH"
	    done
	    )
    fi

    # -- Apply customize dir if required --
    if [ -n "$CUSTOMIZE_DIR" ]; then
	tar -C "$CUSTOMIZE_DIR" -cf - . | tar -C "$DEST_DIR" -xf -
    fi

}
