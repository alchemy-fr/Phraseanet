var clickObj = { X: 0, Y: 0, lastClickX: -1, lastClickY: -1, Src: null, Src0: null };
var balloonObj = { X: 0, Y: 0, Src: null, Src0: null };
var dragObj = { objCursor: null, dragging: 0, X: 0, Y: 0, lastClickX: -1, lastClickY: -1, Src: null, Src0: null, Target: null, Target0: null, idTarget: null, callBack: null };
debuglog = "";

function _ww_setDragObj(obj) {
    if (dragObj.objCursor)
        dragObj.objCursor.style.visibility = "hidden";
    dragObj.objCursor = obj ? obj : document.getElementById("dragDropCursor");
    obj.style.visibility = "visible";
}

var iiii = 0;

function _ww_evt_mdwn_doc(gui, evt) {
    var gui = eval(gui);

    if (gui.elemBalloon)
        gui.elemBalloon.style.visibility = "hidden";
    if (timer_ballon) {
        clearTimeout(timer_ballon);
        timer_ballon = null;
    }
    var button;
    if (typeof(evt.which) != 'undefined')
        button = gui.firefoxButtons["b" + evt.which];
    else
        button = evt.button;

    dragObj.objCursor = document.getElementById("dragDropCursor");
    dragObj.dragSrc0 = dragObj.dragSrc = dragObj.dragTarget = null;
    dragObj.dragging = 0;

    var srcElement = evt.srcElement ? evt.srcElement : evt.target;
//		for(src0=srcElement; src0 && (!src0.tagName); src0=src0.parentNode)
    for (src0 = srcElement; src0 && (!src0.tagName || src0.tagName == 'APPLET' || src0.tagName == 'OBJECT' || !src0.id); src0 = src0.parentNode)
        ;

    if (src0.className == 'hslider') {
        var x;
        var wslider = parseInt(src0.style.width);
        var posmax = parseInt(src0.parentNode.style.width) - wslider;
        var min = 0, max = posmax;
        if ((x = src0.getAttribute('min')) != "")
            min = parseInt(x);
        if ((x = src0.getAttribute('max')) != "")
            max = parseInt(x);
        var pos = parseInt(src0.style.left);
        var val = min + (((max - min) * pos) / posmax);
        // var pos = (int)(((val-min) * divmax) / (max-min));
        var cb = null;
        if ((x = src0.getAttribute('callback')) != "")
            cb = eval(x);

        gui.sliding = { 'obj': src0, 'dir': "H", 'posmax': posmax, 'opos': pos, 'pos': pos, 'min': min, 'max': max, 'val': val, 'callback': cb };
        return(false);
    }
    if (src0.className == 'hsplitter') {
        src0.setAttribute('otop', parseInt(src0.style.top));
        src0.setAttribute('ozindex', parseInt(src0.style.zIndex));
        src0.style.zIndex = 999;
        gui.splitting = src0;
        return(false);
    }
    if (src0.className == 'vsplitter') {
        src0.setAttribute('oleft', parseInt(src0.style.left));
        src0.setAttribute('ozindex', parseInt(src0.style.zIndex));
        src0.style.zIndex = 999;
        gui.splitting = src0;
        return(false);
    }

    var dragfound = false;
    var clickfound = false;
    var xmouse = parseInt(evt.clientX);
    var ymouse = parseInt(evt.clientY);
    var acceptClick = true;
    var e;
    for (var e = src0; e && acceptClick && (!e.tagName || !dragfound || !clickfound); e = e.parentNode) {


        if (e && e.clickable) {
            clickObj.X = xmouse;
            clickObj.Y = ymouse;
            clickObj.Src = e;
            clickObj.Src0 = src0;

            clickfound = true;
            if (e.clickCallback) {
                if (button == 1) // && !event.altKey)		// left button
                    acceptClick = (e.clickCallback)(evt, evt.ctrlKey ? "RMOUSEDOWN" : "MOUSEDOWN", clickObj);
                else if (button == 2 && !evt.ctrlKey)		// right button
                    acceptClick = (e.clickCallback)(evt, "RMOUSEDOWN", clickObj);
            }
        }

        if (acceptClick && e && e.draggable) //  && (!src0.draggable || src0.draggable!=-1))
        {
            // alert(e.draggable);
            dragObj.lastClickX = xmouse;
            dragObj.lastClickY = ymouse;
            dragObj.Src = e;
            dragObj.Src0 = src0;
            //		alert("<" + src0.tagName + " id='" + src0.id + "'>");
            dragObj.dragging = 1;
            //alert("src0:<"+src0.tagName+" id="+src0.id+" "+src0.draggable+">  \ne:<"+e.tagName+" id="+e.id+" "+e.draggable+">");
            dragfound = true;
        }
    }
    evt.cancelBubble = true;
    evt.returnValue = false;
}


function _ww_evt_mmov_doc(gui, evt) {
    var gui = eval(gui);
    var button, xmouse, ymouse;

    if (gui.elemBalloon)
        gui.elemBalloon.style.visibility = "hidden";
    if (timer_ballon) {
        clearTimeout(timer_ballon);
        timer_ballon = null;
    }
    dragObj.X = xmouse = parseInt(evt.clientX);
    dragObj.Y = ymouse = parseInt(evt.clientY);
//window.status = xmouse + '-' + ymouse;

    under_ballon = (evt.target) ? evt.target : evt.srcElement;
    if (under_ballon.id) {
        js = "timeout_ballon('" + under_ballon.id + "', " + xmouse + ", " + ymouse + ");";
        timer_ballon = setTimeout(js, 300);
    }

    xmouse = parseInt(evt.clientX);
    ymouse = parseInt(evt.clientY);
    if (dragObj.Src) {
        if (dragObj.dragging == 1) {
            dx = xmouse - dragObj.lastClickX;
            dy = ymouse - dragObj.lastClickY;
            if (Math.abs(dx) > 4 || Math.abs(dy) > 4) {
                if (dragObj.objCursor) {
                    dragObj.objCursor.style.visibility = "visible";
                }
                dragObj.dragging = 2;
                if (dragObj.Src.ondragstart) {
                    // alert("gui is calling ondragstart");
                    (dragObj.Src.ondragstart)();
                }
                if (dragObj.Src.dragCallback) {
                    var r = (dragObj.Src.dragCallback)(evt, "DRAGSTART", dragObj);
                    if (!r) {
                        dragObj.dragging = 0;
                        dragObj.Src0 = dragObj.Src = dragObj.Target0 = dragObj.Target = null;
                        if (dragObj.objCursor)
                            dragObj.objCursor.style.visibility = "hidden";
                    }
                }
            }
        }
        if (dragObj.dragging == 2) {
            if (dragObj.objCursor) {
//	window.status = xmouse + '-' + ymouse;
                dragObj.objCursor.style.left = (xmouse + 8) + "px";
                dragObj.objCursor.style.top = (ymouse + 8) + "px";
            }
            var srcElement = (evt.target) ? evt.target : evt.srcElement;
            var src0;
            var e;
            for (src0 = srcElement; src0 && !src0.id; src0 = src0.parentNode)
                ;
            for (e = src0; e && (!e.tagName || !e.droppable); e = e.parentNode)
                ;

            if (src0) {
                dragObj.Target0 = src0;
                if (dragObj.Target && e != dragObj.Target) //  && src0 != e)
                {
                    if (dragObj.Target.ondragleave)
                        (dragObj.Target.ondragleave)();
                    if (dragObj.Target.dropCallback)
                        (dragObj.Target.dropCallback)(evt, "DRAGLEAVE", dragObj);
                    dragObj.Target = null;
                }
                if (e) {
                    if (!dragObj.Target) {
                        dragObj.Target = e;
                        if (e.ondragenter)
                            (e.ondragenter)();
                        if (dragObj.Target.dropCallback)
                            (dragObj.Target.dropCallback)(evt, "DRAGENTER", dragObj);
                    }
                    else {
                        if (dragObj.Src.dragCallback)
                            (dragObj.Src.dragCallback)(evt, "DRAG", dragObj);
                        if (dragObj.Target.dropCallback)
                            (dragObj.Target.dropCallback)(evt, "DRAGOVER", dragObj);
                    }
                }
            }
        }
        return;		// if drag/drop, don't care about moving windows
    }

    return false;
}

function _ww_evt_mup_doc(gui, evt) {
    var gui = eval(gui);
    var srcElement = (evt.target) ? evt.target : evt.srcElement;
    var src0;
    for (src0 = srcElement; src0 && !src0.id; src0 = src0.parentNode)
        ;

    var button;
    if (typeof(evt.which) != 'undefined')
        button = gui.firefoxButtons["b" + evt.which];
    else
        button = evt.button;
    var xmouse = parseInt(evt.clientX);
    var ymouse = parseInt(evt.clientY);
    var e;
    var clickfound = false;
    for (e = src0; e && (!e.tagName || !clickfound); e = e.parentNode) {
        if (e && e.clickable) {
            clickObj.X = xmouse;
            clickObj.Y = ymouse;
            clickObj.Src = e;
            clickObj.Src0 = src0;

            clickfound = true;
            if (e.clickCallback) {
                if (button == 1 && !evt.ctrlKey)		// left button
                    acceptClick = (e.clickCallback)(evt, evt.ctrlKey ? "RMOUSEUP" : "MOUSEUP", clickObj);
                else if (button == 2 && !evt.ctrlKey)		// right button
                    acceptClick = (e.clickCallback)(evt, "RMOUSEUP", clickObj);
            }
        }
    }
    if (!dragObj.Src) {
        if (dragObj.objCursor)
            dragObj.objCursor.style.visibility = "hidden";
    }
    else {
        if (dragObj.dragging == 2) {
            for (e = src0; e && (!e.tagName || !e.droppable); e = e.parentNode) {
                ;
            }
            if (e) {
                if (dragObj.Target && e == dragObj.Target) {
                    if (dragObj.Target.ondrop)
                        (dragObj.Target.ondrop)();
                    if (dragObj.Target.dropCallback)
                        (dragObj.Target.dropCallback)(evt, "DROP", dragObj);
                }
            }
        }
        if (dragObj.Src.dropCallback)
            (dragObj.Src.dropCallback)(evt, "DRAGEND", dragObj);
        dragObj.dragging = 0;
        dragObj.Src0 = dragObj.Src = dragObj.Target0 = dragObj.Target = null;
        if (dragObj.objCursor)
            dragObj.objCursor.style.visibility = "hidden";
    }

    return true;
}


function _ww_evt_kon_doc(gui, evt) {

    var gui = eval(gui);

    if (gui.elemBalloon)
        gui.elemBalloon.style.visibility = "hidden";
    if (timer_ballon) {
        clearTimeout(timer_ballon);
        timer_ballon = null;
    }

    if (!dragObj.objCursor)
        dragObj.objCursor = document.getElementById("dragDropCursor");
    dragObj.Src0 = dragObj.Src = dragObj.Target = dragObj.Target0 = null;
    dragObj.dragging = 0;

    var srcElement = (evt.target) ? evt.target : evt.srcElement;
    var src0;
    for (src0 = srcElement; src0 && !src0.id; src0 = src0.parentNode)
        ;

    var dragfound = false;
    var clickfound = false;
    var xmouse = parseInt(evt.clientX);
    var ymouse = parseInt(evt.clientY);
    var acceptClick = true;
    var e;
    for (var e = src0; e && acceptClick && (!e.tagName || !dragfound || !clickfound); e = e.parentNode) {
        if (e && e.clickable) {
            clickObj.X = xmouse;
            clickObj.Y = ymouse;
            clickObj.Src = e;
            clickObj.Src0 = src0;
            clickfound = true;
            if (e.clickCallback) {
                // var js = "cbDD_T0('CONTEXTMENU', '"+src0.id+"')";
                // self.setTimeout(js, 3000);
                // (js)();
                acceptClick = (e.clickCallback)(evt, "CONTEXTMENU", clickObj);
            }
        }
    }
    evt.cancelBubble = true;	// for firefox
    evt.returnValue = false;	// for others
//	return(false);
}



// ------------------------------------------------------------------------------------------------------


var timer_ballon = null;
var under_ballon = null;
function timeout_ballon(id, xmouse, ymouse) {
    var src0;
    if (src0 = document.getElementById(id)) {
        var clickfound = false;
        for (var e = src0; e && (!e.tagName || !clickfound); e = e.parentNode) {
            if (e && e.clickable) {
                balloonObj.X = xmouse;
                balloonObj.Y = ymouse;
                balloonObj.Src = e;
                balloonObj.Src0 = src0;
                clickfound = true;
                if (e.clickCallback)
                    (e.clickCallback)(null, "BALLOON", balloonObj);
            }
        }
    }
    // alert("uballon " + id);
    timer_ballon = null;
}

function _ww_evt_balloon(msg) {
//	for(var i=0; i<24; i++)
//		msg += "<br/>\n"+i;
    var measurediv = 0;
    if (measurediv) {
        if (!this.elemMeasureBalloon) {
            var div = this.document.createElement("div");
            div.style.position = "absolute";
            div.style.top = div.style.left = "0px";
            div.style.width = div.style.height = "auto";
            div.style.backgroundColor = "#80ff80";
            div.style.zIndex = 998;
            div.innerHTML = "HELLO";
            this.elemMeasureBalloon = this.body.appendChild(div);
        }
    }

    var k = 7;
    if (!this.elemBalloon) {
        var div = this.document.createElement("div");
        div.style.overflow = "visible";
        div.style.position = "absolute";
        div.style.width = "auto";
        div.style.height = "auto";
        div.style.backgroundColor = "#ff8800";
        div.style.zIndex = 999;

        for (var i = 1; i < k; i++) {
            var t = 1 - (i / k);	//
            var divs = this.document.createElement("div");
            divs.style.position = "absolute";
            divs.style.top = divs.style.left = (i * 1) + "px";	// 1, 2, 3 ...7
            divs.style.width = divs.style.height = "100%";
            divs.style.backgroundColor = "#000000";
            divs.style.filter = "alpha(opacity=" + (5 * (k - i)) + ")";		// 30 , 20 , 10
            divs.style.opacity = (0.05 * (k - i));	// 0.3 , 0.2 , 0.1

            div.appendChild(divs);
        }

        var divt = this.document.createElement("div");
        divt.style.position = "relative";
        divt.style.padding = "2px";
        // divt.style.border = "#ffff99 1px solid";
        // divt.style.top = divt.style.left = "0px";
        // divt.style.width = divt.style.height = "500px";
        divt.style.backgroundColor = "#ffff99";
        // divt.style.rightMargin = "20px";

        div.appendChild(divt);

        this.elemBalloon = this.body.appendChild(div);
    }

    if (measurediv) {
        this.elemMeasureBalloon.innerHTML = msg; // + "<script language='javascript'>alert('loaded');</script>";
        var msg_h = this.elemMeasureBalloon.clientHeight;
        var msg_w = this.elemMeasureBalloon.clientWidth;

// alert(msg_w + " " + msg_h );

    }

//	var msg_sbox = this.elemBalloon.firstChild;
//	var msg_tbox = this.elemBalloon.children.item(k<1 ? 0 : k-1);
    var msg_tbox = this.elemBalloon.childNodes[k < 1 ? 0 : k - 1];
    if (measurediv) {
        msg_tbox.innerHTML = msg;
        msg_tbox.style.width = (msg_w + 0) + "px";
        msg_tbox.style.height = (msg_h + 0) + "px";
    }
    else {
        //	this.elemBalloon.style.visibility = "visible";
        this.elemBalloon.style.top = "0px";
        this.elemBalloon.style.left = "0px";
        msg_tbox.style.width = "auto";
        msg_tbox.style.height = "auto";
        msg_tbox.innerHTML = msg;
        var msg_h = this.elemBalloon.clientHeight;
        var msg_w = this.elemBalloon.clientWidth;
        msg_tbox.style.width = (msg_w - 4) + "px";
        msg_tbox.style.height = (msg_h - 4) + "px";
    }

    var ymouse = 0 + balloonObj.Y;
    var xmouse = 0 + balloonObj.X;

    var body_h = document.documentElement.clientHeight;

    var msg_t;

    msg_t = ymouse + 5;	// en dessous
    if (msg_t + msg_h > body_h) {
        // tiens pas en dessous
        msg_t = ymouse - msg_h - 3;	// au dessus
        if (msg_t < 3) {
            // tiens pas au dessus
            msg_t = body_h - msg_h - 10;	// colle en bas
            if (msg_t < 3) {
                // tiens pas colle en bas
                msg_t = 3;	// colle en haut, tant pis si ea deborde en bas
            }
        }
    }
    var msg_l;
    msg_l = xmouse + 3;

    this.elemBalloon.style.top = msg_t + "px";
    this.elemBalloon.style.left = msg_l + "px";

    this.elemBalloon.style.visibility = "visible";
}

function _ww_mdwn_win(idx) {
    wf = this.frontWindow();
    if (wf.modal && this.twin[idx] != wf) {
        wf.blink();
        return(false);
    }

    if (o = document.getElementById(this.varname + "w" + idx)) {
        e = window.event;
        z = o.style.zIndex;
        if (z != 99) {
            o.style.zIndex = this.twin[idx].properties["z-index"] = this.maxdepth;
            for (i = 0; i < this.twin.length; i++) {
                if (i == idx)
                    continue;
                if (o2 = document.getElementById(this.varname + "w" + i)) {
                    z2 = parseInt(o2.style.zIndex);
                    if (z2 > z && z2 != 99) {
                        z2--;
                        o2.style.zIndex = this.twin[i].properties["z-index"] = z2;
                    }
                }
            }
        }

        this.w_active = idx;

        this.new_t = this.t0 = o.offsetTop;
        this.new_l = this.l0 = o.offsetLeft;
        this.new_w = this.w0 = o.clientWidth;
        this.new_h = this.h0 = o.clientHeight;

        // alert("ww_mdwn_win : w0="+this.w0 + "  h0="+this.h0);
        this.x0 = e.clientX;
        this.y0 = e.clientY;
        return true;
    }
    return false;
}
function _ww_evt_mdwn_window(idx) {
    this.mdwn_win(idx);
}
function _ww_evt_mdwn_title(idx) {
    if (!this.twin[idx] || this.twin[idx].moveable <= 0)
        return;
    if (this.mdwn_win(idx)) {

        this.dragging = true;

        window.event.cancelBubble = true;
        if (window.event.stopPropagation)
            window.event.stopPropagation();
    }
}
function _ww_evt_mdwn_sizer(idx) {
    if (!this.twin[idx] || this.twin[idx].sizeable <= 0)
        return;
    if (this.mdwn_win(idx))
        this.sizing = true;
    window.event.cancelBubble = true;
    if (window.event.stopPropagation)
        window.event.stopPropagation();
}
function _ww_evt_mdwn_reducer(idx) {
// alert("recucer");
    if (o = document.getElementById(this.varname + "w" + idx)) {
        if (this.twin[idx].reduced) {
            // restore
            // o.style.clip = "rect(auto auto auto auto)";
            // alert(this.twin[idx].oldheight);
            this.twin[idx].setProperties({height: this.twin[idx].oldheight + "px"});
            //o.style.height = (this.twin[idx].oldheight + "px");
            this.twin[idx].reduced = false;
        }
        else {
            // reduce
            // o.style.clip = "rect(0px auto 17px auto)";
            this.twin[idx].oldheight = parseInt(o.style.height);

            tith = (document.getElementById(this.varname + "tbar" + idx).clientHeight) + "px";

            this.twin[idx].setProperties({height: tith});
            this.twin[idx].reduced = true;
        }
    }
    window.event.cancelBubble = true;
    if (window.event.stopPropagation)
        window.event.stopPropagation();
    //if(!this.twin[idx] || this.twin[idx].sizeable<=0)
    //	return;
    //if(this.mdwn_win(idx))
    //	this.sizing = true;
}

function _ww_evt_mdwn_closer(idx) {
    if (o = document.getElementById(this.varname + "w" + idx)) {
        // alert(this.twin[idx].onclose);
        if (!this.twin[idx].onclose || (this.twin[idx].onclose)(this.twin[idx]) == true) {
            this.twin[idx].hide();
//			o.style.visibility = "hidden";
        }
        window.event.cancelBubble = true;
        if (window.event.stopPropagation)
            window.event.stopPropagation();
    }
}

function _ww_setProperties(properties) {
    style = "";
    for (p in this.properties) {
        if (properties[p])
            this.properties[p] = properties[p];
        style += p + ":" + this.properties[p] + ";";
    }
//	if(this.reduced)
//		style += "clip:rect(0px auto 17px auto);" ;

    document.getElementById(this.varname + "w" + this.idx).style.cssText = style;
// alert("ww_setProperties : style="+style);
    tith = document.getElementById(this.varname + "tbar" + this.idx).clientHeight;
//	alert("tith=" + tith);
    tv = false;
    win_w = parseInt(this.properties.width);
    win_h = parseInt(this.properties.height);
    o_h = win_h - tith - 2;
    if (o_h < 0)
        o_h = 0;
    if (this.twoviews && (o = document.getElementById(this.varname + "e" + this.idx).style)) {
        tv = true;
        o.height = (o_h) + "px";
        o.width = (Math.round(win_w * .25) - 2) + "px";
        // document.getElementById(this.varname+"e"+this.idx).style.cssText = style;
    }
    if (o = document.getElementById(this.varname + "c" + this.idx).style) {

        o.height = (o_h) + "px";
        if (tv)
            o.width = (Math.round(win_w * .75) - 2) + "px";
        else
            o.width = (Math.round(win_w * 1) - 2) + "px";
        // style += "width:" + document.getElementById(this.varname+"c"+this.idx).style.width+";";

        // alert("ww_setProperties : style=" + style);
        // document.getElementById(this.varname+"c"+this.idx).style.cssText = style;
    }

    if (o = document.getElementById(this.varname + "r" + this.idx)) {
        t = "top:" + (parseInt(this.properties.height) - 18) + "px; left:" + (parseInt(this.properties.width) - 18) + "px;";
        o.style.cssText = t;
    }
}

function _ww_repaint() {
    w = parseInt(this.gui.twin[this.idx].properties.width);
    h = parseInt(this.gui.twin[this.idx].properties.height);
    this.gui.twin[this.idx].setProperties({ width: (w) + "px", height: (h) + "px" });
}
function _ww_sizeto(w, h) {
    if (w == -1)
        w = parseInt(this.gui.twin[this.idx].properties.width);
    if (h == -1)
        h = parseInt(this.gui.twin[this.idx].properties.height);
    this.gui.twin[this.idx].setProperties({ width: (w) + "px", height: (h) + "px" });
    if (this.gui.twin[this.idx].onresized)
        (this.gui.twin[this.idx].onresized)(parseInt(this.gui.twin[this.idx].properties.top)
            , parseInt(this.gui.twin[this.idx].properties.left), w, h);
}
function _ww_moveto(t, l) {
    this.gui.twin[this.idx].setProperties({ top: (t) + "px", left: (l) + "px" });
}
function _ww_setTitle(t) {
    document.getElementById(this.varname + "t" + this.idx).innerHTML = "&nbsp;" + t;
}
function _ww_setContent(t, view) {
    if (!view || !this.twoviews || view != "e")
        view = "c";
    document.getElementById(this.varname + view + this.idx).innerHTML = t;
}
function _ww_show() {
    this.properties.visibility = "visible";
    document.getElementById(this.varname + "w" + this.idx).style.visibility = "visible";
}
function _ww_toFront() {
    wf = this.gui.frontWindow();
    if (wf.modal && this != wf) {
        wf.blink();
        return(false);
    }
    var o;
    if (o = document.getElementById(this.varname + "w" + this.idx)) {
        e = window.event;
        z = o.style.zIndex;
        if (z != 99) {
            o.style.zIndex = this.gui.twin[this.idx].properties["z-index"] = this.gui.maxdepth;
            for (i = 0; i < this.gui.twin.length; i++) {
                if (i == this.idx)
                    continue;
                var o2;
                if (o2 = document.getElementById(this.varname + "w" + i)) {
                    z2 = parseInt(o2.style.zIndex);
                    if (z2 > z && z2 != 99) {
                        z2--;
                        o2.style.zIndex = this.gui.twin[i].properties["z-index"] = z2;
                    }
                }
            }
            // this.gui.frontWindow = this.idx;
        }

        this.gui.w_active = this.idx;

        this.gui.new_t = this.gui.t0 = o.offsetTop;
        this.gui.new_l = this.gui.l0 = o.offsetLeft;
        this.gui.new_w = this.gui.w0 = o.clientWidth;
        this.gui.new_h = this.gui.h0 = o.clientHeight;
        return true;
    }
    return false;
}
function _ww_hide() {
    this.properties.visibility = "hidden";
    document.getElementById(this.varname + "w" + this.idx).style.visibility = "hidden";
}
function _ww_blink() {
    if (o = document.getElementById(this.varname + "t" + this.idx)) {
        o.className = "ww_title_blink";
        window.setTimeout("ww_noblink('" + this.varname + "t" + this.idx + "');", 500);
    }
}
function ww_noblink(titleid) {
    if (o = document.getElementById(titleid))
        o.className = "ww_title";
}

function _ww_getClientSize(view) {
    if (!view || !this.twoviews || view != "e")
        view = "c";
    if (o = document.getElementById(this.varname + view + this.idx)) {
        // alert("ww_getClientSize : o.style.width="+o.style.width + "   o.style.height="+o.style.height );
        // return({w:(o.clientWidth-20), h:(o.clientHeight-2)});
        return({w: (parseInt(o.style.width) - 2), h: (parseInt(o.style.height) - 2)});
    }
    return(null);
}

function _ww_autoMove() {
    // alert("ww_autoMove");
    var moved = false;
    var sized = false;
    if (o = document.getElementById(this.varname + "w" + this.idx).style) {
        wgui = document.getElementById(this.gui.desktop).clientWidth;
        hgui = document.getElementById(this.gui.desktop).clientHeight;
        twin = parseInt(o.top);
        lwin = parseInt(o.left);
        wwin = parseInt(o.width);
        hwin = parseInt(o.height);
        bwin = twin + hwin;
        if (lwin > wgui - wwin) {
            lwin = wgui - wwin;
            moved = true;
        }
        if (lwin < 0) {
            lwin = 0;
            wwin = wgui;
            moved = true;
            sized = true;
        }
        if (twin > hgui - hwin) {
            twin = hgui - hwin;
            moved = true;
        }
        if (twin < 0) {
            twin = 0;
            hwin = hgui;
            moved = true;
            sized = true;
        }
        // o.top = twin+"px";
        // o.left = lwin+"px";
        if (moved)
            this.moveto(twin, lwin);
        if (sized)
            this.sizeto(wwin, hwin);
//		this.setProperties( {top:twin+"px", left:lwin+"px" } );
    }
}

function ww_Window(gui, anchor, varname, idx, properties) {
    if (!document.getElementById(anchor))
        return;
    oldContent = document.getElementById(anchor).innerHTML;
    this.gui = gui;
    this.varname = varname;
    this.idx = idx;
    this.anchor = anchor;
    this.properties = { top: "0px", left: "0px", width: "200px", height: "200px", "z-index": "0", visibility: "visible" };

//	this.evt_mdwn_window = ww_evt_mdwn_window;
//	this.evt_mdwn_title  = ww_evt_mdwn_title;
//	this.evt_mdwn_sizer  = ww_evt_mdwn_sizer;
//	this.evt_mdwn_reducer  = ww_evt_mdwn_reducer;
    // this.evt_mdwn_closer  = ww_evt_mdwn_closer;
    this.sizeto = _ww_sizeto;
    this.moveto = _ww_moveto;
    this.repaint = _ww_repaint;
    this.setTitle = _ww_setTitle;
    this.setContent = _ww_setContent;
    this.setProperties = _ww_setProperties;
    this.show = _ww_show;
    this.hide = _ww_hide;
    this.toFront = _ww_toFront;
    this.autoMove = _ww_autoMove;
    this.blink = _ww_blink;
    this.getClientSize = _ww_getClientSize;
//	this.mdwn_win = ww_mdwn_win;
    this.modal = (properties.modal && properties.modal > 0);
    this.twoviews = (properties.twoviews && properties.twoviews > 0);
    this.hasscroll = (properties.scroll && properties.scroll > 0);
    this.closeable = (properties.closeable && properties.closeable > 0);
    this.moveable = (properties.moveable && properties.moveable > 0);
    this.sizeable = (properties.sizeable && properties.sizeable > 0);
    this.visibility = (properties.visibility && properties.visibility == "hidden") ? "hidden" : "visible";
    this.ontop = (properties.ontop && properties.ontop > 0);
    this.minwidth = (properties.minwidth && properties.minwidth > 0) ? properties.minwidth : 50;
    this.maxwidth = (properties.maxwidth && properties.maxwidth > 0) ? properties.maxwidth : 20000;
    this.minheight = (properties.minheight && properties.minheight > 0) ? properties.minheight : 40;
    this.maxheight = (properties.maxheight && properties.maxheight > 0) ? properties.maxheight : 20000;
    this.onresized = properties.onresized;
    this.oncreated = properties.oncreated;
    this.onclose = properties.onclose;
    this.reduced = false;
    this.oldheight = -1;

    if (!this.ontop) {
        // gui.frontWindow = this;
        this.properties["z-index"] = ++gui.maxdepth;
    }
    else
        this.properties["z-index"] = 99;

    s = "";
    style = "";
    for (p in this.properties) {
        if (properties[p]) {
            if (p == "height") {
                if (parseInt(properties[p]) < 60)
                    properties[p] = "60px";
            }
            this.properties[p] = properties[p];
        }
        style += p + ":" + this.properties[p] + ";";
    }
    s += "<div style=\"" + style + "\" id=\"" + varname + "w" + idx + "\" class=\"ww_window\" onMouseDown=\"" + varname + ".evt_mdwn_window(" + idx + ");\">\n";
//	s += "<div style=\"z-index:-1; position:absolute; top:2px; left:2px; width:100%; height:100%; background-color:#808080; filter: alpha(opacity=50); opacity:.4\"></div>";
//	s += "<div style=\"z-index:-1; position:absolute; top:4px; left:4px; width:100%; height:100%; background-color:#808080; filter: alpha(opacity=30); opacity:.2\"></div>";
//	s += "<div style=\"position:absolute; top:7px; left:7px; width:100%; height:100%; background-color:#808080; filter: alpha(opacity=10); opacity:.5\"></div>";
    s += "<div>";
    s += "	<div id=\"" + varname + "tbar" + idx + "\" class=\"ww_title\" onMouseDown=\"" + varname + ".evt_mdwn_title(" + idx + ");\">\n";

    s += "<table cellpadding=0 cellspacing=0 border=1 width=100% xheight=19>";
    s += "	<tr>";
    s += "		<td width=15 valign=\"top\"><img src=\"/assets/common/images/icons/grip.gif\" border=0 xwidth=15 xheight=19 alt=\"\" onDragStart=\"return false;\"></td>";
    if (properties.title) {
//		s += "	<td><div id=\""+varname+"t"+idx+"\" class=ww_winTitle>" + properties.title + "</div></td>";
        s += "	<td id=\"" + varname + "t" + idx + "\" class=ww_winTitle>" + properties.title + "</td>";
    }
    else
        s += "	<td>&nbsp;</td>";

    if (properties.title2) {
        s += "<td class=ww_winTitle style=\"text-align:right\">" + properties.title2 + "</td>\n";
        s += "<td style=\"width:50px;\">&nbsp;</td>\n";
    }

    s += "	</tr>";
    s += "</table>";
    //if(properties.title)
    //	s += "<div id=\""+varname+"t"+idx+"\">&nbsp;" + properties.title + "</div>\n";
    s += "   <img src=\"/assets/common/images/icons/titrwin.gif\" onDragStart=\"return false;\" width=\"100%\" height=\"100%\" style=\"position:absolute; top:0px; left:15px; width:100%; z-index:-1\">";


    s += "<div class=\"ww_close\">\n";

    s += "<img onmousedown=\"" + varname + ".evt_mdwn_reducer(" + idx + ");\" src=\"/assets/common/images/icons/reducer.gif\" hspace=5 />";
    if (this.closeable)
        s += "<img onmousedown=\"" + varname + ".evt_mdwn_closer(" + idx + ");\" src=\"/assets/common/images/icons/closer.gif\" hspace=5 />\n";
    //else
    //	s += "&nbsp;";
    s += "</div>\n";

    s += "	</div>\n";

    //s += "<div class=\"ww_winInternalBorder\" style=\"width:" + this.properties["width"] + ";\">";
    style = "";
    if (this.hasscroll)
        style += "overflow:scroll;";
    else
        style += "overflow:hidden;";

    if (properties.width)
        style += "width" + ":" + (parseInt(this.properties["width"]) - 2) + "px;";
    if (properties.heigth)
        style += "heigth" + ":" + (parseInt(this.properties["heigth"]) - 20) + "px;";


    if (this.twoviews) {
        s += "<div style=\"" + style + "\" id=\"" + varname + "e" + idx + "\" class=\"ww_content2l\"></div>\n";
        s += "<div style=\"" + style + "\" id=\"" + varname + "c" + idx + "\" class=\"ww_content2r\">" + oldContent + "</div>\n";
    }
    else {
        s += "<div style=\"" + style + "\" id=\"" + varname + "c" + idx + "\" class=\"ww_content\">" + oldContent + "</div>\n";
    }
    if (this.sizeable) {
        s += "	<div id=\"" + varname + "r" + idx + "\" class=\"ww_resize\" onMouseDown=\"" + varname + ".evt_mdwn_sizer(" + idx + ");\"><img onDragStart=\"return false\" src=\"/assets/common/images/icons/resizer.gif\"/></div>\n";
    }
//		alert("ww_Window : style=" + style);
    //s += "</div>\n";
    s += "</div>\n";
    s += "</div>\n";

//	alert(s);
//	document.write(s);
    document.getElementById(anchor).innerHTML = s;
    this.setProperties(this.properties);
    if (this.oncreated)
        (this.oncreated)();
    return this;
}
function _ww_createWindow(anchor, properties) {
    this.twin[this.nwin] = new ww_Window(this, anchor, this.varname, this.nwin, properties);
    //this.nwin++;
    return(this.twin[this.nwin++]);
}

// retourne la fenetre au premier plan (hors palettes 'ontop')
function _ww_frontWindow() {
    idx = -1;
    z = -1;
    for (i = 0; i < this.twin.length; i++) {
        if (o2 = document.getElementById(this.varname + "w" + i)) {
            if (o2.style.visibility == "hidden")
                continue;
            z2 = parseInt(o2.style.zIndex);
            if (z2 > z && z2 != 99) {
                z = z2;
                idx = i;
            }
        }
    }
    return(idx >= 0 ? this.twin[idx] : null);
}

function _ww_openCenterWindow(zurl, w, h, name, params) {
    p = new Array();
    p.directories = "no";
    p.location = "no";
    p.menubar = "no";
    p.toolbar = "no";
    p.help = "no";
    p.status = "no";
    p.resizable = "no";
    for (cc in params) {
        if (p[params[cc].n])
            p[params[cc].n] = params[cc].v;
    }
    t = ((document.getElementById(this.desktop).clientHeight - h) / 2) + self.screenTop;
    l = ((document.getElementById(this.desktop).clientWidth - w) / 2) + self.screenLeft;
    if (t < 10)
        t = 10;
    if (l < 10)
        l = 10;
    s = "top=" + t + ", left=" + l + ", width=" + w + ", height=" + h;
    for (cc in p)
        s += "," + cc + "=" + p[cc];
    return(window.open(zurl, name, s, true));
}

function _ww_select(obj) {
    this.unselect();
    if (obj) {
        obj.className = obj.className.replace("s_", "S_");
        this.selectedObject = obj;
    }
}

function _ww_unselect() {
    if (this.selectedObject) {
        var cn = this.selectedObject.className;
        cn = cn.replace("S_", "s_");
        this.selectedObject.className = cn;
        this.selectedObject = null;
    }
}

function _ww_runAsMenu(event, backparm) {
    // alert(event);
    this.backparm = backparm;
    if (this.gui.activeMenu != null) {
        this.gui.activeMenu.style.visibility = "hidden";
        this.gui.activeMenu = null;
    }

    if (typeof(event.which) != 'undefined')
        button = this.gui.firefoxButtons["b" + event.which];	// safari aussi ???
    else
        button = event.button;
    xmouse = parseInt(event.clientX);
    ymouse = parseInt(event.clientY);

    (this.menuCallback)("INIT", this.backparm, null);

    // this.style.top  = (ymouse-3)+"px";
    // this.style.left = (xmouse-3)+"px";

    var bodyH = document.documentElement.clientHeight;

    if (ymouse + this.clientHeight < bodyH) {
        // menu en dessous
        this.style.top = (ymouse - 3) + "px";
        this.style.left = (xmouse - 3) + "px";
    }
    else {
        // menu au dessus
        this.style.top = (ymouse + 0 - this.clientHeight) + "px";
        this.style.left = (xmouse - 3) + "px";
    }

    this.style.visibility = "visible";
    this.gui.activeMenu = this;
}

function ww_dieMenu(mid) {
    var m = document.getElementById(mid);
    if (m.gui.activeMenu) {
        m.style.visibility = "hidden";
        m.gui.activeMenu = null;
        (m.menuCallback)("DIE", m.backparm, null);
    }
}
function _ww_evt_mouseout_menu() {
    this.dieMenuTimer = self.setTimeout("ww_dieMenu('" + this.id + "');", 500);
}
function _ww_evt_mousemove_menu() {
//  var str=''; for (var k in event) {str+='event.'+k+'='+event[k]+'<br/>\n'}
//	document.getElementById("debug").innerHTML = "MOVE "+(iiii++)+"<br/>\n" + str ;
    if (this.dieMenuTimer) {
        clearTimeout(this.dieMenuTimer);
        this.dieMenuTimer = null;
    }
}
function _ww_evt_click_menu(e) {
    if (this.dieMenuTimer) {
        clearTimeout(this.dieMenuTimer);
        this.dieMenuTimer = null;
    }
    if (!e)
        e = window.event;
    var tg = (e.target) ? e.target : e.srcElement;
    while (tg && (tg.nodeType != 1 || !tg.id))
        tg = tg.parentNode;
    if (tg.className == "disabled")
        return;
    this.style.visibility = "hidden";
    this.gui.activeMenu = null;
    (this.menuCallback)("SELECT", this.backparm, tg.id);
}
function _ww_setAsMenu(id, callback) {
    o = this.document.getElementById(id);
    o.ismenu = 1;
    o.gui = this;
    o.runAsMenu = _ww_runAsMenu;
    o.menuCallback = callback;
    o.onmouseout = _ww_evt_mouseout_menu;
    o.onmousemove = _ww_evt_mousemove_menu;
    o.onclick = _ww_evt_click_menu;
}


function GUI(varname, idbody, skin) {
    this.firefoxButtons = {"b65536": 0, "b1": 1, "b2": 4, "b3": 2};
    if (!skin)
        skin = "FR";
    this.skin = skin;
    this.document = document;
    this.sizing = false,
        this.dragging = false,
        this.w_active = -1,
        this.varname = varname;
    this.t0 = 0, this.l0 = 0,		// le top/left initial de la win
        this.w0 = 0, this.h0 = 0,		// le width/height
        this.x0 = 0, this.y0 = 0,		// la pos de la souris
        this.new_t = 0, this.new_l = 0, this.new_w = 0, this.new_h = 0;
    this.nwin = 0;
    this.twin = new Array();		// le tableau des win
    this.maxdepth = 0;			// le zindex de la win au premier plan (hors win 'ontop')
    // this.frontWindow = null;	// la win au premier plan (hors win 'ontop')
    this.body = document.getElementById(idbody);

    this.elemBalloon = null;

    this.createWindow = _ww_createWindow;
    this.frontWindow = _ww_frontWindow;
    this.evt_mdwn_window = _ww_evt_mdwn_window;
    this.evt_mdwn_title = _ww_evt_mdwn_title;
    this.evt_mdwn_sizer = _ww_evt_mdwn_sizer;
    this.evt_mdwn_reducer = _ww_evt_mdwn_reducer;
    this.evt_mdwn_closer = _ww_evt_mdwn_closer;
    this.showBalloon = _ww_evt_balloon;
    this.mdwn_win = _ww_mdwn_win;
    this.openCenterWindow = _ww_openCenterWindow;

    var desk = document.getElementById(idbody);

    var node;

    node = document.createElement("div");
    node.id = this.varname + "wb";
    node.className = "ww_winborder";
    this.windowBorder = desk.appendChild(node);
//		this.windowBorder = document.getElementById("winborder");

    node = document.createElement("img");
    node.id = "dragDropCursor";
    node.style.position = "absolute";
    node.style.top = "50px";
    node.style.left = "50px";
    node.style.zIndex = 99;
    node.style.visibility = "hidden";
    node.src = "/assets/common/images/icons/nodrop01.gif";
    dragObj.objCursor = desk.appendChild(node);

    desk.onmousemove = function (e) {
        _ww_evt_mmov_doc(varname, (e ? e : window.event));
    };
    desk.onmousedown = function (e) {
        var evt = e ? e : window.event;
        // ---- prevent selection into ff
        var srcElement = evt.srcElement ? evt.srcElement : evt.target;
        if (typeof evt.preventDefault != 'undefined' && (srcElement.tagName != "INPUT" && srcElement.tagName != "SELECT" && srcElement.tagName != "TEXTAREA")) {
            evt.preventDefault();
        }
        // ----
        _ww_evt_mdwn_doc(varname, evt);
    };
    desk.onmouseup = function (e) {
        _ww_evt_mup_doc(varname, (e ? e : window.event));
    };

    if (typeof(document.onselectstart) != "undefined") {
        // ie
        document.onselectstart = _evt_select_doc;
    }

    // on interdit les menus contextuels de explorer
//		document.oncontextmenu = function (e) { if(e){e.returnValue=false}else{window.event.returnValue=false}; return false; };
    document.oncontextmenu = function (e) {
        _ww_evt_kon_doc(varname, (e ? e : window.event));
    };


    this.setClickable = function (id, clickCallback) {
        o = this.document.getElementById(id);
        o.clickable = 1;
        o.clickCallback = clickCallback;
    };
    this.setDraggable = function (id, dragCallback) {
        o = this.document.getElementById(id);
        o.draggable = 1;
        o.dragCallback = dragCallback;
    };
    this.setDroppable = function (id, dropCallback) {
        o = this.document.getElementById(id);
        o.droppable = 1;
        o.dropCallback = dropCallback;
    };

    this.activeMenu = null;
    //	this.setAsMenu    = function(id){o=this.document.getElementById(id); o.ismenu=1; o.gui=this; o.runAsMenu=ww_runAsMenu};
    this.setAsMenu = _ww_setAsMenu;

    this.selectedObject = null;
    this.select = _ww_select;
    this.unselect = _ww_unselect;

    this.setDragObj = _ww_setDragObj;
}

function _evt_select_doc(evt) {
    evt = evt ? evt : window.event;
    var srcElement = evt.srcElement ? evt.srcElement : evt.target;
    return(srcElement.tagName == "INPUT" || srcElement.tagName == "TEXTAREA");
}
