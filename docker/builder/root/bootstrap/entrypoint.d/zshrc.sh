#!/bin/bash

ZSH_FILE="$HOME/.zshrc"

if [ ! -f "$HOME/.zshrc" ]; then
    cp "/bootstrap/.zshrc" "$HOME/.zshrc"
fi
