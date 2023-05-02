<?php

namespace TEC\Tickets\Tests\Emails\Email;

use TEC\Tickets\Emails\Dispatcher;
use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Dummy_Email
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Emails\Email
 */
class Dummy_Email extends Email_Abstract {
	protected static string $id = 'tec_tickets_emails_dummy';

	protected static string $slug = 'dummy';

	public $template = 'dummy';

	public $test_is_enabled = true;

	public $test_title = '%%TITLE%%';

	public $test_to = '%%TO%%';

	public $test_content = '%%CONTENT%%';

	public $test_subject = '%%SUBJECT%%';

	protected function get_default_data(): array {
		$data = [
			'enabled' => $this->test_is_enabled,
			'title' => $this->test_title,
			'to' => $this->test_to,
			'content' => $this->test_content,
			'subject' => $this->test_subject,
		];

		return array_merge( parent::get_default_data(), $data );
	}

	public function is_enabled(): bool {
		return $this->test_is_enabled;
	}

	public function get_title(): string {
		return $this->test_title;
	}

	public function get_to(): string {
		return $this->test_to;
	}

	public function get_recipient(): string {
		return $this->recipient;
	}

	public function get_subject(): string {
		return $this->test_subject;
	}

	public function get_content( $args = [] ): string {
		return $this->test_content;
	}

	public function prepare_settings(): array {
		return [];
	}

	public function get_default_preview_context( $args = [] ): array {
		return [];
	}

	public function get_default_template_context(): array {
		return [];
	}

	public function send() {
		$recipient = $this->get_recipient();

		// Bail if there is no email address to send to.
		if ( empty( $recipient ) ) {
			return false;
		}

		if ( ! $this->is_enabled() ) {
			return false;
		}

		return Dispatcher::from_email( $this )->send();
	}
}