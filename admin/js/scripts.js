( function( $ ) {

	'use strict';

	if ( typeof wfp === 'undefined' || wfp === null ) {
		return;
	}

	$( function() {
		var welcomePanel = $( '#wfp-welcome-panel' );
		var updateWelcomePanel;

		updateWelcomePanel = function( visible ) {
			$.post( ajaxurl, {
				action: 'wfp-update-welcome-panel',
				visible: visible,
				welcomepanelnonce: $( '#welcomepanelnonce' ).val()
			} );
		};

		$( 'a.welcome-panel-close', welcomePanel ).click( function( event ) {
			event.preventDefault();
			welcomePanel.addClass( 'hidden' );
			updateWelcomePanel( 0 );
			$( '#wfp-welcome-panel-show' ).prop( 'checked', false );
		} );

		$( '#wfp-welcome-panel-show' ).click( function( event ) {
			if ( this.checked ) {
				welcomePanel.removeClass( 'hidden' );
				updateWelcomePanel( 1 );
			} else {
				welcomePanel.addClass( 'hidden' );
				updateWelcomePanel( 0 );
			}
		} );

		$( '#wing-form-editor' ).tabs( {
			active: wfp.activeTab,
			activate: function( event, ui ) {
				$( '#active-tab' ).val( ui.newTab.index() );
			}
		} );

		$( '#wing-form-editor-tabs' ).focusin( function( event ) {
			$( '#wing-form-editor .keyboard-interaction' ).css(
				'visibility', 'visible' );
		} ).focusout( function( event ) {
			$( '#wing-form-editor .keyboard-interaction' ).css(
				'visibility', 'hidden' );
		} );

		wfp.toggleMail2( 'input:checkbox.toggle-form-table' );

		$( 'input:checkbox.toggle-form-table' ).click( function( event ) {
			wfp.toggleMail2( this );
		} );

		if ( '' === $( '#title' ).val() ) {
			$( '#title' ).focus();
		}

		wfp.titleHint();

		$( '.wing-form-editor-box-mail span.mailtag' ).click( function( event ) {
			var range = document.createRange();
			range.selectNodeContents( this );
			window.getSelection().addRange( range );
		} );

		wfp.updateConfigErrors();

		$( '[data-config-field]' ).change( function() {
			var postId = $( '#post_ID' ).val();

			if ( ! postId || -1 == postId ) {
				return;
			}

			var data = [];

			$( this ).closest( 'form' ).find( '[data-config-field]' ).each( function() {
				data.push( {
					'name': $( this ).attr( 'name' ).replace( /^wfp-/, '' ).replace( /-/g, '_' ),
					'value': $( this ).val()
				} );
			} );

			data.push( { 'name': 'context', 'value': 'dry-run' } );

			$.ajax( {
				method: 'POST',
				url: wfp.apiSettings.getRoute( '/wing-forms/' + postId ),
				beforeSend: function( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', wfp.apiSettings.nonce );
				},
				data: data
			} ).done( function( response ) {
				wfp.configValidator.errors = response.config_errors;
				wfp.updateConfigErrors();
			} );
		} );

		$( window ).on( 'beforeunload', function( event ) {
			var changed = false;

			$( '#wfp-admin-form-element :input[type!="hidden"]' ).each( function() {
				if ( $( this ).is( ':checkbox, :radio' ) ) {
					if ( this.defaultChecked != $( this ).is( ':checked' ) ) {
						changed = true;
					}
				} else if ( $( this ).is( 'select' ) ) {
					$( this ).find( 'option' ).each( function() {
						if ( this.defaultSelected != $( this ).is( ':selected' ) ) {
							changed = true;
						}
					} );
				} else {
					if ( this.defaultValue != $( this ).val() ) {
						changed = true;
					}
				}
			} );

			if ( changed ) {
				event.returnValue = wfp.saveAlert;
				return wfp.saveAlert;
			}
		} );

		$( '#wfp-admin-form-element' ).submit( function() {
			if ( 'copy' != this.action.value ) {
				$( window ).off( 'beforeunload' );
			}

			if ( 'save' == this.action.value ) {
				$( '#publishing-action .spinner' ).addClass( 'is-active' );
			}
		} );

		$( '#wfp-ctct-enable-wing-list, #wfp-sendinblue-enable-wing-list, #wfp-sendinblue-enable-transactional-email' ).on( 'change', function() {
			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( 'tr' ).removeClass( 'inactive' );
			} else {
				$( this ).closest( 'tr' ).addClass( 'inactive' );
			}
		} );

	} );

	wfp.toggleMail2 = function( checkbox ) {
		var $checkbox = $( checkbox );
		var $fieldset = $( 'fieldset',
			$checkbox.closest( '.wing-form-editor-box-mail' ) );

		if ( $checkbox.is( ':checked' ) ) {
			$fieldset.removeClass( 'hidden' );
		} else {
			$fieldset.addClass( 'hidden' );
		}
	};

	wfp.updateConfigErrors = function() {
		var errors = wfp.configValidator.errors;
		var errorCount = { total: 0 };

		$( '[data-config-field]' ).each( function() {
			$( this ).removeAttr( 'aria-invalid' );
			$( this ).next( 'ul.config-error' ).remove();

			var section = $( this ).attr( 'data-config-field' );

			$( this ).attr( 'aria-describedby', 'wfp-config-error-for-' + section );

			if ( errors[ section ] ) {
				var $list = $( '<ul></ul>' ).attr( {
					'id': 'wfp-config-error-for-' + section,
					'class': 'config-error'
				} );

				$.each( errors[ section ], function( i, val ) {
					var $li = $( '<li></li>' ).append(
						wfp.iconInCircle( '!' )
					).append(
						$( '<span class="screen-reader-text"></span>' ).text( wfp.configValidator.iconAlt )
					).append( ' ' );

					if ( val.link ) {
						$li.append(
							$( '<a></a>' ).attr( 'href', val.link ).text( val.message )
						);
					} else {
						$li.text( val.message );
					}

					$li.appendTo( $list );

					var tab = section
						.replace( /^mail_\d+\./, 'mail.' ).replace( /\..*$/, '' );

					if ( ! errorCount[ tab ] ) {
						errorCount[ tab ] = 0;
					}

					errorCount[ tab ] += 1;

					errorCount.total += 1;
				} );

				$( this ).after( $list ).attr( { 'aria-invalid': 'true' } );
			}
		} );

		$( '#wing-form-editor-tabs > li' ).each( function() {
			var $item = $( this );
			$item.find( '.icon-in-circle' ).remove();
			var tab = $item.attr( 'id' ).replace( /-panel-tab$/, '' );

			$.each( errors, function( key, val ) {
				key = key.replace( /^mail_\d+\./, 'mail.' );

				if ( key.replace( /\..*$/, '' ) == tab.replace( '-', '_' ) ) {
					var $mark = wfp.iconInCircle( '!' );
					$item.find( 'a.ui-tabs-anchor' ).first().append( $mark );
					return false;
				}
			} );

			var $tabPanelError = $( '#' + tab + '-panel > div.config-error:first' );
			$tabPanelError.empty();

			if ( errorCount[ tab.replace( '-', '_' ) ] ) {
				$tabPanelError.append( wfp.iconInCircle( '!' ) );

				if ( 1 < errorCount[ tab.replace( '-', '_' ) ] ) {
					var manyErrorsInTab = wfp.configValidator.manyErrorsInTab
						.replace( '%d', errorCount[ tab.replace( '-', '_' ) ] );
					$tabPanelError.append( manyErrorsInTab );
				} else {
					$tabPanelError.append( wfp.configValidator.oneErrorInTab );
				}
			}
		} );

		$( '#misc-publishing-actions .misc-pub-section.config-error' ).remove();

		if ( errorCount.total ) {
			var $warning = $( '<div></div>' )
				.addClass( 'misc-pub-section config-error' )
				.append( wfp.iconInCircle( '!' ) );

			if ( 1 < errorCount.total ) {
				$warning.append(
					wfp.configValidator.manyErrors.replace( '%d', errorCount.total )
				);
			} else {
				$warning.append( wfp.configValidator.oneError );
			}

			$warning.append( '<br />' ).append(
				$( '<a></a>' )
					.attr( 'href', wfp.configValidator.docUrl )
					.text( wfp.configValidator.howToCorrect )
			);

			$( '#misc-publishing-actions' ).append( $warning );
		}
	};

	/**
	 * Copied from wptitlehint() in wp-admin/js/post.js
	 */
	wfp.titleHint = function() {
		var $title = $( '#title' );
		var $titleprompt = $( '#title-prompt-text' );

		if ( '' === $title.val() ) {
			$titleprompt.removeClass( 'screen-reader-text' );
		}

		$titleprompt.click( function() {
			$( this ).addClass( 'screen-reader-text' );
			$title.focus();
		} );

		$title.blur( function() {
			if ( '' === $(this).val() ) {
				$titleprompt.removeClass( 'screen-reader-text' );
			}
		} ).focus( function() {
			$titleprompt.addClass( 'screen-reader-text' );
		} ).keydown( function( e ) {
			$titleprompt.addClass( 'screen-reader-text' );
			$( this ).unbind( e );
		} );
	};

	wfp.iconInCircle = function( icon ) {
		var $span = $( '<span class="icon-in-circle" aria-hidden="true"></span>' );
		return $span.text( icon );
	};

	wfp.apiSettings.getRoute = function( path ) {
		var url = wfp.apiSettings.root;

		url = url.replace(
			wfp.apiSettings.namespace,
			wfp.apiSettings.namespace + path );

		return url;
	};

} )( jQuery );
