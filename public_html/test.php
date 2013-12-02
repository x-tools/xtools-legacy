<?php
// JS and CSS with Timestamp #################################################
function show_stamped_JS($file) {
    $t = filemtime($_SERVER['DOCUMENT_ROOT'] . preg_replace("/^(.*)\.js\??(.*)$/", '$1.js', $file));
    $file = $file . ((strpos($file, ".js?") === false) ? "?" : "&");
    echo "<script src='{$file}v=" . md5(date("YmdHis", $t)) . "' type='text/javascript' language='JavaScript'></script>";
}
function show_stamped_CSS($file) {
    $t = filemtime($_SERVER['DOCUMENT_ROOT'] . $file);
    echo "<link rel='stylesheet' type='text/css' href='$file?v=" . md5(date("YmdHis", $t)) . "'></link>";
}
// JSLoader ##################################################################
function JSLoader($ar, $div) {
    // $ar is an array of JS files to be loaded, JS Loader reports progress with a progress bar etc
    // $div the id of the div where the progress will be reported
    if (!defined('JSLOADER_JS_SET')) {
        define('JSLOADER_JS_SET',true);
        // load the JSloader javascript
?>
<!-- JSLoader Script -->
<script language='JavaScript' type='text/javascript'>
var JSLoader = function(){
    // namespaced private variables & functions
    var JSL = {
        totalSize:0, sizeSoFar:0, currentTotal:0, div:"",
        startTime:"", rate:0, timerOn:false, intrval:100,
        drawBox: function(){
            JSL.div.innerHTML = "<div class='JSLoader-barholder'>"
                + "<div class='JSLoader-bar' id='JSLoaderBar' style='width:0%;'>"
                + "&nbsp;Loading:&nbsp;<span id='JSLoaderText'>0</span>%"
                + "</div>"
                + "</div>";
            JSL.bar = document.getElementById('JSLoaderBar');
            JSL.barText = document.getElementById('JSLoaderText');
        },
        update: function(fr) {
            // shows the box to fraction fr (of 1)
            if (JSL.bar === undefined) {
                JSL.drawBox();
            }
            pc = Math.floor(fr * 100);
            JSL.bar.style.width = pc + "%";
            JSL.barText.innerHTML = pc;
        },
        timeUpdate: function(){
            if (JSL.timerOn) {
                JSL.currentTotal = JSL.currentTotal + JSL.rate;
                if (JSL.currentTotal <= JSL.totalSize) {
                    JSL.update(JSL.currentTotal / JSL.totalSize);
                    setTimeout(JSL.timeUpdate, JSL.intrval);
                }
            }
        }
    };
    return {
        init: function(tS, divId){
            // sets up everything
            JSL.totalSize = tS;
            JSL.div = document.getElementById(divId);
            JSL.startTime = new Date();
            JSL.rate = 1000;
            JSL.timerOn = false;
            JSL.currentTotal = 0;    // the virtual amount of bytes
            JSL.sizeSoFar = 0;    // the actual amount of bytes
        },
        startFile: function(fName, fSize){
            // call this before a file loads, after the previous file has finished
            if (JSL.sizeSoFar == 0) {
                JSL.update(0);
                JSL.timerOn = true;
                setTimeout(JSL.timeUpdate, JSL.intrval);
            } else {
                // timer is on,
                // recalculate the 'rate' - ie the increment each intrval
                var t = new Date();
                var currRate = (JSL.sizeSoFar == 0) ? 0 : JSL.sizeSoFar / (t.getTime() - JSL.startTime.getTime());
                var timeRemaining = (JSL.totalSize - JSL.sizeSoFar) / currRate;
                if (timeRemaining != 0) {
                    JSL.rate = JSL.intrval * ((JSL.totalSize - JSL.currentTotal) / timeRemaining);
                }
            }
            JSL.sizeSoFar += fSize;
        },
        complete: function(){
            // call this to end it all!
            JSL.timerOn = false;
            JSL.update(1);
        }
    }
}();
</script>
<?php        
    }    // eof first run JS
    
    // calculate total file size
    // changing file names to get rid of any ?thing=wotsit
    $t = 0;
    $fName = "";
    foreach ($ar as $f) {
        $f1 = preg_replace("/^(.*)\.js\??(.*)$/", '$1.js', $f);
        $fName[$f] = $f1;
        $t = $t + filesize($_SERVER['DOCUMENT_ROOT'] . $f1);
    }
    // call the start function
    echo "<script type='text/javascript' language='JavaScript'>"
        . "JSLoader.init('$t','$div',$width);"
        . "</script>";
    // load each file, and report in between
    foreach ($ar as $f) {
        // report
        echo "<script type='text/javascript' language='JavaScript'>"
            . "JSLoader.startFile('$f'," . filesize($_SERVER['DOCUMENT_ROOT'] . $fName[$f]) . ");"
            . "</script>";
        show_stamped_JS($f);
    }
    // complete it 
    echo "<script type='text/javascript' language='JavaScript'>"
        . "JSLoader.complete();"
        . "</script>";
    
}
?>