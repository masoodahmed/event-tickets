/* global tecTicketsCommerceData */
/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Configures ET Tickets Commerce Object in the Global Tribe variable
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets.commerce = {};

/**
 * Initializes in a Strict env the code that manages the plugin tickets commerce.
 *
 * @since 5.1.9
 *
 * @param  {Object} $   jQuery
 * @param  {Object} obj tribe.tickets.commerce
 *
 * @return {void}
 */
( function( $, obj ) {
	'use strict';
	const $document = $( document );

	/**
	 * Ticket Commerce custom Events.
	 *
	 * @since TBD
	 */
	obj.customEvents = {
		showLoader : 'showLoader.tecTicketsCommerce',
		hideLoader : 'hideLoader.tecTicketsCommerce',
	};

	/*
	 * Tickets Commerce Selectors.
	 *
	 * @since 5.1.9
	 */
	obj.selectors = {
		checkoutContainer: '.tribe-tickets__commerce-checkout',
		checkoutItem: '.tribe-tickets__commerce-checkout-cart-item',
		checkoutItemDescription: '.tribe-tickets__commerce-checkout-cart-item-details-description',
		checkoutItemDescriptionOpen: '.tribe-tickets__commerce-checkout-cart-item-details--open',
		checkoutItemDescriptionButtonMore: '.tribe-tickets__commerce-checkout-cart-item-details-button--more', // eslint-disable-line max-len
		checkoutItemDescriptionButtonLess: '.tribe-tickets__commerce-checkout-cart-item-details-button--less', // eslint-disable-line max-len
		hiddenElement: '.tribe-common-a11y-hidden',
		nonce: '#tec-tc-checkout-nonce',
		noticeItem: '.tribe-tickets__commerce-checkout__notice',
		noticeContent: '.tribe-tickets__commerce-checkout__notice__content',
		noticeTitle: '.tribe-tickets-notice__title',
	};

	/**
	 * Show the loader/spinner.
	 *
	 * @since TBD
	 */
	obj.loaderShow = function() {
		tribe.tickets.loader.show( $( obj.selectors.checkoutContainer ) );
	};

	/**
	 * Hide the loader/spinner.
	 *
	 * @since TBD
	 */
	obj.loaderHide = function() {
		tribe.tickets.loader.hide( $( obj.selectors.checkoutContainer ) );
	};

	/**
	 * Bind loader events.
	 *
	 * @since TBD
	 */
	obj.bindLoaderEvents = function () {
		$document.on( obj.customEvents.showLoader, obj.loaderShow );
		$document.on( obj.customEvents.hideLoader, obj.loaderHide );
	}

	/**
	 * Display the notice component.
	 *
	 * @since TBD
	 *
	 * @param {Object} data Data for notice.
	 */
	obj.showNotice = function( data = {} ) {
		const $notice = $( obj.selectors.noticeItem );
		const $content = $notice.find( obj.selectors.noticeContent );
		const $title = $notice.find( obj.selectors.noticeTitle );

		if ( ! $notice.length || ! $content.length || ! $title.length ) {
			return;
		}

		const title = data.title ? data.title : tecTicketsCommerceData.default_notice.title;
		const message = data.message ? data.message : tecTicketsCommerceData.default_notice.content;

		$title.text( title );
		$content.text( message );
		$notice.show();
	}

	/**
	 * Hide the notice component.
	 *
	 * @since TBD
	 */
	obj.hideNotice = function() {
		const $notice = $( obj.selectors.noticeItem );

		$notice.hide();
	}

	/**
	 * Toggle the checkout item description visibility.
	 *
	 * @since 5.1.9
	 *
	 * @param {event} event The event.
	 *
	 * @return {void}
	 */
	obj.checkoutItemDescriptionToggle = function( event ) {
		if ( 'keydown' === event.type && 13 !== event.keyCode ) {
			return;
		}

		const trigger = event.currentTarget;

		if ( ! trigger ) {
			return;
		}

		const $trigger = $( trigger );

		if (
			! $trigger.hasClass( obj.selectors.checkoutItemDescriptionButtonMore.className() ) &&
			! $trigger.hasClass( obj.selectors.checkoutItemDescriptionButtonLess.className() )
		) {
			return;
		}

		const $parent = $trigger.closest( obj.selectors.checkoutItem );
		const $target = $( '#' + $trigger.attr( 'aria-controls' ) );

		if ( ! $target.length || ! $parent.length ) {
			return;
		}

		// Let our CSS handle the hide/show. Also allows us to make it responsive.
		const onOff = ! $parent.hasClass( obj.selectors.checkoutItemDescriptionOpen.className() );
		$parent.toggleClass( obj.selectors.checkoutItemDescriptionOpen.className(), onOff );
		$target.toggleClass( obj.selectors.checkoutItemDescriptionOpen.className(), onOff );
		$target.toggleClass( obj.selectors.hiddenElement.className() );
	};

	/**
	 * Binds the checkout item description toggle.
	 *
	 * @since 5.1.9
	 *
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.bindCheckoutItemDescriptionToggle = function( $container ) {
		const $descriptionToggleButtons = $container.find( obj.selectors.checkoutItemDescriptionButtonMore + ', ' + obj.selectors.checkoutItemDescriptionButtonLess ); // eslint-disable-line max-len

		$descriptionToggleButtons
			.on( 'keydown', obj.checkoutItemDescriptionToggle )
			.on( 'click', obj.checkoutItemDescriptionToggle );
	};

	/**
	 * Unbinds the description toggle.
	 *
	 * @since 5.1.9
	 *
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.unbindCheckoutItemDescriptionToggle = function( $container ) {
		const $descriptionToggleButtons = $container.find( obj.selectors.checkoutItemDescriptionButtonMore + ', ' + obj.selectors.checkoutItemDescriptionButtonLess ); // eslint-disable-line max-len

		$descriptionToggleButtons.off();
	};

	/**
	 * Binds events for checkout container.
	 *
	 * @since 5.1.9
	 *
	 * @param {jQuery} $container jQuery object of object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.bindCheckoutEvents = function( $container ) {
		$document.trigger( 'beforeSetup.tecTicketsCommerce', [ $container ] );

		// Bind container based events.
		obj.bindCheckoutItemDescriptionToggle( $container );

		// Bind loader visibility.
		obj.bindLoaderEvents();

		$document.trigger( 'afterSetup.tecTicketsCommerce', [ $container ] );
	};

	/**
	 * Handles the initialization of the tickets commerce events when Document is ready.
	 *
	 * @since 5.1.9
	 *
	 * @return {void}
	 */
	obj.ready = function() {
		const $checkoutContainer = $document.find( obj.selectors.checkoutContainer );
		// Bind events for each tickets commerce checkout block.
		$checkoutContainer.each( function( index, block ) {
			obj.bindCheckoutEvents( $( block ) );
		} );
	};

	$( obj.ready );

} )( jQuery, tribe.tickets.commerce );
