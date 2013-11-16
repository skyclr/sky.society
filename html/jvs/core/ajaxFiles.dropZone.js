/* Class to work with drop zone for File api */
sky.AjaxFiles.dropZone = function(options) {

	if(!sky.XHRIsSupported) {
		this.Callbacks.onNonSupported();
		return false;
	}
	this.options 	= options;
	this.zone 	 	= options.zone;
	this.Callbacks 	= options.Callbacks;
	this.data  		= options.data;
	this.url  		= options.url;
	this.files   	= [];
	
	this.attachEvents();
}

/* Prototype functions */
jQuery.extend(sky.AjaxFiles.dropZone.prototype, {

	attachEvents: function(){
        var self = this;              
                  
        /* While over event */
        self.zone.bind({
        
        	dragover: function(e){
        		e = e.originalEvent;
	            if (!self.isValidFileDrag(e)) return;
	            
	            var effect = e.dataTransfer.effectAllowed;
	            if (effect == 'move' || effect == 'linkMove'){
	                e.dataTransfer.dropEffect = 'move'; // for FF (only move allowed)    
	            } else {                    
	                e.dataTransfer.dropEffect = 'copy'; // for Chrome
	            }
	                                                     
	            e.stopPropagation();
	            e.preventDefault();                                                                    
	        },
        	dragenter: function(e) { if(typeof self.options.onEnter !== "undefined") self.options.onEnter.apply(self, [e]); },
	        dragleave: function(e) { if(typeof self.options.onLeave !== "undefined") self.options.onLeave.apply(self, [e]); },
	        dragend  : function(e) { if(typeof self.options.onEnd   !== "undefined") self.options.onEnd.apply(self, [e]); },
	        drop: function(e){
	            if (!self.isValidFileDrag(e.originalEvent)) return;
	            self.options.onDrop.apply(self, [e]);
	        }
	    });          
        
        /* Stop file opening when drop on browser window */
		$(document).bind({
			dragover: function(e){
				e = e.originalEvent; 
		        if (e.dataTransfer){
		            e.dataTransfer.dropEffect = 'none';
		            e.preventDefault(); 
		        }
        	},
        	dragenter: function(e) { if(typeof self.options.onStart !== "undefined") self.options.onStart.apply(self, [e]); }
        });
    },
    
    isValidFileDrag: function(e){
 
        var dt = e.dataTransfer,
            // do not check dt.types.contains in webkit, because it crashes safari 4            
            isWebkit = navigator.userAgent.indexOf("AppleWebKit") > -1;                        

        // dt.effectAllowed is none in Safari 5
        // dt.types.contains check is for firefox            
        return dt && dt.effectAllowed != 'none' && 
            (dt.files || (!isWebkit && dt.types.contains && dt.types.contains('Files')));
        
    }        

});