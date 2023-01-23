#!/bin/bash

if [ ! -d "$HOME/.oh-my-zsh" ]; then
    cp -r "/bootstrap/.oh-my-zsh" "$HOME/.oh-my-zsh"
fi
