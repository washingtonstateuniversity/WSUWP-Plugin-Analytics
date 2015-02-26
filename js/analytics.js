(function($, window, analytics){
	/* start with the defaults for WSU sites */
	var rendered_accounts = [];
	
	/* should the wsu global be used? */
	if(analytics.app.page_view_type==="Front End" || analytics.app.page_view_type==="unknown"){
		rendered_accounts = jQuery.merge( rendered_accounts , [{
			id:analytics.wsuglobal.ga_code,
			settings:{
				namedSpace:'WSUGlobal',
				cookieDomain:".wsu.edu",
				dimension:[
					{'name':'dimension1','val': window.location.protocol },//protocol <string> (http: / https:)
					{'name':'dimension2','val': analytics.wsuglobal.campus },//campus <string>
					{'name':'dimension3','val': analytics.wsuglobal.college },//college <string>
					{'name':'dimension4','val': analytics.wsuglobal.unit },//unit <string>
					{'name':'dimension5','val': analytics.wsuglobal.subunit },//subunit <string>
					{'name':'dimension6','val': ""+analytics.app.is_editor },//editor <bool>(as string)
					{'name':'dimension7','val': window.location.hostname }//base site url <string>(as string)
				],
				events: analytics.wsuglobal.events
			}
		}] );
	}

	/* Set up the app level for the GA schema */
	if(analytics.app.ga_code!==false){
		rendered_accounts = jQuery.merge( rendered_accounts , [{
			id: analytics.app.ga_code,
			settings:{
				namedSpace:'appScope',
				cookieDomain:".wsu.edu",
				dimension:[
					{'name':'dimension1','val': analytics.app.page_view_type },//page_view_type <string>
					{'name':'dimension2','val': analytics.app.authenticated_user },//authenticated_user <string>
				],
				events: analytics.app.events
			}
		}] );
	}

	/* if on the front end send site level data */
	if(analytics.app.page_view_type==="Front End" || analytics.app.page_view_type==="unknown"){
		if(analytics.site.ga_code!==false){
			rendered_accounts = jQuery.merge( rendered_accounts , [{
				id: analytics.site.ga_code,
				settings:{
					namedSpace:'siteScope',
					cookieDomain:".wsu.edu",
					dimension:[
						{'name':'dimension1','val': ""+analytics.app.is_editor }//editor <bool>(as string)
					],
					events: analytics.site.events
				}
			}] );
		}
	}
	//console.log(analytics.app.page_view_type);
	//console.log(rendered_accounts);
	/* just so we are not tracking at this time*/
	jQuery.jtrack({
		analytics:{
			ga_name:"_wsuGA",
			accounts: rendered_accounts
		}
	});
})(jQuery, window, window.wsu_analytics);
