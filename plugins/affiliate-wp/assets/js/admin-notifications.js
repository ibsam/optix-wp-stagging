/**
 * Handles the in-plugin notifications inbox.
 *
 * Sets up AlpineJS store and methods for notifications.
 *
 * @since 2.9.5
 */

/**
 * Global variable with data passed from WP when localizing the script.
 *
 * @since 2.9.5
 * @global
 *
 * @type object
 */
var affwp_notifications_vars;

document.addEventListener( 'alpine:init', function() {
	/**
	 * Notifications panel handler.
	 */
	Alpine.store( 'affwpNotifications', {
		/**
		 * Checks if the panel is open.
		 *
		 * @since 2.9.5
		 *
		 * @type bool
		 */
		isPanelOpen: false,

		/**
		 * Checks if notifications are loaded.
		 *
		 * @since 2.9.5
		 *
		 * @type bool
		 */
		notificationsLoaded: false,

		/**
		 * Gets the number of active notifications.
		 *
		 * @since 2.9.5
		 *
		 * @type int
		 */
		numberActiveNotifications: 0,

		/**
		 * Active notification data.
		 *
		 * @since 2.9.5
		 *
		 * @type array
		 */
		activeNotifications: [],

		/**
		 * Inactive notification data.
		 *
		 * @since 2.9.5
		 *
		 * @type array
		 */
		inactiveNotifications: [],

		/**
		 * Whether all notifications are being dismissed.
		 *
		 * @since 2.30.0
		 *
		 * @type bool
		 */
		isDismissingAll: false,

		/**
		 * Init.
		 *
		 * Initializes the AlpineJS instance.
		 *
		 * @since 2.9.5
		 *
		 * @return void
		 */
		init() {
			const affwpNotifications = this;

			/*
			 * The bubble starts out hidden until AlpineJS is initialized. Once it is, we remove
			 * the hidden class. This prevents a flash of the bubble's visibility in the event that there
			 * are no notifications.
			 */
			const notificationCountBubble = document.querySelector(
				'#affwp-notification-button .affwp-number',
			);

			if ( notificationCountBubble ) {
				notificationCountBubble.classList.remove( 'affwp-hidden' );
			}

			document.addEventListener( 'keydown', function( e ) {
				if ( e.key !== 'Escape' ) {
					return;
				}

				affwpNotifications.closePanel();
			} );
		},

		/**
		 * Open panel.
		 *
		 * Opens the panel and gets notification data.
		 *
		 * @since 2.9.5
		 *
		 * @return void
		 */
		openPanel() {
			// Set for use in the api request.
			const affwpNotifications = this;
			const panelHeader = document.getElementById( 'affwp-notifications-header' );

			affwpNotifications.isPanelOpen = true;

			if ( affwpNotifications.notificationsLoaded && panelHeader ) {
				panelHeader.focus();
				return;
			}

			// Request notification data.
			affwpNotifications
				.apiRequest( '/notifications', 'GET' )
				.then( function( data ) {
					affwpNotifications.activeNotifications = data.active;
					affwpNotifications.numberActiveNotifications =
						affwpNotifications.activeNotifications.length;
					affwpNotifications.inactiveNotifications = data.dismissed;
					affwpNotifications.notificationsLoaded = true;

					if ( panelHeader ) {
						panelHeader.focus();
					}
				} )
				.catch( function( error ) {
					// console.log( 'Notification error', error );
				} );
		},

		/**
		 * Close panel.
		 *
		 * Closes the panel.
		 *
		 * @since 2.9.5
		 *
		 * @return void
		 */
		closePanel() {
			const affwpNotifications = this;

			if ( ! affwpNotifications.isPanelOpen ) {
				return;
			}

			affwpNotifications.isPanelOpen = false;

			const notificationButton = document.getElementById(
				'affwp-notification-button',
			);
			if ( ! notificationButton ) {
				return;
			}

			notificationButton.focus();
		},

		/**
		 * API Request.
		 *
		 * Gets the data for the notifications.
		 *
		 * @param endpoint
		 * @param method
		 * @since 2.9.5
		 *
		 * @return void
		 */
		apiRequest( endpoint, method ) {
			return fetch( affwp_notifications_vars.restBase + endpoint, {
				method,
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': affwp_notifications_vars.restNonce,
				},
			} )
				.then( function( response ) {
					if ( ! response.ok ) {
						return Promise.reject( response );
					}

					/*
					 * Returning response.text() instead of response.json() because dismissing
					 * a notification doesn't return a JSON response, so response.json() will break.
					 */
					return response.text();
				} )
				.then( function( data ) {
					return data ? JSON.parse( data ) : null;
				} );
		},

		/**
		 * Dismiss.
		 *
		 * Dismisses a notification from the inbox.
		 *
		 * @param event
		 * @param index
		 * @since 2.9.5
		 *
		 * @return void
		 */
		dismiss( event, index ) {
			// Set for use in the api request.
			const affwpNotifications = this;

			if (
				'undefined' === typeof affwpNotifications.activeNotifications[ index ]
			) {
				return;
			}

			event.target.disabled = true;

			const notification = affwpNotifications.activeNotifications[ index ];

			// Remove notification from the active list.
			affwpNotifications
				.apiRequest( '/notifications/' + notification.id, 'DELETE' )
				.then( function( response ) {
					affwpNotifications.activeNotifications.splice( index, 1 );
					affwpNotifications.numberActiveNotifications =
						affwpNotifications.activeNotifications.length;
				} )
				.catch( function( error ) {
					// console.log( 'Dismiss error', error );
				} );
		},

		/**
		 * Dismiss all active notifications at once.
		 *
		 * @param event
		 * @since 2.30.0
		 *
		 * @return void
		 */
		dismissAll( event ) {
			const affwpNotifications = this;

			if (
				affwpNotifications.isDismissingAll ||
				! affwpNotifications.activeNotifications.length
			) {
				return;
			}

			affwpNotifications.isDismissingAll = true;

			if ( event && event.target ) {
				event.target.disabled = true;
			}

			affwpNotifications
				.apiRequest( '/notifications', 'DELETE' )
				.then( function() {
					affwpNotifications.activeNotifications = [];
					affwpNotifications.numberActiveNotifications = 0;
				} )
				.catch( function( error ) {
					if ( window.console && console.error ) {
						console.error( 'AffiliateWP notifications: dismiss-all request failed', error );
					}
				} )
				.finally( function() {
					affwpNotifications.isDismissingAll = false;

					if ( event && event.target ) {
						event.target.disabled = false;
					}
				} );
		},
	} );
} );
