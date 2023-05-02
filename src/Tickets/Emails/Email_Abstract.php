<?php
/**
 * Tickets Emails Email abstract class.
 *
 * @since   5.5.9
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use WP_Post;
use WP_Error;

use TEC\Tickets\Emails\Admin\Emails_Tab;
use TEC\Tickets\Emails\Admin\Settings as Emails_Settings;
use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use Tribe__Utils__Array as Arr;

/**
 * Class Email_Abstract.
 *
 * @since   5.5.9
 *
 * @package TEC\Tickets\Emails
 */
abstract class Email_Abstract {

	/**
	 * Option prefix for the Ticket Emails data stored on the database.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const OPTION_PREFIX = 'tec-tickets-emails-';

	/**
	 * Email ID.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	protected static string $id;

	/**
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	protected static string $slug;

	/**
	 * Email template filename.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Email recipient.
	 *
	 * @since 5.5.9
	 *
	 * @var string
	 */
	public $recipient;

	/**
	 * Strings to find/replace in subjects/headings.
	 *
	 * @since 5.5.9
	 *
	 * @var array
	 */
	protected $placeholders = [];

	/**
	 * Array holding all the dynamic values attached to the object.
	 *
	 * @since 5.5.10
	 *
	 * @var ?array<string, mixed> An array holding the dynamic values set to this model.
	 */
	protected ?array $data = null;

	/**
	 * Get ID.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public static function get_id(): string {
		return static::$id;
	}

	/**
	 * Get ID.
	 *
	 * @since 5.5.10
	 *
	 * @return string
	 */
	public static function get_slug(): string {
		return static::$slug;
	}

	/**
	 * Handles the hooking of a given email to the correct actions in WP.
	 *
	 * @since 5.5.9
	 */
	public function hook() {
		$default_placeholders = [
			'{site_title}'   => $this->get_blogname(),
			'{site_address}' => wp_parse_url( home_url(), PHP_URL_HOST ),
			'{site_url}'     => wp_parse_url( home_url(), PHP_URL_HOST ),
		];

		$this->set_placeholders( $default_placeholders );
	}

	/**
	 * Generates the default dataset for the Email.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	protected function get_default_data(): array {
		$data = [
			'version'    => \Tribe__Tickets__Main::VERSION,
			'from_email' => tribe_get_option( Emails_Settings::$option_sender_email, tribe( Emails_Settings::class )->get_default_sender_email() ),
			'from_name'  => tribe_get_option( Emails_Settings::$option_sender_name, tribe( Emails_Settings::class )->get_default_sender_name() ),
		];

		return array_merge( $data, $this->generate_default_data_from_settings() );
	}

	/**
	 * Prepare the settings for this Email.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	abstract protected function prepare_settings(): array;

	/**
	 * Get default preview context.
	 *
	 * @since 5.5.11
	 *
	 * @param array $args The arguments.
	 *
	 * @return array<string,mixed> The email preview context.
	 */
	abstract public function get_default_preview_context( $args = [] ): array;

	/**
	 * Get the default template context.
	 *
	 * @since 5.5.11
	 *
	 * @return array The email template context.
	 */
	abstract public function get_default_template_context(): array;

	/**
	 * Get email content.
	 *
	 * @since 5.5.10
	 *
	 * @param array $args The arguments.
	 *
	 * @return ?string The email content.
	 */
	abstract public function get_content( $args ): ?string;

	/**
	 * Set email placeholders.
	 *
	 * @since 5.5.10
	 *
	 * @param array $placeholders the placeholders to set.
	 *
	 * @return string
	 */
	public function set_placeholders( array $placeholders = [] ): array {
		$this->placeholders = array_merge(
			$placeholders,
			$this->get_placeholders()
		);

		return $this->placeholders;
	}

	/**
	 * Get email placeholders.
	 *
	 * @since 5.5.9
	 *
	 * @return array
	 */
	public function get_placeholders(): array {
		/**
		 * Filter the placeholders.
		 *
		 * @since 5.5.9
		 *
		 * @param array          $placeholders The placeholders.
		 * @param Email_Abstract $this         The email object.
		 */
		$placeholders = apply_filters( 'tec_tickets_emails_placeholders', $this->placeholders, $this );

		$email_slug = static::get_slug();

		/**
		 * Filter the placeholders for the particular email.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $placeholders The placeholders.
		 * @param Email_Abstract $this         The email object.
		 */
		$placeholders = apply_filters( "tec_tickets_emails_{$email_slug}_placeholders", $placeholders, $this );

		return $placeholders;
	}

	/**
	 * Format email string.
	 *
	 * @param mixed $string Text to replace placeholders in.
	 *
	 * @return string
	 */
	public function format_string( $string ): string {
		$placeholders = $this->get_placeholders();
		$find         = array_keys( $placeholders );
		$replace      = array_values( $placeholders );

		/**
		 * Filter the formatted email string.
		 *
		 * @since 5.5.9
		 *
		 * @param string         $string The formatted string.
		 * @param string         $id     The email id.
		 * @param Email_Abstract $this   The email object.
		 */
		return apply_filters( 'tec_tickets_emails_format_string', str_replace( $find, $replace, $string ), $this );
	}

	/**
	 * Get WordPress blog name.
	 *
	 * @todo  This doesnt belong on the abstracts, it's more like a template helper.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_blogname(): string {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Get edit URL.
	 *
	 * @since 5.5.9
	 *
	 * @return string
	 */
	public function get_edit_url(): string {
		// Force the `emails` tab.
		$args = [
			'tab'     => Emails_Tab::$slug,
			'section' => static::get_id(),
		];

		// Use the settings page get_url to build the URL.
		return tribe( Plugin_Settings::class )->get_url( $args );
	}

	/**
	 * Checks if this email is enabled.
	 *
	 * @since 5.5.10
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return tribe_is_truthy( $this->get( 'enabled' ) );
	}

	/**
	 * Get and filter email settings.
	 *
	 * @since 5.5.10
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$settings = $this->prepare_settings();

		/**
		 * Allow filtering the settings globally.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $settings The settings array.
		 * @param Email_Abstract $this     The email object.
		 */
		$settings = apply_filters( 'tec_tickets_emails_settings', $settings, $this );

		$email_slug = static::get_slug();

		/**
		 * Allow filtering the settings for this email.
		 *
		 * @since 5.5.10
		 *
		 * @param array          $settings The settings array.
		 * @param Email_Abstract $this     The email object.
		 */
		$settings = apply_filters( "tec_tickets_emails_{$email_slug}_settings", $settings, $this );

		/**
		 * This is present so that we can use the settings page to save.
		 * When we move the Emails to an actual Post Type Editing we should remove this foreach.
		 */
		foreach ( $settings as $key => $setting ) {
			if ( is_numeric( $key ) ) {
				continue;
			}
			$setting['name'] = sprintf( 'tec-tickets-emails[%s]', $key );

			$settings[ $key ] = $setting;
		}

		return $settings;
	}

	/**
	 * Get template context for email.
	 *
	 * @since 5.5.11
	 *
	 * @param array $args The arguments.
	 *
	 * @return array $args The modified arguments
	 */
	public function get_template_context( $args = [] ): array {
		$defaults = $this->get_default_template_context();

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Allow filtering the template context globally.
		 *
		 * @since 5.5.11
		 *
		 * @param array          $args     The email arguments.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$args = apply_filters( 'tec_tickets_emails_template_args', $args, $this->template, $this );

		$email_slug = static::get_slug();

		/**
		 * Allow filtering the template context.
		 *
		 * @since 5.5.11
		 *
		 * @param array          $args     The email arguments.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$args = apply_filters( "tec_tickets_emails_{$email_slug}_template_args", $args, $this->template, $this );

		return $args;
	}

	/**
	 * Get template preview context for email.
	 *
	 * @since 5.5.11
	 *
	 * @param array $args The arguments.
	 *
	 * @return array $args The modified arguments
	 */
	public function get_preview_context( $args = [] ): array {
		$defaults = $this->get_default_preview_context();

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Allow filtering the template preview context globally.
		 *
		 * @since 5.5.11
		 *
		 * @param array          $args     The email preview arguments.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$args = apply_filters( 'tec_tickets_emails_preview_args', $args, $this->template, $this );

		$email_slug = static::get_slug();

		/**
		 * Allow filtering the template context.
		 *
		 * @since 5.5.11
		 *
		 * @param array          $args     The email arguments.
		 * @param string         $id       The email id.
		 * @param string         $template Template name.
		 * @param Email_Abstract $this     The email object.
		 */
		$args = apply_filters( "tec_tickets_emails_{$email_slug}_preview_args", $args, $this->template, $this );

		return $args;
	}

	/**
	 * Set a value to a dynamic property.
	 *
	 * @since TBD
	 *
	 * @param string|array $name  The name of the property.
	 * @param mixed        $value The value of the property.
	 */
	public function set( $name, $value ) {
		$this->data = Arr::set( $this->get_data(), $name, $value );
	}

	/**
	 * Getter to access dynamic properties.
	 *
	 * @since 5.5.10
	 *
	 * @param string|array $name The name of the property.
	 *
	 * @return mixed|null The value of the passed property. Null if the value does not exist.
	 */
	public function get( $name, $default = null ) {
		return Arr::get( $this->get_data(), $name, $default );
	}

	/**
	 * Get setting option key.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function get_option_key(): string {
		return static::OPTION_PREFIX . static::get_slug();
	}

	protected function get_db_data(): array {
		$db_data = get_option( $this->get_option_key(), [] );
		if ( ! is_array( $db_data ) ) {
			$db_data = [];
		}
		return $db_data;
	}

	protected function generate_default_data_from_settings(): array {
		$fields = $this->get_settings();
		$fields = array_filter( $fields, 'is_string', ARRAY_FILTER_USE_KEY );

		$data = [];
		foreach ( $fields as $key => $field ) {
			$data[ $key ] = $field['default'] ?? null;
		}

		return $data;
	}

	public function get_data_allowed_save_keys(): array {
		$fields = $this->get_settings();
		$fields = array_filter( $fields, 'is_string', ARRAY_FILTER_USE_KEY );
		$allowed_keys = array_keys( $fields );
		$allowed_keys[] = 'version';

		return $allowed_keys;
	}

	public function get_data(): array {
		if ( null === $this->data ) {
			$this->data = array_merge( $this->get_default_data(), $this->get_db_data() );
		}

		return $this->data;
	}

	public function save_data(): ?bool {
		$allowed_keys = $this->get_data_allowed_save_keys();
		$data = array_filter( $this->get_data(), static function( $key ) use ( $allowed_keys ) {
			return in_array( $key, $allowed_keys, true );
		}, ARRAY_FILTER_USE_KEY );

		return update_option( $this->get_option_key(), $data, true );
	}
}
