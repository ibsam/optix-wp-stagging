; (function ($) {

	PPOffcanvasContent = function (settings) {
		this.id 				= settings.id;
		this.node 				= $('.fl-node-' + this.id);
		this.wrap 				= this.node.find('.pp-offcanvas-content-wrap');
		this.content 			= this.node.find('.pp-offcanvas-content');
		this.button 			= this.node.find('.pp-offcanvas-toggle');
		this.direction			= settings.direction,
		this.contentTransition	= settings.contentTransition,
		this.closeButton		= settings.closeButton,
		this.escClose			= settings.escClose,
		this.closeButton		= settings.closeButton,
		this.bodyClickClose		= settings.bodyClickClose,
		this.toggleSource		= settings.toggleSource,
		this.toggle_class		= settings.toggle_class,
		this.toggle_id			= settings.toggle_id,
		this.duration			= 500,
		this.isBuilderActive 	= settings.isBuilderActive,
		this._active = false;
		this._previous = false;

		this._destroy();
		this._init();
	};

	PPOffcanvasContent.prototype = {
		animations: [
			'slide',
			'slide-along',
			'reveal',
			'push',
		],

		_active: false,
		_previous: false,

		_init: function () {
			if (!this.wrap.length) {
				return;
			}

			if ( this.isBuilderActive ) {
				return;
			}

			$('html').addClass('pp-offcanvas-content-widget');

			if ($('.pp-offcanvas-container').length === 0) {
				$('body').wrapInner('<div class="pp-offcanvas-container" />');
				this.content.insertBefore('.pp-offcanvas-container');
			}

			if (this.wrap.find('.pp-offcanvas-content').length > 0) {
				if ($('.pp-offcanvas-container > .pp-offcanvas-content-' + this.id).length > 0) {
					$('.pp-offcanvas-container > .pp-offcanvas-content-' + this.id).remove();
				}
				if ($('body > .pp-offcanvas-content-' + this.id).length > 0) {
					$('body > .pp-offcanvas-content-' + this.id).remove();
				}
				$('body').prepend(this.wrap.find('.pp-offcanvas-content'));
			}

			this._bindEvents();
		},

		_destroy: function () {
			this._close();

			this.animations.forEach(function (animation) {
				if ($('html').hasClass('pp-offcanvas-content-' + animation)) {
					$('html').removeClass('pp-offcanvas-content-' + animation)
				}
			});
		},
		
		_setTrigger: function () {
			var $trigger = false;

			if (this.toggleSource == 'id' && this.toggle_id != '') {
				var toggleId = this.toggle_id.replace('#', '');
				$trigger = $('#' + toggleId);
			} else if (this.toggleSource == 'class' && this.toggle_class != '') {
				var toggleClass = this.toggle_class.replace('#', '');
				$trigger = $('.' + toggleClass);
			} else {
				$trigger = this.node.find('.pp-offcanvas-toggle');
			}

			return $trigger;
		},

		_bindEvents: function () {
			var self = this;
			var $trigger = this._setTrigger();
			var scrollPos = $(window).scrollTop();

			if ($trigger) {
				$trigger.on('click', $.proxy(this._toggleContent, this));
			}

			this._onHashChange();

			$(window).on('hashchange', function(e) {
				e.preventDefault();
				window['pp_offcanvas_' + self.id]._onHashChange();
			});

			$('body').delegate('.pp-offcanvas-content .pp-offcanvas-close', 'click', $.proxy(this._close, this));
			$('body').delegate('.pp-offcanvas-' + this.id + '-close', 'click', function(e) {
				e.preventDefault();
				window['pp_offcanvas_' + self.id]._close();
			});

			// Close the off-canvas panel on clicking on inner links start with hash.
			$('body').delegate('.pp-offcanvas-content .pp-offcanvas-body a[href*="#"]:not([href="#"])', 'click', $.proxy(this._close, this));

			$('body').delegate( 'a[href*="#"]:not([href="#"])', 'click', function(e) {
				var hash = '#' + $(this).attr('href').split('#')[1];

				if ( $(hash).length > 0 && $(hash).hasClass( 'fl-node-' + self.id ) ) {
					if ( ! $('html').hasClass('pp-offcanvas-content-open') ) {
						self._show();
					}
				}
			} );

			if (this.escClose === 'yes') {
				this._closeESC();
			}
			if (this.bodyClickClose === 'yes') {
				this._closeClick();
			}
		},

		_onHashChange: function() {
			var hash = location.hash;
			var self = this;

			if ( $(hash).length > 0 && $(hash).hasClass( 'fl-node-' + this.id ) ) {
				setTimeout(function() {
					if ( ! $('html').hasClass('pp-offcanvas-content-open') ) {
						self._show();
					}
				}, 500);
			}
		},

		_toggleContent: function (e) {
			e.preventDefault();

			if (!$('html').hasClass('pp-offcanvas-content-open')) {
				this._show();
			} else {
				this._close();
			}
		},

		_show: function () {
			this._previous = this._active;

			$('.pp-offcanvas-content-' + this.id).addClass('pp-offcanvas-content-visible');
			// init animation class.
			$('html').addClass('pp-offcanvas-content-' + this.contentTransition);
			$('html').addClass('pp-offcanvas-content-' + this.direction);
			$('html').addClass('pp-offcanvas-content-open');
			$('html').addClass('pp-offcanvas-content-' + this.id + '-open');
			$('html').addClass('pp-offcanvas-content-reset');

			this.button.addClass('pp-is-active');

			this._active = {
				id: this.id,
				contentTransition: this.contentTransition,
				direction: this.direction,
				$button: this.button
			};
		},

		_close: function () {
			var hash = location.hash;

			$('html').removeClass('pp-offcanvas-content-open');
			$('html').removeClass('pp-offcanvas-content-' + this.id + '-open');
			setTimeout($.proxy(function () {
				$('html').removeClass('pp-offcanvas-content-reset');
				$('html').removeClass('pp-offcanvas-content-' + this.contentTransition);
				$('html').removeClass('pp-offcanvas-content-' + this.direction);
				$('.pp-offcanvas-content-' + this.id).removeClass('pp-offcanvas-content-visible');

				if ( $(hash).length > 0 && $(hash).hasClass( 'fl-node-' + this.id ) ) {
					if ( ! $('html').hasClass('pp-offcanvas-content-open') ) {
						var scrollPos = $(window).scrollTop();
						location.href = location.href.split('#')[0] + '#';
						window.scrollTo(0, scrollPos);
					}
				}
			}, this), 500);

			this.button.removeClass('pp-is-active');
			this._active = false;
		},

		_closeESC: function () {
			var self = this;

			if ('no' === self.escClose) {
				return;
			}

			// menu close on ESC key
			$(document).on('keydown', function (e) {
				if (e.keyCode === 27) { // ESC
					self._close();
				}
			});
		},

		_closeClick: function () {
			var self = this;

			if (this.toggleSource == 'id' && this.toggle_id != '') {
				$trigger = '#' + this.toggle_id;
			} else if (this.toggleSource == 'class' && this.toggle_class != '') {
				$trigger = '.' + this.toggle_class;
			} else {
				$trigger = '.pp-offcanvas-toggle';
			}

			$(document).on('click', function (e) {
				if ( $(e.target).is('.pp-offcanvas-content') || 
					$(e.target).parents('.pp-offcanvas-content').length > 0 || 
					$(e.target).is('.pp-offcanvas-toggle') || 
					$(e.target).parents('.pp-offcanvas-toggle').length > 0 || 
					$(e.target).is($trigger) || 
					$(e.target).parents($trigger).length > 0 || 
					! $(e.target).is('.pp-offcanvas-container') ) {
					return;
				} else {
					self._close();
				}
			});
		}
	};
}) (jQuery);
