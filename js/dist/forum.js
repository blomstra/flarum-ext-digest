(()=>{var t={n:e=>{var r=e&&e.__esModule?()=>e.default:()=>e;return t.d(r,{a:r}),r},d:(e,r)=>{for(var o in r)t.o(r,o)&&!t.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:r[o]})},o:(t,e)=>Object.prototype.hasOwnProperty.call(t,e),r:t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})}},e={};(()=>{"use strict";function r(){return r=Object.assign||function(t){for(var e=1;e<arguments.length;e++){var r=arguments[e];for(var o in r)Object.prototype.hasOwnProperty.call(r,o)&&(t[o]=r[o])}return t},r.apply(this,arguments)}t.r(e);const o=flarum.core.compat["forum/app"];var s=t.n(o);const n=flarum.core.compat["common/extend"],a=flarum.core.compat["common/components/Select"];var i=t.n(a);const u=flarum.core.compat["forum/components/SettingsPage"];var l=t.n(u);s().initializers.add("blomstra/digest",(function(){(0,n.extend)(l().prototype,"notificationsItems",(function(t){var e,o=this;t.add("digestFrequency",m(".Form-group",[m("label",s().translator.trans("blomstra-digest.forum.settings.frequency")),i().component({options:{immediate:s().translator.trans("blomstra-digest.forum.settings.frequencyOptions.immediate"),daily:s().translator.trans("blomstra-digest.forum.settings.frequencyOptions.daily"),weekly:s().translator.trans("blomstra-digest.forum.settings.frequencyOptions.weekly")},value:this.user.attribute("digestFrequency")||"immediate",onchange:function(t){"immediate"===t&&(t=null),o.digestFrequencyLoading=!0;var e={digestFrequency:t},s=o.user.preferences();"flarum-subscriptions.notify_for_all_posts"in s&&(e.preferences=r({},s,{"flarum-subscriptions.notify_for_all_posts":!!t})),o.user.save(e).then((function(){o.digestFrequencyLoading=!1,m.redraw()}))},disabled:this.digestFrequencyLoading},s().translator.trans("flarum-subscriptions.forum.settings.follow_after_reply_label"))])),t.has("notifyForAllPosts")&&this.user.attribute("digestFrequency")&&null!=(e=this.user.preferences())&&e["flarum-subscriptions.notify_for_all_posts"]&&(t.get("notifyForAllPosts").attrs.disabled=!0),console.log(this.user.attribute("digestFrequency")),null!==this.user.attribute("digestFrequency")&&t.add("digestHour",m(".Form-group",[m("label",s().translator.trans("blomstra-digest.forum.settings.hour")),i().component({options:Array.from(Array(24).keys()).reduce((function(t,e){return t[e]=e.toString().padStart(2,"0")+":00 UTC",t}),{}),value:this.user.attribute("digestHour")||"0",onchange:function(t){o.digestHourLoading=!0;var e={digestHour:t};o.user.save(e).then((function(){o.digestHourLoading=!1,m.redraw()}))},disabled:this.digestHourLoading})]))}))}))})(),module.exports=e})();
//# sourceMappingURL=forum.js.map