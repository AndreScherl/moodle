YUI.add("moodle-block_mbsnews-editjobform",function(e,t){M.block_mbsnews=M.block_mbsnews||{},M.block_mbsnews.editjobform=function(t){function l(){var e=n.get("value");e==0||e==10?i.set("disabled","disabled"):i.removeAttribute("disabled")}function c(){s.set("value",""),r.set("innerHTML",""),i.set("value",""),o.set("value",0),u.set("value",0)}function h(){c(),d(),l()}function p(n,r){e.io(t.url,{data:n,on:{success:function(t,n){var i;try{i=e.JSON.parse(n.responseText)}catch(s){return}i.error!==0?alert(i.error):r(i.results)}}})}function d(){var e={};e.action="getroleoptions",e.contextlevel=n.get("value"),p(e,function(e){v(e)})}function v(t){o.set("innerHTML","");for(var n=0;n<t.length;n++){var r=t[n];o.append(e.Node.create('<option value="'+r.value+'">'+r.text+"</option>"))}o.set("value",u.get("value"))}function m(){var t={};t.action="searchrecipients",t.contextlevel=n.get("value"),t.roleid=u.get("value");var r=new Array;e.all('input[name^="instanceidsselected"]').each(function(e,t){var n=e.get("name").split("[")[1];n=n.substr(0,n.length-1),r[t]=n}),t.instanceids=r.join(","),p(t,function(e){g(e)})}function g(e){a.set("innerHTML",e.list),f.set("value",e.count)}function y(){n=e.one("#id_contextlevel"),s=e.one("#id_instanceids"),r=e.one("#id_instanceids_list"),i=e.one("#id_instanceids_search"),o=e.one("#id_roleselector"),u=e.one("#id_roleid"),a=e.one("#id_recipients"),f=e.one("#id_countrecipients"),n.on("change",function(e){h(),m()}),s.on("change",function(e){m()}),o.on("change",function(e){u.set("value",o.get("value")),m()}),l(),d(),m()}var n,r,i,s,o,u,a,f;y()}},"@VERSION@",{requires:["base","node","io-base"]});