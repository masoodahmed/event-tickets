<?php
/**
* Provides shortcodes for the attendee registration templatee.
* @since TBD
*/
class Tribe__Tickets__Attendee_Registration__Shortcode {
	protected $shortcode_name = 'tribe_attendee_registration';
	protected $params = array();

	public function hook() {
		// block editor has a fit if we don't bail on the admin...don't really need them in other places?
		if ( is_admin() || wp_doing_cron() || wp_doing_ajax() ) {
			return;
		}

		add_shortcode( $this->shortcode_name, [ $this, 'render' ] );
	}

	/**
	 * Renders the shortcode AR page.
	 *
	 * @since TBD
	 *
	 * @param string|array $unused_arams An array of shortcode attributes.
	 * @param string       $unused_content    The shortcode content if any.
	 *
	 * @return string
	 */
	public function render( $params = array(), $shortcode_name = 'tribe_attendee_registration' ) {
		ob_start();

		echo tribe( 'tickets.attendee_registration.view' )->display_attendee_registration_page( null, true );

		return ob_get_clean();
	}
}
