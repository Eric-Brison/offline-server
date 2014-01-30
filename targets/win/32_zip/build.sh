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

    _check_env

    local PKG_NAME=$1
    local OUTPUT=$2
    local MAR_BASENAME=$3

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

    mkdir -p "$PKG_NAME"

    _prepare_xulapp "$PKG_NAME"

    tar -C "$XULRUNTIMES_DIR/$BUILD_OS/$BUILD_ARCH" -cf - "xulrunner" | tar -C "$PKG_NAME" -xf -

    cp "$PKG_NAME/xulrunner/xulrunner-stub.exe" "$PKG_NAME/dynacase-offline.exe"
    cp "$wpub/share/offline/targets/$BUILD_OS/$BUILD_ARCH/dynacase-offline.ico" "$PKG_NAME/dynacase-offline.ico"

    _make_mar "$PKG_NAME"

    if [ -f "$OUTPUT" ]; then
	rm "$OUTPUT"
    fi

    zip -q -y -r "$OUTPUT" "$PKG_NAME"

    set +e
}

_main "$@"
