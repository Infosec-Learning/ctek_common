!function(e){var n={};function t(o){if(n[o])return n[o].exports;var r=n[o]={i:o,l:!1,exports:{}};return e[o].call(r.exports,r,r.exports,t),r.l=!0,r.exports}t.m=e,t.c=n,t.d=function(e,n,o){t.o(e,n)||Object.defineProperty(e,n,{enumerable:!0,get:o})},t.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},t.t=function(e,n){if(1&n&&(e=t(e)),8&n)return e;if(4&n&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(t.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&n&&"string"!=typeof e)for(var r in e)t.d(o,r,function(n){return e[n]}.bind(null,r));return o},t.n=function(e){var n=e&&e.__esModule?function(){return e.default}:function(){return e};return t.d(n,"a",n),n},t.o=function(e,n){return Object.prototype.hasOwnProperty.call(e,n)},t.p="",t(t.s=0)}([function(e,n){function t(e,n){for(var t=0;t<n.length;t++){var o=n[t];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}var o=function(){function e(){!function(e,n){if(!(e instanceof n))throw new TypeError("Cannot call a class as a function")}(this,e)}var n,o,r;return n=e,r=[{key:"isDebug",value:function(e){return"prod"!==e.env}},{key:"register",value:function(n,t,o){var r=null===n?t:n+"_"+t;e.isDebug(drupalSettings)&&console.log("Registered Behavior: ".concat(r)),Drupal.behaviors[r]=new o}},{key:"resolve",value:function(e,n){return Drupal.behaviors[e+"_"+n]}}],(o=[{key:"attach",value:function(n,t){void 0===this._firstInvocation&&(this._firstInvocation=!0),this._firstInvocation&&(this.settings=t,this.debug=e.isDebug(t),this.onReady()),this.onContent(n),this._firstInvocation=!1}},{key:"detach",value:function(e){this.onRemoveContent(e)}},{key:"onReady",value:function(){}},{key:"onContent",value:function(e){}},{key:"onRemoveContent",value:function(e){}}])&&t(n.prototype,o),r&&t(n,r),e}();window.DrupalBehavior=o,window.$=jQuery}]);
//# sourceMappingURL=DrupalBehavior.js.map