
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

    if [ -z "$UPDATE_PACKAGING_DIR" ]; then
	UPDATE_PACKAGING_DIR="$wpub/share/offline/targets/update-packaging"
    fi
    if [ ! -d "$UPDATE_PACKAGING_DIR" ]; then
	echo "Error: UPDATE_PACKAGING_DIR '$UPDATE_PACKAGING_DIR' is not a valid directory."
	exit 1
    fi

    if [ -z "$MAR" ]; then
	for MAR in $(type -p mar) "$UPDATE_PACKAGING_DIR/mar"; do
	    if [ -x "$MAR" ]; then
		break
	    fi
	done
	if [ ! -x "$MAR" ]; then
	    echo "Error: 'mar' command not found."
	    exit 1
	fi
    fi
    export MAR

    if [ -z "$MBSDIFF" ]; then
	for MBSDIFF in $(type -p mbsdiff) "$UPDATE_PACKAGING_DIR/mbsdiff"; do
	    if [ -x "$MBSDIFF" ]; then
		break
	    fi
	done
	if [ ! -x "$MBSDIFF" ]; then
	    echo "Error: 'mbsdiff' command not found."
	    exit 1
	fi
    fi
    export MBSDIFF
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
	    for PATCH in "$wpub/share/offline/patches/"*.patch; do
		patch -p1 -d "$DEST_DIR" -i "$PATCH"
	    done
	    )
    fi

    # -- Apply customize dir if required --
    if [ -n "$CUSTOMIZE_DIR" ]; then
	tar -C "$CUSTOMIZE_DIR" -cf - . | tar -C "$DEST_DIR" -xf -
    fi

    # -- Patch application.ini Version with customize release --
    local CUSTOMIZE_RELEASE=0
    if [ -f "$CUSTOMIZE_DIR/RELEASE" ]; then
	CUSTOMIZE_RELEASE=$(head -1 "$CUSTOMIZE_DIR/RELEASE")
    fi
    sed -i'' -e "s/^\(Version=.*\)/\1.${CUSTOMIZE_RELEASE}/" "$DEST_DIR/application.ini"

    # -- Set auto-update URL --
    local CORE_EXTERNURL=$("$wpub/wsh.php" --api=get_param --param=CORE_EXTERNURL 2> /dev/null)
    if [ -n "$CORE_EXTERNURL" ]; then
	cat <<EOF >> "$DEST_DIR/defaults/preferences/ZZ_prefs.js"

/* auto-update URL set by build.sh */
pref("app.update.url", "${CORE_EXTERNURL}guest.php?app=OFFLINE&action=OFF_UPDATE&download=update&version=%VERSION%&os=${BUILD_OS}&arch=${BUILD_ARCH}");
pref("app.update.url.manual", "${CORE_EXTERNURL}?app=OFFLINE&action=OFF_DLCLIENT&os=${BUILD_OS}&arch=${BUILD_ARCH}");

EOF
    fi
}

function _sha512 {
    openssl dgst -sha512 -binary < "$1" | php -r 'print bin2hex(file_get_contents("php://stdin"));'
}

function _filesize {
    ls -l -- "$1" | head -1 | awk '{print $5}'
}

function _make_precomplete {
    local DEST_DIR=$1

    if [ -z "$DEST_DIR" ]; then
	echo "Error: undefined or empty DEST_DIR in _make_precomplete."
	return 1
    fi
    if [ ! -d "$DEST_DIR" ]; then
	echo "Error: DEST_DIR '$DEST_DIR' is not a valid directory."
	return 1
    fi

    pushd "$DEST_DIR"
    "$UPDATE_PACKAGING_DIR/createprecomplete.py"
    popd
}

function _make_complete_mar {
    local MAR_FILE=$1
    local DEST_DIR=$2

    if [ -z "$DEST_DIR" ]; then
	echo "Error: undefined or empty DEST_DIR in _prepare_xulapp."
	return 1
    fi
    if [ ! -d "$DEST_DIR" ]; then
	printf "Error: DEST_DIR '%s' is not a valid directory." "$DEST_DIR"
	return 1
    fi

    "$UPDATE_PACKAGING_DIR/make_full_update.sh" "$MAR_FILE.tmp" "$DEST_DIR"

    local HASH_OLD=""
    if [ -f "$MAR_FILE.old" ]; then
	HASH_OLD=$(_sha512 "$MAR_FILE.old")
    fi
    local HASH_NEW=$(_sha512 "$MAR_FILE.tmp")

    if [ "$HASH_OLD" != "$HASH_NEW" ]; then
	if [ -f "$MAR_FILE" ]; then
	    mv "$MAR_FILE" "$MAR_FILE.old"
	fi
	mv "$MAR_FILE.tmp" "$MAR_FILE"
    else
	rm "$MAR_FILE.tmp"
    fi
}

function _make_partial_mar {
    local PARTIAL_MAR_FILE=$1
    local COMPLETE_MAR_1=$2
    local COMPLETE_MAR_2=$3

    if [ -z "$COMPLETE_MAR_1" ]; then
	echo "Warning: undefined or empty COMPLETE_MAR_1 in _make_partial_mar."
	return 0
    fi
    if [ ! -f "$COMPLETE_MAR_1" ]; then
	printf "Warning: COMPLETE_MAR_1 '%s' is not a valid file." "$COMPLETE_MAR_1"
	return 0
    fi

    if [ -z "$COMPLETE_MAR_2" ]; then
	echo "Error: undefined or empty COMPLETE_MAR_2 in _make_partial_mar."
	return 1
    fi
    if [ ! -f "$COMPLETE_MAR_2" ]; then
	printf "Error: COMPLETE_MAR_2 '%s' is not a valid file." "$COMPLETE_MAR_2"
	return 1
    fi

    local UNPACK_DIR=$(mktemp -d -t _make_partial_mar.XXXXXX)
    if [ $? -ne 0 ]; then
	echo "Error: could not create temporary directory in _make_partial_mar."
	return 1
    fi

    mkdir "$UNPACK_DIR/1"
    pushd "$UNPACK_DIR/1"
    "$UPDATE_PACKAGING_DIR/unwrap_full_update.pl" "$COMPLETE_MAR_1"
    popd

    mkdir "$UNPACK_DIR/2"
    pushd "$UNPACK_DIR/2"
    "$UPDATE_PACKAGING_DIR/unwrap_full_update.pl" "$COMPLETE_MAR_2"
    popd

    "$UPDATE_PACKAGING_DIR/make_incremental_update.sh" "$PARTIAL_MAR_FILE" "$UNPACK_DIR/1" "$UNPACK_DIR/2"

    rm -Rf "$UNPACK_DIR"
}

function _make_update_xml {
    local UPDATE_XML=$1
    local COMPLETE_MAR=$2
    local COMPLETE_DOWNLOAD_URL=$3
#    local PARTIAL_MAR=$4
#    local PARTIAL_DOWNLOAD_URL=$5

    if [ -z "$APP_VERSION" ]; then
	echo "Error: undefined or empty APP_VERSION in _make_update_xml."
	return 1
    fi
    if [ -z "$APP_BUILDID" ]; then
	echo "Error: undefined or empty BUILD_ID in _make_update_xml."
	return 1
    fi

    if [ -z "$UPDATE_XML" ]; then
	echo "Error: undefined or empty UPDATE_XML in _make_update_xml."
	return 1
    fi
    if [ -z "$COMPLETE_MAR" ]; then
	echo "Error: undefined or empty COMPLETE_MAR in _make_update_xml."
	return 1
    fi
    if [ ! -f "$COMPLETE_MAR" ]; then
	echo "Error: COMPLETE_MAR '$COMPLETE_MAR' is not a valid file."
	return 1
    fi
    if [ -z "$COMPLETE_DOWNLOAD_URL" ]; then
	echo "Error: undefined or empty COMPLETE_DOWNLOAD_URL in _make_update_xml."
	return 1
    fi

    local XML="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
    XML=$XML"<updates xmlns=\"http://www.mozilla.org/2005/app-update\">\n"
    XML=$XML"<update type=\"major\" version=\"$APP_VERSION\" extensionVersion=\"$APP_VERSION\" buildID=\"$APP_BUILDID\" detailsURL=\"DETAILS_URL\" licenseURL=\"LICENSE_URL\">\n"

    local COMPLETE_MAR_HASH=$(_sha512 "$COMPLETE_MAR")
    local COMPLETE_MAR_SIZE=$(_filesize "$COMPLETE_MAR")
    XML=$XML"<patch type=\"complete\" URL=\"$COMPLETE_DOWNLOAD_URL\" hashFunction=\"SHA512\" hashValue=\"$COMPLETE_MAR_HASH\" size=\"$COMPLETE_MAR_SIZE\" />\n"

#    if [ -n "$PARTIAL_MAR" -a -f "$PARTIAL_MAR" ]; then
#	local PARTIAL_MAR_HASH=$(_sha512 "$PARTIAL_MAR")
#	local PARTIAL_MAR_SIZE=$(_filesize "$PARTIAL_MAR")
#	XML=$XML"<patch type=\"partial\" URL=\"$PARTIAL_DOWNLOAD_URL\" hashFunction=\"SHA512\" hashValue=\"$PARTIAL_MAR_HASH\" size=\"$PARTIAL_MAR_SIZE\" />\n"
#    fi

    XML=$XML"</update>\n"
    XML=$XML"</updates>\n"

    echo -e "$XML" > "$UPDATE_XML"
}

function _make_mar {
    local APP_DIR=$1

    if [ -z "$MAR_BASENAME" ]; then
	return 0
    fi

    if [ -z "$APP_DIR" ]; then
	echo "Error: undefined or empty APP_DIR in _make_mar."
	return 1
    fi
    if [ ! -d "$APP_DIR" ]; then
	echo "Error: APP_DIR '$APP_DIR' is not a valid directory in _make_mar?."
	return 1
    fi

    local CORE_EXTERNURL=$("$wpub/wsh.php" --api=get_param --param=CORE_EXTERNURL 2> /dev/null)
    if [ -z "$CORE_EXTERNURL" ]; then
	echo "Error: undefined or empty CORE_EXTERNURL parameter."
	return 1
    fi

    _make_precomplete "$APP_DIR"

    _make_complete_mar "$wpub/share/offline/clients/${MAR_BASENAME}.complete.mar" \
	"$APP_DIR"

#    _make_partial_mar "$wpub/share/offline/clients/${MAR_BASENAME}.partial.mar" \
#	"$wpub/share/offline/clients/${MAR_BASENAME}.complete.mar.old" \
#	"$wpub/share/offline/clients/${MAR_BASENAME}.complete.mar"

#    _make_update_xml "$wpub/share/offline/clients/${MAR_BASENAME}.update.xml" \
#	"$wpub/share/offline/clients/${MAR_BASENAME}.complete.mar" \
#	"${CORE_EXTERNURL}guest.php?app=OFFLINE&amp;action=OFF_UPDATE&amp;download=complete&amp;version=%VERSION%&amp;os=$BUILD_OS&amp;arch=$BUILD_ARCH" \
#	"$wpub/share/offline/clients/${MAR_BASENAME}.partial.mar" \
#	"${CORE_EXTERNURL}guest.php?app=OFFLINE&amp;action=OFF_UPDATE&amp;download=partial&amp;version=%VERSION%&amp;os=$BUILD_OS&amp;arch=$BUILD_ARCH"

    _make_update_xml "$wpub/share/offline/clients/${MAR_BASENAME}.update.xml" \
	"$wpub/share/offline/clients/${MAR_BASENAME}.complete.mar" \
	"${CORE_EXTERNURL}guest.php?app=OFFLINE&amp;action=OFF_UPDATE&amp;download=complete&amp;version=%VERSION%&amp;os=$BUILD_OS&amp;arch=$BUILD_ARCH"
}