/** @license Hyphenator_Loader 5.3.0 - client side hyphenation for webbrowsers
 *  Copyright (C) 2017  Mathias Nater, Zürich (mathiasnater at gmail dot com)
 *  https://github.com/mnater/Hyphenator
 *
 *  Released under the MIT license
 *  http://mnater.github.io/Hyphenator/LICENSE.txt
 */
var Hyphenator_Loader=function(e){"use strict";var t,n,a,o=function(t){var n;return e.document.createElementNS?n=e.document.createElementNS("http://www.w3.org/1999/xhtml",t):e.document.createElement&&(n=e.document.createElement(t)),n},d=function(t){var n,d,r=!1;n=e.document.getElementsByTagName("head").item(0),d=o("script"),d.src=a,d.type="text/javascript",d.onreadystatechange=function(){r||d.readyState&&"loaded"!==d.readyState&&"complete"!==d.readyState||(r=!0,Hyphenator.config(t),Hyphenator.run(),d.onreadystatechange=null,d.onload=null,n&&d.parentNode&&n.removeChild(d))},d.onload=d.onreadystatechange,n.appendChild(d)},r=function(){var a,r,l,i=o("body");a=o("div"),a.style.visibility="hidden",i.appendChild(a),e.document.documentElement.appendChild(i);for(l in t)if(t.hasOwnProperty(l)&&(r=o("div"),r.style.MozHyphens="auto",r.style["-webkit-hyphens"]="auto",r.style["-ms-hyphens"]="auto",r.style.hyphens="auto",r.style.width="5em",r.style.lineHeight="12px",r.style.border="none",r.style.padding="0",r.style.wordWrap="normal",r.style["-webkit-locale"]="'"+l+"'",r.lang=l,r.appendChild(e.document.createTextNode(t[l])),a.appendChild(r),r.offsetHeight<=13)){d(n);break}i.parentNode.removeChild(i)};return{init:function(e,o,d){t=e,a=o,n=d||{},r()}}}(window);
