function is_ctrl_key(event) {
    if (event.altKey)
        return true;
    if (event.ctrlKey)
        return true;
    if (event.metaKey)	// apple key opera
        return true;
    if (event.keyCode == '17')	// apple key opera
        return true;
    if (event.keyCode == '224')	// apple key mozilla
        return true;
    if (event.keyCode == '91')	// apple key safari
        return true;

    return false;
}

function is_shift_key(event) {
    if (event.shiftKey)
        return true;
    return false;
}
