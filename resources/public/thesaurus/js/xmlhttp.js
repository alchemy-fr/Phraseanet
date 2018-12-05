function loadXMLDoc(url, post_parms, asxml) {
    if (typeof(asxml) == "undefined")
        asxml = false;
    out = null;
    xmlhttp = null;
    // code for Mozilla, etc.
    if (window.XMLHttpRequest)
        xmlhttp = new XMLHttpRequest();
    else if (window.ActiveXObject)
        xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");

    if (xmlhttp) {
        // xmlhttp.onreadystatechange=state_Change
        if (post_parms) {
            xmlhttp.open("POST", url, false);
            xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xmlhttp.send(post_parms);
        }
        else {
            xmlhttp.open("GET", url, false);
            xmlhttp.send(null);
        }
        out = asxml ? xmlhttp.responseXML : xmlhttp.responseText;
    }
    return(out);
}
