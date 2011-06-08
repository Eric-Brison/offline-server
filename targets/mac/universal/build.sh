#!/bin/bash

BUILD_OS="mac"
BUILD_ARCH="universal"

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

    local APP="$PKG_NAME/Dynacase Offline.app"
    mkdir -p "$APP"

    tar -C "$wpub/share/offline/targets/$BUILD_OS/$BUILD_ARCH/Dynacase Offline.app.template" -cf - . | tar -C "$APP" -xf -

    _prepare_xulapp "$APP/Contents/Resources"

    mkdir -p "$APP/Contents/Frameworks"
    tar -C "$XULRUNTIMES_DIR/$BUILD_OS/$BUILD_ARCH" -cf - "XUL.framework" | tar -C "$APP/Contents/Frameworks" -xf -

    mkdir -p "$APP/Contents/MacOS"
    cp "$APP/Contents/Frameworks/XUL.framework/Versions/Current/xulrunner" "$APP/Contents/MacOS/xulrunner"

    zip -q -y -r "$OUTPUT" "$APP"

    set +e
}

_main "$@"