YUI.add("moodle-local_mbs-shortname",function(e,t){M.local_mbs=M.local_mbs||{},M.local_mbs.shortname={parent:null,shortnamefield:null,id:null,timeoutid:null,init:function(){"use strict";this.id=e.one("input[name=id]").get("value"),this.shortnamefield=e.one("input#id_shortname"),this.parent=this.shortnamefield.ancestor(),this.shortnamefield.on("valuechange",this.queueShortnameCheck,this)},queueShortnameCheck:function(){"use strict";var e;this.timeoutid&&(window.clearTimeout(this.timeoutid),this.timeoutid=null),e=this,this.timeoutid=window.setTimeout(function(){e.checkShortname()},800)},checkShortname:function(){"use strict";var t;this.parent.all("#shortnameerror").remove(),t=this.shortnamefield.get("value");if(!t)return;e.io(M.cfg.wwwroot+"/local/mbs/ajax.php",{data:{id:this.id,shortname:t,action:"checkshortname"},context:this,on:{success:function(t,n){var r;r=e.JSON.parse(n.responseText),r.response==="Exists"&&this.parent.prepend('<span id="shortnameerror"><span class="error">'+r.error+"</span><br /></span>")}}})}}},"@VERSION@",{requires:["base","json","event-valuechange"]});