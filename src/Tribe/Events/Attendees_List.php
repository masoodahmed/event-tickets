<?php
/**
 * Class that detects if given post
 * is displaying the Attendee List.
 */

namespace Tribe\Tickets\Events;

use Exception;
use InvalidArgumentException;
use Tribe__Tickets__Main;
use Tribe__Tickets_Plus__Attendees_List;
use WP_Post;

/**
 * Class Attendees_List
 *
 * @since TBD
 *
 * @package Tribe\Tickets\Events
 */
class Attendees_List {

	/**
	 * @since TBD
	 * @var string Meta name to control whether the Attendee List
	 *             meta was changed by a shortcode in the content.
	 */
	protected static $attendee_list_by_shortcode = 'tribe_tickets_attendee_list_triggered_by_shortcode';

	/**
	 * Check if given event is hiding the attendees list.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post $post The Post being checked.
	 *
	 * @return bool True if event is hiding the attendees list. False otherwise.
	 *
	 * @throws InvalidArgumentException Could not determine if given event is hiding or showing the Attendee List.
	 */
	public function is_event_hiding_attendee_list( $post ) {
		// Attendees list is a Plus feature, if ET Plus is not present it will always be hidden.
		if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return true;
		}

		if ( is_numeric( $post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post instanceof WP_Post ) {
			/*
			 * There's no way to apply a sensible default in this scenario.
			 *
			 * Let's throw an exception so the client calling this function deals
			 * with it according to the >> caller << context.
			 */
			throw new InvalidArgumentException( '$post should be either a post ID or a WP_Post object.' );
		}

		return (bool) Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $post );
	}

	/**
	 * Determine whether we should hide the optout option.
	 *
	 * @since TBD
	 *
	 * @param bool $should_hide Whether the optout form should be hidden or not.
	 *
	 * @return bool Whether we should hide the optout option.
	 *
	 * @see \Tribe\Tickets\Events\Events_Service_Provider::hooks
	 */
	public function should_hide_optout( $should_hide ) {
		try {
			global $post;

			return $this->is_event_hiding_attendee_list( $post );
		} catch ( Exception $e ) {
			// Eg: global $post not a WP_Post object.
			return $should_hide;
		}
	}

	/**
	 * Determines whether this post is displaying the Attendees List.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post $post The Post being checked.
	 *
	 * @return int|bool|void Void if didn't try to update. The return of update_post_meta otherwise.
	 *
	 * @see \Tribe\Tickets\Events\Events_Service_Provider::hooks
	 */
	public function maybe_update_attendee_list_hide_meta( $post ) {
		// Attendees list is a Plus feature, if ET Plus is not present we don't have to update the meta.
		if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return null;
		}

		// Early bail: is an autosave or auto-draft.
		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return null;
		}

		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}

		// Early bail: Invalid post.
		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		/** @var Tribe__Tickets__Main $main */
		$main = tribe( 'tickets.main' );

		$post_types_allowed_to_have_tickets = $main->post_types();

		// Early bail: This post type can't have tickets.
		if ( ! in_array( $post->post_type, $post_types_allowed_to_have_tickets, true ) ) {
			return null;
		}

		$this->track_shortcode_driven_meta( $post );

		$is_events_using_blocks = tribe( 'editor' )->is_events_using_blocks();

		if ( $is_events_using_blocks ) {
			$is_showing_attendee_list = $this->is_showing_attendee_list_with_blocks( $post );
		} else {
			$is_showing_attendee_list = $this->is_showing_attendee_list_with_classical_editor( $post );
		}

		/**
		 * Returns true if the post is displaying a list of attendees.
		 *
		 * You can use this filter to let the system know that you're displaying
		 * the Attendee List in some other way.
		 *
		 * @since TBD
		 *
		 * @param bool    $is_showing_attendee_list Whether the post is showing the attendee list or not.
		 * @param WP_Post $post                     The WP_Post object being checked.
		 * @param bool    $is_events_using_blocks   Whether the post is using Blocks or not.
		 */
		$is_showing_attendee_list = (bool) apply_filters(
			'tribe_tickets_event_is_showing_attendee_list',
			$is_showing_attendee_list,
			$post,
			$is_events_using_blocks
		);

		return update_post_meta( $post->ID, Tribe__Tickets_Plus__Attendees_List::HIDE_META_KEY, (int) $is_showing_attendee_list );
	}

	/**
	 * This keeps track of whether the Attendee List is being displayed becase of a shortcode
	 * in the content, and acts accordingly if said shortcode is removed.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post The Post being checked.
	 *
	 * @return void
	 */
	private function track_shortcode_driven_meta( WP_Post $post ) {
		$is_visible_by_meta                  = Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $post ) === false;
		$has_attendee_list_shortcode         = has_shortcode( $post->post_content, 'tribe_attendees_list' );
		$has_attendee_list_by_shortcode_meta = get_post_meta( $post->ID, self::$attendee_list_by_shortcode, true ) === 'yes';

		/*
		 * If what triggers the Attendee List to display is a shortcode in the content,
		 * let's save this piece of information so that we revert it when the shortcode is removed.
		 */
		if ( ! $is_visible_by_meta && $has_attendee_list_shortcode ) {
			update_post_meta( $post->ID, self::$attendee_list_by_shortcode, 'yes' );
		}

		/*
		 * The shortcode that triggered the Attendee List to display is no longer in the content.
		 * Thus, we shall update the "Attendee List" meta to "hidden" again.
		 */
		if ( $has_attendee_list_by_shortcode_meta && ! $has_attendee_list_shortcode ) {
			update_post_meta( $post->ID, self::$attendee_list_by_shortcode, 'no' );

			add_filter( 'tribe_tickets_event_is_showing_attendee_list', '__return_false' );
		}
	}

	/**
	 * Determine whether we are showing the attendee list with the block editor.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post The Post being checked.
	 *
	 * @return bool Whether we are showing the attendee list with the block editor.
	 */
	private function is_showing_attendee_list_with_blocks( WP_Post $post ) {
		$has_attendee_list_block     = has_block( 'tribe/attendees', $post );
		$has_attendee_list_shortcode = has_shortcode( $post->post_content, 'tribe_attendees_list' );

		return $has_attendee_list_block || $has_attendee_list_shortcode;
	}

	/**
	 * Determine whether we are showing the attendee list with the block editor.
	 *
	 * @since TBD
	 *
	 * @param WP_Post $post The Post being checked.
	 *
	 * @return bool Whether we are showing the attendee list with the block editor.
	 */
	private function is_showing_attendee_list_with_classical_editor( WP_Post $post ) {
		$is_visible_by_meta          = Tribe__Tickets_Plus__Attendees_List::is_hidden_on( $post ) === false;
		$has_attendee_list_shortcode = has_shortcode( $post->post_content, 'tribe_attendees_list' );

		return $is_visible_by_meta || $has_attendee_list_shortcode;
	}
}
