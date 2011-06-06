#!/bin/bash

BUILD_OS="linux"
BUILD_ARCH="x86_64"

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

    cp "$PKG_NAME/xulrunner/xulrunner-stub" "$PKG_NAME/dynacase-offline"

    if [ "$OUTPUT" != "-" -a "${OUTPUT:0:1}" != "/" ]; then
	OUTPUT="$ORIG_DIR/$OUTPUT"
    fi
    tar -zcf "$OUTPUT" "$PKG_NAME"

    set +e
}

_main "$@"