<?php
/**
 * Class RSVP
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Email;

use TEC\Tickets\Commerce\Settings as Settings;
use TEC\Tickets\Emails\Dispatcher;
use \TEC\Tickets\Emails\Email_Template;
use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class RSVP
 *
 * @since   5.5.10
 *
 * @package TEC\Tickets\Emails
 */
class RSVP extends Email_Abstract {

	/**
	 * Email ID.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	protected static string $id = 'tec_tickets_emails_rsvp';

	/**
	 * Email slug.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	protected static string $slug = 'rsvp';

	/**
	 * Email template.
	 *
	 * @since 5.5.10
	 *
	 * @var string
	 */
	public $template = 'rsvp';

	/**
	 * @inheritDoc
	 */
	public function get_default_data(): array {
		$data = [
			'to'    => esc_html__( 'RSVP Email', 'event-tickets' ),
			'title' => esc_html__( 'Attendee(s)', 'event-tickets' ),
		];

		return array_merge( parent::get_default_data(), $data );
	}

	/**
	 * @inheritDoc
	 */
	public function prepare_settings(): array {
		$settings = [
			[
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			[
				'type' => 'html',
				'html' => '<h2>' . esc_html__( 'RSVP Email Settings', 'event-tickets' ) . '</h2>',
			],
			[
				'type' => 'html',
				'html' => '<p>' . esc_html__( 'Registrants will receive an email including their RSVP info upon registration. Customize the content of this specific email using the tools below. The brackets {event_name}, {event_date}, and {rsvp_name} can be used to pull dynamic content from the RSVP into your email. Learn more about customizing email templates in our Knowledgebase.' ) . '</p>',
			],
			'enabled'          => [
				'type'            => 'toggle',
				'label'           => esc_html__( 'Enabled', 'event-tickets' ),
				'default'         => true,
				'validation_type' => 'boolean',
			],
			'use-ticket-email' => [
				'type'            => 'toggle',
				'label'           => esc_html__( 'Use Ticket Email', 'event-tickets' ),
				'placeholder'     => esc_html__( 'Use the ticket email settings and template.', 'event-tickets' ),
				'default'         => true,
				'validation_type' => 'boolean',
			],
		];

		// If using the ticket email settings, no need to show the remaining settings.
		if ( tribe_is_truthy( tribe_get_option( $this->get_option_key( 'use-ticket-email' ), true ) ) ) {
			return $settings;
		}

		$default_heading        = sprintf(
		// Translators: %s Lowercase singular of ticket.
			esc_html__( 'Here\'s your %s, {attendee_name}!', 'event-tickets' ),
			tribe_get_ticket_label_singular_lowercase()
		);
		$default_heading_plural = sprintf(
		// Translators: %s Lowercase plural of tickets.
			esc_html__( 'Here are your %s, {attendee_name}!', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);
		$default_subject        = tribe_get_option( Settings::$option_confirmation_email_subject, sprintf(
		// Translators: %s - Lowercase singular of ticket.
			esc_html__( 'Your %s from {site_title}', 'event-tickets' ),
			tribe_get_ticket_label_singular_lowercase()
		) );
		$default_subject_plural = sprintf(
		// Translators: %s - Lowercase plural of tickets.
			esc_html__( 'Your %s from {site_title}', 'event-tickets' ),
			tribe_get_ticket_label_plural_lowercase()
		);

		$add_settings = [
			'subject'            => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets' ),
				'default'             => $default_subject,
				'placeholder'         => $default_subject,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'subject_plural'     => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject (plural)', 'event-tickets' ),
				'default'             => $default_subject_plural,
				'placeholder'         => $default_subject_plural,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'heading'            => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets' ),
				'default'             => $default_heading,
				'placeholder'         => $default_heading,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'heading_plural'     => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading (plural)', 'event-tickets' ),
				'default'             => $default_heading_plural,
				'placeholder'         => $default_heading_plural,
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			'additional_content' => [
				'type'            => 'wysiwyg',
				'label'           => esc_html__( 'Additional content', 'event-tickets' ),
				'default'         => '',
				'tooltip'         => esc_html__( 'Additional content will be displayed below the RSVP information in your email.', 'event-tickets' ),
				'validation_type' => 'html',
				'settings'        => [
					'media_buttons' => false,
					'quicktags'     => false,
					'editor_height' => 200,
					'buttons'       => [
						'bold',
						'italic',
						'underline',
						'strikethrough',
						'alignleft',
						'aligncenter',
						'alignright',
					],
				],
			],
		];

		return array_merge( $settings, $add_settings );
	}

	/**
	 * Get default preview context for email.
	 *
	 * @since 5.5.11
	 *
	 * @param array $args The arguments.
	 *
	 * @return array $args The modified arguments
	 */
	public function get_default_preview_context( $args = [] ): array {
		$defaults = tribe( Email_Template::class )->get_preview_context( $args );

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Get default template context for email.
	 *
	 * @since 5.5.11
	 *
	 * @return array $args The default arguments
	 */
	public function get_default_template_context(): array {
		$defaults = [
			'email'              => $this,
			'title'              => $this->get( 'title' ),
			'heading'            => $this->get( 'heading' ),
			'additional_content' => $this->get( 'additional_content' ),
			'tickets'            => $this->get( 'tickets' ),
			'post_id'            => $this->get( 'post_id' ),
		];

		return $defaults;
	}

	/**
	 * Get email content.
	 *
	 * @since 5.5.10
	 *
	 * @param array $args The arguments.
	 *
	 * @return string The email content.
	 */
	public function get_content( $args = [] ): string {
		// @todo: Parse args, etc.
		$is_preview = ! empty( $args['is_preview'] ) ? tribe_is_truthy( $args['is_preview'] ) : false;
		$args       = $this->get_template_context( $args );

		$email_template = tribe( Email_Template::class );
		$email_template->set_preview( $is_preview );

		return $email_template->get_html( $this->template, $args );
	}

	/**
	 * Send the email.
	 *
	 * @since 5.5.11
	 *
	 * @return bool Whether the email was sent or not.
	 */
	public function send() {
		$recipient = $this->get( 'recipient' );

		// Bail if there is no email address to send to.
		if ( empty( $recipient ) ) {
			return false;
		}

		if ( ! $this->is_enabled() ) {
			return false;
		}

		$tickets = $this->get( 'tickets' );
		$post_id = $this->get( 'post_id' );

		// Bail if there's no tickets or post ID.
		if ( empty( $tickets ) || empty( $post_id ) ) {
			return false;
		}

		$placeholders = [
			'{attendee_name}'  => $tickets[0]['holder_name'],
			'{attendee_email}' => $tickets[0]['holder_email'],
		];

		$this->set_placeholders( $placeholders );

		return Dispatcher::from_email( $this )->send();
	}
}
