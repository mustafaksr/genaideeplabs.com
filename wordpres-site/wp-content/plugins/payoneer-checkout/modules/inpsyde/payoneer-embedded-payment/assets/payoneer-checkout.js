!function(){"use strict";var e={n:function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,{a:n}),n},d:function(t,n){for(var r in n)e.o(n,r)&&!e.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:n[r]})},o:function(e,t){return Object.prototype.hasOwnProperty.call(e,t)}},t=window.jQuery,n=e.n(t),r=function(){var e;return null===(e=document.querySelector('.woocommerce-checkout input[name="payment_method"]:checked'))||void 0===e?void 0:e.id},o=function(){return/^payment_method_payoneer-checkout/i.test(r())},i=function(){return"1"===window.PayoneerData.isPayForOrder},a=function(){var e=".payment_box."+r();return document.querySelector(e)};function u(e,t,n,r,o,i,a){try{var u=e[i](a),c=u.value}catch(e){return void n(e)}u.done?t(c):Promise.resolve(c).then(r,o)}function c(e){return function(){var t=this,n=arguments;return new Promise((function(r,o){var i=e.apply(t,n);function a(e){u(i,r,o,a,c,"next",e)}function c(e){u(i,r,o,a,c,"throw",e)}a(void 0)}))}}function s(e){return s="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},s(e)}function l(e){var t=function(e,t){if("object"!=s(e)||!e)return e;var n=e[Symbol.toPrimitive];if(void 0!==n){var r=n.call(e,"string");if("object"!=s(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(e)}(e);return"symbol"==s(t)?t:t+""}function d(e,t,n){return(t=l(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function p(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,l(r.key),r)}}var f=window.regeneratorRuntime,y=e.n(f);function m(e,t){if(null==e)return{};var n,r,o=function(e,t){if(null==e)return{};var n={};for(var r in e)if({}.hasOwnProperty.call(e,r)){if(t.indexOf(r)>=0)continue;n[r]=e[r]}return n}(e,t);if(Object.getOwnPropertySymbols){var i=Object.getOwnPropertySymbols(e);for(r=0;r<i.length;r++)n=i[r],t.indexOf(n)>=0||{}.propertyIsEnumerable.call(e,n)&&(o[n]=e[n])}return o}var h=function(e){var t;return null!==(t=window.Payoneer)&&void 0!==t&&t.CheckoutWeb?window.Payoneer.CheckoutWeb(e):new Promise((function(t,n){var r,o,i=document.createElement("script");i.src=(r=e.env,new URL(null===(o=window.PayoneerData.webSdkUmdUrlTemplate)||void 0===o?void 0:o.replace("<env>",r))),i.onload=t,i.onerror=n,document.head.appendChild(i)})).then((function(){return window.Payoneer.CheckoutWeb(e)}))},b=["env","longId"];function v(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function w(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?v(Object(n),!0).forEach((function(t){d(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):v(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}var O={},g=function(){var e=c(y().mark((function e(t){var n,r,o,i;return y().wrap((function(e){for(;;)switch(e.prev=e.next){case 0:if(n=t.env,r=t.longId,o=m(t,b),i="".concat(n,"-").concat(r),O.hasOwnProperty(i)){e.next=6;break}return e.next=5,h(w({env:n,longId:r},o));case 5:O[i]=e.sent;case 6:return e.abrupt("return",O[i]);case 7:case"end":return e.stop()}}),e)})));return function(_x){return e.apply(this,arguments)}}();function k(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}var P=function(){return e=function e(t){var n=t.element,r=t.paymentFieldsComponentAttribute,o=t.extraSdkOptions,i=t.listUrlContainerId,a=t.listUrlContainerIdAttribute,u=t.listUrlContainerEnvAttribute,c=t.hostedModeOverrideFlag,s=t.onErrorRefreshFragmentFlag;!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this.dropInContainer=n.querySelector(".payoneer-payment-fields-container"),this.dropInComponentName=this.dropInContainer.getAttribute(r),this.listUrlInput=n.querySelector(".".concat(i)),this.hostedModeOverride=n.querySelector("input[name=".concat(c,"]")),this.onErrorRefreshFlag=n.querySelector("input[name=".concat(s,"]")),this.initSdk(this.sdkOptions(a,u,o))},t=[{key:"sdkOptions",value:function(e,t,n){return function(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?k(Object(n),!0).forEach((function(t){d(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):k(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}({env:this.listUrlInput.getAttribute(t),longId:this.listUrlInput.getAttribute(e),onBeforeError:this.onSdkError.bind(this),preloadComponents:[this.dropInComponentName]},n)}},{key:"initSdk",value:(r=c(y().mark((function e(t){return y().wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return e.prev=0,e.next=3,g(t);case 3:this.sdk=e.sent,this.mount(),e.next=11;break;case 7:e.prev=7,e.t0=e.catch(0),console.error(e.t0),this.hostedModeOverride.removeAttribute("disabled");case 11:case"end":return e.stop()}}),e,this,[[0,7]])}))),function(_x){return r.apply(this,arguments)})},{key:"dropIn",value:function(){if(!this.sdk)throw new Error("dropIn() called before SDK was initialized.");if(this._dropIn)return this._dropIn;if(!this.sdk.availableDropInComponents().map((function(e){return e.name})).includes(this.dropInComponentName))throw new Error("Invalid dropIn component provided: "+this.dropInComponentName);return this._dropIn=this.sdk.dropIn(this.dropInComponentName,{hidePaymentButton:!0})}},{key:"onSdkError",value:function(){if(i()){var e=new URL(document.location);return e.searchParams.set(window.PayoneerData.payOrderErrorFlag,!0),window.location.href=e.toString(),!1}return this.onErrorRefreshFlag.value=!0,n()(document.body).trigger("update_checkout"),!1}},{key:"mount",value:function(){var e=this.dropIn();this.dropInContainer.innerHTML="",this.hostedModeOverride.setAttribute("disabled","disabled"),e.mount(this.dropInContainer)}},{key:"unmount",value:function(){this.dropIn().unmount()}},{key:"pay",value:function(){this.dropIn().pay()}}],t&&p(e.prototype,t),Object.defineProperty(e,"prototype",{writable:!1}),e;var e,t,r}(),I=P;document.addEventListener("DOMContentLoaded",(function(){var e=n()("form.checkout"),t=n()("#order_review"),r=null,u=function(){r&&r.unmount(),o()&&function(){var e=a();if(e){var t=window.PayoneerData,n=t.paymentFieldsComponentAttribute,o=t.listUrlContainerId,i=t.listUrlContainerIdAttribute,u=t.listUrlContainerEnvAttribute,c=t.hostedModeOverrideFlag,s=t.onErrorRefreshFragmentFlag,l=t.websdkStyles;r=new I({element:e,paymentFieldsComponentAttribute:n,listUrlContainerId:o,listUrlContainerIdAttribute:i,listUrlContainerEnvAttribute:u,hostedModeOverrideFlag:c,onErrorRefreshFragmentFlag:s,extraSdkOptions:{styles:l}})}}()},c=function(){r&&r.pay()};n()(document.body).on("payment_method_selected updated_checkout",u),e.on("checkout_place_order_success",(function(t,r){if(o())return c(),r.redirect="#payoneer-redirect",setTimeout((function(){e.removeClass("processing").unblock(),n().scroll_to_notices(n()(a()))}),1500),!0})),t.on("submit",(function(e){var r;o()&&(e.preventDefault(),e.stopImmediatePropagation(),(r=t,r.block({message:null,overlayCSS:{background:"#fff",opacity:.6}}),n().ajax({type:"POST",url:window.wc_checkout_params.ajax_url,xhrFields:{withCredentials:!0},dataType:"json",data:{action:"payoneer_order_pay",fields:r.serialize(),params:new URL(document.location).searchParams.toString()}}).always((function(){return r.unblock()}))).then((function(){return c()})).catch((function(){window.location.reload()})))})),i()&&(u(),n()('input[name="payment_method"]').on("change",u))}),!1)}();
//# sourceMappingURL=payoneer-checkout.js.map