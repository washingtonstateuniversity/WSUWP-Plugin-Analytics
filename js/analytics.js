(function($, window){
	/* start with the defaults for WSU sites */
	var rendered_accounts = [{
			id:'UA-55791317-1',
			settings:{
				namedSpace:'WSUGlobal',
				cookieDomain:".wsu.edu",
				dimension:[
					{'name':'dimension1','val': window.location.protocol },//protocol <string> (http: / https:)
					{'name':'dimension2','val': window.wsu_analytics.global.campus },//campus <string>
					{'name':'dimension3','val': window.wsu_analytics.global.college },//college <string>
					{'name':'dimension4','val': window.wsu_analytics.global.unit },//unit <string>
					{'name':'dimension5','val': window.wsu_analytics.global.subunit },//subunit <string>
					{'name':'dimension6','val': window.wsu_analytics.app.is_authenticated }//editor <bool>
				],
				events: window.wsu_analytics.global.events
			}
		},{
			id: 'UA-52133513-1',
			settings:{
				namedSpace:'appScope',
				cookieDomain:".wsu.edu",
				dimension:[
					{'name':'dimension1','val': window.wsu_analytics.app.page_view_type },//page_view_type <string>
					{'name':'dimension2','val': window.wsu_analytics.app.authenticated_user },//authenticated_user <string>
				],
				events: window.wsu_analytics.app.events
			}
		}];
	/* add the "built" account object to the array of accounts for GA */
	if(window.wsu_analytics.site.ga_code!==false){
		rendered_accounts.push({
			id: window.wsu_analytics.site.ga_code,
			settings:{
				namedSpace:'siteScope',
				cookieDomain:".wsu.edu",
				dimension:[
					{'name':'dimension1','val': window.wsu_analytics.app.is_authenticated }//editor <bool>
				],
				events: window.wsu_analytics.site.events
			}
		});
	}
	console.log(rendered_accounts);
	/* just so we are not tracking at this time
	jQuery.jtrack({
		analytics:{
			ga_name:"_wsuGA",
			accounts: rendered_accounts
		}
	});*/
})(jQuery, window);
