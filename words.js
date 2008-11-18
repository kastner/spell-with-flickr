function $(id) {
    if (typeof id == 'string') {
        return document.getElementById(id);
    }
    else {
        return id;
    }
}
function getCookie(str) {
    var ret;
    var sRe = new RegExp(".*"+str+"=([^;]+);?.*");
    if (document.cookie.match(sRe)) ret = document.cookie.replace(sRe, "$1");
    return ret;
}
function setCookie(str, val) {
    var exp = new Date();
    exp.setTime(exp.getTime()+31536000000); //one year
    document.cookie = str + "=" + val + ";expires="+exp.toGMTString()+";path=/";
}
function changesize(sz,  ta_rnd) {
    size = sz;
    var sR = new RegExp("_"+oldsize+".jpg", "g");
    $('flickrImages').innerHTML = $('flickrImages').innerHTML.replace(sR, "_"+size+".jpg");
    $('flickrSource').innerHTML = $('flickrSource').innerHTML.replace(sR, "_"+size+".jpg");
    $('badge').innerHTML = $('badge').innerHTML.replace(/picsize=./, "picsize="+size);
    $('flickrOut_' + ta_rnd).value = replaceLeg($('flickrSource').innerHTML);
    oldsize = size;
    setCookie("size", size);
}

function replaceLeg(src) {
    src = src.replace(/<LEGEND>On Flickr<\/LEGEND>/, '');   //FOR IE
    src = src.replace(/^[\s.]*<legend>.*<\/legend>\s*/,'');
    return src;
    //fsIH = fsIH.replace('<LEGEND>.*</LEGEND>', '');
}

function subform() {
    window.location = "/words/" + document.getElementById("words").value;
}

function swapImg(id, ele, ta_rnd) {
    var spot = Math.floor(Math.random()*letters[id].length);
    var let = letters[id][spot];

    fs_id = $(ele).id.replace(/f_/, "fs_");

    newImg = "<img border='0' alt='"+let.t+"' title='"+let.t+"' src='http://static.flickr.com/"+let.s+"/"+let.id+"_"+let.st+"_"+size+".jpg'/>";

    $(ele).innerHTML=newImg;
    $(fs_id).href="http://www.flickr.com/photos/"+let.o+"/"+let.id;
    $(fs_id).title='"' + let.t + '"';
    $(fs_id).innerHTML = newImg;

    $('flickrOut_' + ta_rnd).value = replaceLeg($('flickrSource').innerHTML);
}

sz = getCookie("size");
var oldsize = (sz) ? sz : "t";
var size=oldsize;
var x, y;
var offsetx, offsety;

