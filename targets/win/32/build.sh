#!/bin/bash

BUILD_OS="win"
BUILD_ARCH="32"

if [ -z "$wpub" ]; then
    echo "Error: undefined or empty 'wpub' environment variable."
    exit 1
fi

ORIG_DIR=`pwd`
WORK_DIR=`mktemp -d "$ORIG_DIR/build.XXXXXXXXX"`

cd "$WORK_DIR"

. "$wpub/share/offline/targets/common.sh"

function on_exit {
    local EXITCODE=$?
    cd "$ORIG_DIR"
    if [ -n "$WORK_DIR" ]; then
	rm -Rf "$WORK_DIR"
    fi
    exit $EXITCODE
}

trap on_exit EXIT

function _usage {
    echo ""
    echo "  $0 <package_name> <output_file>"
    echo ""
}

function _main {
    set -e

    if [ -z "$(type -p makensis||true)" ]; then
	echo "Soft-error: required 'makensis' not found in PATH"
	exit 0
    fi

    _check_env

    local PKG_NAME=$1
    local OUTPUT=$2

    if [ -z "$PKG_NAME" ]; then
	echo "Missing or undefined PKG_NAME."
	_usage
	exit 1
    fi
    if [ -z "$OUTPUT" ]; then
	echo "Missing or undefined OUTPUT."
	_usage
	exit 1
    fi

    mkdir -p "$PKG_NAME/dist"

    _prepare_xulapp "$PKG_NAME/dist"

    tar -C "$XULRUNTIMES_DIR/$BUILD_OS/$BUILD_ARCH" -cf - "xulrunner" | tar -C "$PKG_NAME/dist" -xf -

    cp "$PKG_NAME/dist/xulrunner/xulrunner-stub.exe" "$PKG_NAME/dist/dynacase-offline.exe"
    cp "$wpub/share/offline/targets/$BUILD_OS/$BUILD_ARCH/dynacase-offline.ico" "$PKG_NAME/dist/dynacase-offline.ico"
    cp "$wpub/share/offline/targets/$BUILD_OS/$BUILD_ARCH/LICENSE.txt" "$PKG_NAME/dist/LICENSE.txt"

    # MAR file is made from the win/32_zip target

    cp "$wpub/share/offline/targets/$BUILD_OS/$BUILD_ARCH/build.nsi" "$PKG_NAME/build.nsi"
    cp -pR "$wpub/share/offline/targets/$BUILD_OS/$BUILD_ARCH/l10n" "$PKG_NAME/l10n"
    ( cd "$PKG_NAME/dist" && find . -maxdepth 1 -type f ) | sed -e 's:\./\(.*\)$:Delete "$INSTDIR\\\1":' > "$PKG_NAME/uninstall_files.nsi"
    ( cd "$PKG_NAME/dist" && ls -d */ ) | sed -e 's:^\(.*\)$:RMDir /r "$INSTDIR\\\1":' > "$PKG_NAME/uninstall_dirs.nsi"

    ( cd "$PKG_NAME" && makensis -V2 -DPRODUCT_VERSION="${APP_VERSION}" build.nsi)

    cp "$PKG_NAME/dynacase-offline-setup.exe" "$OUTPUT"

    set +e
}

_main "$@"
