!function(a,b){"use strict";var c={};c.getAssetURL=function(a){var b=a.indexOf("?")!=-1?"&ts=":"?ts=",c=(new Date).getTime();return a+b+c},c.loadJavaScript=function(a){if(!b('script[src*="'+a+'"]').length&&!b('script[data-source~="'+a+'"]').length){var a=this.getAssetURL(a);b("head").append('<script type="text/javascript" src="'+a+'"></script>')}},c.loadCSS=function(a){if(navigator.userAgent.indexOf("MSIE")!=-1){var a=this.getAssetURL(a),c=document.createElement("link"),d=document.getElementsByTagName("head")[0];c.type="text/css",c.rel="stylesheet",c.href=a,c.media="screen",d.appendChild(c)}else if(!b("head").children('link[href*="'+a+'"]').length&&!b("head").children('link[data-source~="'+a+'"]').length){var a=this.getAssetURL(a);b("head").append('<link rel="stylesheet" media="screen" type="text/css" href="'+a+'" />')}},c.loadOther=function(a){b("head").children(a).length||b("head").append(a)},a.ConcreteAssetLoader=c}(this,$),ccm_addHeaderItem=function(a,b){"CSS"==b?ConcreteAssetLoader.loadCSS(a):"JAVASCRIPT"==b?ConcreteAssetLoader.loadJavaScript(a):ConcreteAssetLoader.loadOther(a)};