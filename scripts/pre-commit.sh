#!/bin/sh

# Run composer pre-commit script
# composer run-script pre-commit --no-interaction

# Check if tests passed
if [ $? -ne 0 ]; then
    echo "Pre-commit checks failed. Commit aborted."
    exit 1
fi

exit 0
