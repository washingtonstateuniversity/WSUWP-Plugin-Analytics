window.wsu_analytics.global.events=[];
window.wsu_analytics.app.events=[];
window.wsu_analytics.site.events = [
		{
			element:"a[href^='http']:not([href*='wsu.edu']), .track.outbound",
			options:{
				mode:"event,_link",
				category:"outbound"
			}
		},
		{
			element:"a[href*='wsu.edu']:not([href*='**SELF_DOMAIN**']), .track.internal",
			options:{
				skip_internal:true,
				mode:"event,_link",
				category:"internal"
			}
		},
		{
			element:"a[href*='zzusis.wsu.edu'],\
					 a[href*='portal.wsu.edu'],\
					 a[href*='applyweb.com/public/inquiry'],\
					 a[href*='www.mme.wsu.edu/people/faculty/faculty.html'],\
					 a[href*='puyallup.wsu.edu'],\
					 .track.internal.skip_campaign",
			options:{
				skip_internal:true,
				mode:"event,_link",
				category:"internal",
				skip_campaign:true,
				overwrites:true
			}
		},
		// Externals that are known to be url query intolerant.
		{
			element:"a[href*='tinyurl.com'],\
					 a[href*='ptwc.weather.gov'],\
					 a[href*='www.atmos.washington.edu'],\
					 .track.outbound.skip_campaign",
			options:{
				skip_internal:true,
				mode:"event,_link",
				category:"outbound",
				skip_campaign:true,
				overwrites:true
			}
		},

		{
			element:".youtube,.youtube2",
			options:{
				action:"youtube",
				category:"videos",
				label:function(ele){
					return ( ($(ele).attr('title')!='' && typeof($(ele).attr('title')) !=='undefined' ) ? $(ele).attr('title') : $(ele).attr('href') );
				},
				overwrites:true
			}
		},
		{
			element:"a[href*='.jpg'], a[href*='.zip'], a[href*='.tiff'], a[href*='.tif'],\
					 a[href*='.bin'], a[href*='.Bin'], a[href*='.eps'], a[href*='.gif'],\
					 a[href*='.png'], a[href*='.ppt'], a[href*='.pdf'], a[href*='.doc'],\
					 a[href*='.docx'],\
					 .track.jpg, .track.zip, .track.tiff, .track.tif,\
					 .track.bin, .track.Bin, .track.eps, .track.gif,\
					 .track.png, .track.ppt, .track.pdf, .track.doc,\
					 .track.docx\
					",
			options:{
				action:function(ele){
					var href_parts =$(ele).attr('herf').split('.');
					return href_parts[href_parts.length-1];
				},
				category:"download",
				label:function(ele){
					return ( ($(ele).attr('title')!='' && typeof($(ele).attr('title')) !=='undefined' ) ? $(ele).attr('title') : $(ele).attr('href') );
				},
				overwrites:true
			}
		},
		//this should be built on which are loading in the customizer
		{
			element:"a[href*='facebook.com']",
			options:{
				category:"Social",
				action:"Facebook",
				overwrites:true
			}
		},
		{
			element:"a[href*='.rss'],.track.rss",
			options:{
				category:"Feed",
				action:"RSS",
				overwrites:true
			}
		},
		{
			element:"a[href*='mailto:'],.track.email",
			options:{
				category:"email",
				overwrites:true
			}
		},
		//this should be moved to an extension of jQuery ui events, when enqued
		{
			element:"a.modal",
			options:{
				category:"modal",
				skip_internal:true,
				mode:"event",
				overwrites:true
			}
		}
	];