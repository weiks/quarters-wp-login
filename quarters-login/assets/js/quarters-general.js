(function($){
	console.log('call');
	var ql = {

		/*
		 * Get Query string data
		 *
		*/
		getUrlVars: function(){
		    var vars = [], hash;
		    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		    for( var i = 0; i < hashes.length; i++ ) {
		        hash = hashes[i].split('=');
		        vars.push(hash[0]);
		        vars[hash[0]] = hash[1];
		    }
		    return vars;
		},

		init : function(){
			console.log( 'here' );
			ql.do_auth();
			$( ".ql-button" ).on( "click", ql.loginProcess );
		},

		loginProcess: function( e ){
			e.preventDefault();
			window.localStorage.setItem( 'quarters_authorize_action', "login" );
			window.location.replace( $( this ).attr( "href" ) );
		},

		check_auth_requirement: function(){
			//var success = ql.getUrlVars()['success'],
			var action  = window.localStorage.getItem( 'quarters_authorize_action' ), // In latest version success parameter is removed so pass it directly.
				code	= ql.getUrlVars()['code'],
				error	= ql.getUrlVars()['error'];

			if( typeof action !== 'undefined' && typeof code !== 'undefined' ){
				if( ( action != '' && action == 'login' ) && code != '' ){
					return true;
				}
			}

			if( typeof error !== 'undefined' && error != '' ){
				ql.auth_cancel();
			}

			return false;
		},

		auth_cancel: function(){
			console.log( 'auth_cancel' );
		},

		do_auth: function(){
			if( ! ql.check_auth_requirement() ){
				return false;
			}

			var success = ql.getUrlVars()['success'],
				code	= ql.getUrlVars()['code'];

			window.localStorage.setItem( 'quarters_authorize_token', code );

			var data = {
				'action' : 'ql_get_access_token',
				'code'	 : code,
			}
			console.log( 'here' );
			console.log( data );

			$.ajax({
				url: ql_data.ajax_url,
				data: data,
				dataType: 'json',
				type:'post',
				success : function( response ) {
					if( response.flag ){
						console.log( 'true' );
						ql.getuser_data( response );
					} else {
						console.log( response.data.message );
						window.location.replace( ql_data.my_account_url );
					}
				}
			});

		},

		getuser_data: function( auth_response ){
			var data = {
				'action' : 'ql_get_userdata',
				'authorization'	 : auth_response.data.access_token,
				'reauthorization'	 : auth_response.data.refresh_token,
				'expires_in'		: auth_response.data.expires_in
			}
			$.ajax({
				url: ql_data.ajax_url,
				data: data,
				dataType: 'json',
				async: false,
				type:'post',
				success : function( response ) {
					//location.reload();
					if( response.flag ){
						window.location.replace( ql_data.my_account_url );
					} else {
						window.location.replace( ql_data.my_account_url );
					}
				}
			});
		}

	};
	$( document ).ready(function(){
		ql.init();
	});
})(jQuery);