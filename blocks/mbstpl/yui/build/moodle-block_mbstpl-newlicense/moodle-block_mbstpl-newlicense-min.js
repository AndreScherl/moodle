YUI.add("moodle-block_mbstpl-newlicense",function(e,t){M.block_mbstpl=M.block_mbstpl||{},M.block_mbstpl.newlicense={init:function(t,n){var r=e.one("#id_"+n),i=e.one("#fgroup_id_"+t).all("input");r.on("change",function(){var e=r.get("value")==="__createnewlicense__";i.set("disabled",!e)})}}},"@VERSION@",{requires:["base","node"]});