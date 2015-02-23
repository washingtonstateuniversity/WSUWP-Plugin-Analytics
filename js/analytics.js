(function($, window){
	jQuery.jtrack({
		analytics:{
			ga_name:"_wsuGA",
			accounts:[{
				id:'UA-55791317-1',
				settings:{
					namedSpace:'WSUGlobal',
					cookieDomain:".wsu.edu",
					dimension:[
						{'name':'dimension1','val': window.location.protocol },//protocol
						{'name':'dimension2','val': "none" },//campus
						{'name':'dimension3','val': "none"},//college
						{'name':'dimension4','val': "none" },//unit
						{'name':'dimension5','val': "none" },//subunit
						{'name':'dimension6','val': "false" }//editor
					]
				}
			},{
				id: window.wsu_analytics.site_ga_code,
				settings:{
					namedSpace:'siteScope',
					cookieDomain:".wsu.edu",
					dimension:[
						{'name':'dimension1','val': "false" }//editor
					],
					events: site_events
				}
			}]
		}
	});
})(jQuery, window);
