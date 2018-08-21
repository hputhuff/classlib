<?php
/**
 * Display a Dialog Box on a page
 * August 2015 by Harley H. Puthuff
 * Copyright 2015, Your Showcase
 */
?>

<!--styles for dialog & background-->

<style type="text/css">
#dialogBG {display: none; position: fixed; _position: absolute; height: 100%; width: 100%;
	   top: 0; left: 0; background: #000000; border: 1px solid #cecece; z-index: 1;}
#dialogBox {
	display: none;
	position: fixed;
	_position: absolute;
	height:500px; width:600px;
	border-radius: 20px;
	border-top: 1px solid gainsboro;
	border-left: 1px solid gainsboro;
	box-shadow: 4px 4px black;
	background: white;
	z-index: 2;
	padding: 10px;
	font: 14pt tahoma,sans;
	text-align: left;
	}
</style>

<!--javascript to handle dialog box enable/disable/etc.-->

<script type="text/javascript">
var dialogVisible = false;	// false=hidden, true=visible
var dialogCallback = null;	// callback function when disabled
var dialogDataback = null;	// data from fg dialog process
function enableDialog(){
	if (!dialogVisible) {
		$("#dialogBG").css({"opacity": "0.5"});
		$("#dialogBG").fadeIn("slow");
		$("#dialogBox").fadeIn("slow");
		dialogVisible = true;
		}
	}
function disableDialog(returnValue){
	if (dialogVisible) {
		dialogDataback = returnValue;
		$("#dialogBG").fadeOut("slow");
		$("#dialogBox").fadeOut("slow");
		dialogVisible = false;
		if (dialogCallback) dialogCallback(dialogDataback);
		}
	}
function centerDialog(){
	var winW=1024,winH=768,x,y;
	if (parseInt(navigator.appVersion)>3) {
		if (navigator.appName=="Netscape") {
			winW = window.innerWidth;
			winH = window.innerHeight;
			}
		if (navigator.appName.indexOf("Microsoft")!=-1) {
			winW = document.body.offsetWidth;
			winH = document.body.offsetHeight;
			}
		}
	x = ((winW - $("#dialogBox").width()) / 2);
	y = ((winH - $("#dialogBox").height()) / 2) - 20;
	$("#dialogBox").css({"position": "absolute","top": y,"left": x});
	}
function displayDialog(contents,callback) {
	// for now, contents are text only
	$("#dialogBox").html(contents);
	$("#dialogBG").click(function(){disableDialog();});
//	$("#dialogBox").click(function(){disableDialog();});
//	$(document).keypress(function(e){if(e.keyCode==27 && dialogVisible) disableDialog();});
	centerDialog();
	enableDialog();
	}
</script>

<!--html for the dialog box container & background-->
<div id="dialogBox"></div>
<div id="dialogBG"></div>
