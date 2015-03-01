var JFormValidator=function(){"use strict";var e,t,a,r=function(t,a,r){r=""===r?!0:r,e[t]={enabled:r,exec:a}},i=function(e,t){var a,r=jQuery(t);return e?(a=r.find("#"+e+"-lbl"),a.length?a:(a=r.find('label[for="'+e+'"]'),a.length?a:!1)):!1},n=function(e,t){var a=t.data("label");void 0===a&&(a=i(t.attr("id"),t.data("form")),t.data("label",a)),e===!1?(t.addClass("invalid").attr("aria-invalid","true"),a&&a.addClass("invalid").attr("aria-invalid","true")):(t.removeClass("invalid").attr("aria-invalid","false"),a&&a.removeClass("invalid").attr("aria-invalid","false"))},l=function(t){var a,r,i=jQuery(t);if(i.attr("disabled"))return n(!0,i),!0;if(i.attr("required")||i.hasClass("required"))if(a=i.prop("tagName").toLowerCase(),"fieldset"===a&&(i.hasClass("radio")||i.hasClass("checkboxes"))){if(!i.find("input:checked").length)return n(!1,i),!1}else if(!i.val()||i.hasClass("placeholder")||"checkbox"===i.attr("type")&&!i.is(":checked"))return n(!1,i),!1;return r=i.attr("class")&&i.attr("class").match(/validate-([a-zA-Z0-9\_\-]+)/)?i.attr("class").match(/validate-([a-zA-Z0-9\_\-]+)/)[1]:"",""===r?(n(!0,i),!0):r&&"none"!==r&&e[r]&&i.val()&&e[r].exec(i.val(),i)!==!0?(n(!1,i),!1):(n(!0,i),!0)},o=function(e){var t,r,i,n,o,u=!0;if(jQuery.each(jQuery(e).find("input, textarea, select, fieldset, button"),function(e,t){l(t)===!1&&(u=!1)}),jQuery.each(a,function(e,t){t.exec()!==!0&&(u=!1)}),!u){r=Joomla.JText._("JLIB_FORM_FIELD_INVALID"),i=jQuery("input.invalid, textarea.invalid, select.invalid, fieldset.invalid, button.invalid");var s=i.length,d=document.getElementById("system-message-container");for(n={},n.error=[],t=0;t<i.length;t++)o=jQuery("label[for="+i[t].id+"]").text(),n.error[t]=6>s&&"undefined"!==o?r+o.replace("*",""):1===t?Joomla.JText._("JLIB_FORM_FIELDS_REQUIRED"):"";n.error.reverse(),n.error[t]="<h4>"+Joomla.JText._("JLIB_FORM_NC")+"</h4>",Joomla.renderMessages(n),d.scrollIntoView(!0)}return u},u=function(e){var a=[],r=jQuery(e);r.find("input, textarea, select, fieldset, button").each(function(){var i=jQuery(this),n=i.prop("tagName").toLowerCase();i.hasClass("required")&&i.attr("aria-required","true").attr("required","required"),"input"!==n&&"button"!==n||"submit"!==i.attr("type")?("fieldset"!==n&&(i.on("blur",function(){return l(this)}),i.hasClass("validate-email")&&t&&(i.get(0).type="email")),i.data("form",r),a.push(i)):i.hasClass("validate")&&i.on("click",function(){return o(e)})}),r.data("inputfields",a)},s=function(){e={},a=a||{},t=function(){var e=document.createElement("input");return e.setAttribute("type","email"),"text"!==e.type}(),r("username",function(e){var t=new RegExp("[<|>|\"|'|%|;|(|)|&]","i");return!t.test(e)}),r("password",function(e){var t=/^\S[\S ]{2,98}\S$/;return t.test(e)}),r("numeric",function(e){var t=/^(\d|-)?(\d|,)*\.?\d*$/;return t.test(e)}),r("email",function(e){e=punycode.toASCII(e);var t=/^[a-zA-Z0-9.!#$%&’*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;return t.test(e)}),jQuery("form.form-validate").each(function(){u(this)})};return s(),{isValid:o,validate:l,setHandler:r,attachToForm:u,custom:a}};document.formvalidator=null,jQuery(function(){document.formvalidator=new JFormValidator});
