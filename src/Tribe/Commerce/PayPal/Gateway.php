<?php

class Tribe__Tickets__Commerce__PayPal__Gateway {

	/**
	 * @var string
	 */
	public $base_url = 'https://www.paypal.com';
	/**
	 * @var string
	 */
	public $cart_url = 'https://www.paypal.com/cgi-bin/webscr';

	/**
	 * @var string
	 */
	public $sandbox_base_url = 'https://www.sandbox.paypal.com';

	/**
	 * @var string
	 */
	public $sandbox_cart_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

	/**
	 * @var string
	 */
	public $identity_token;

	/**
	 * @var array
	 */
	protected $transaction_data;

	/**
	 * @var string
	 */
	public static $invoice_cookie_name = 'event-tickets-tpp-invoice';

	/**
	 * @var Tribe__Tickets__Commerce__Paypal__Notices
	 */
	protected $notices;

	/**
	 * @var \Tribe__Tickets__Commerce__PayPal__Handler__Interface
	 */
	protected $handler;

	/**
	 * Tribe__Tickets__Commerce__PayPal__Gateway constructor.
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Commerce__PayPal__Notices $notices
	 */
	public function __construct( Tribe__Tickets__Commerce__Paypal__Notices $notices ) {
		$this->identity_token = tribe_get_option( 'ticket-paypal-identity-token' );
		$this->notices = $notices;
	}

	/**
	 * Set up hooks for the gateway
	 *
	 * @since TBD
	 */
	public function hook() {
		add_action( 'template_redirect', array( $this, 'add_to_cart' ) );
	}

	/**
	 * Handles adding tickets to cart
	 *
	 * @since TBD
	 */
	public function add_to_cart() {
		global $post;

		// bail if this isn't a Tribe Commerce PayPal ticket
		if (
			empty( $_POST['product_id'] )
			|| empty( $_POST['provider'] )
			|| 'Tribe__Tickets__Commerce__PayPal__Main' !== $_POST['provider']
		) {
			return;
		}

		$url           = $this->get_cart_url( '_cart' );
		$now           = time();
		$post_url      = get_permalink( $post );
		$currency_code = tribe_get_option( 'ticket-paypal-currency-code' );
		$product_ids   = $_POST['product_id'];

		/**
		 * Filters the notify URL.
		 *
		 * The `notify_url` argument is an IPN only argument specifying the URL PayPal should
		 * use to POST the payment information.
		 *
		 * @since TBD
		 *
		 * @see  \Tribe__Tickets__Commerce__PayPal__Handler__IPN::check_response()
		 * @link https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/
		 *
		 * @param string $notify_url
		 * @param WP_Post $post The post tickets are associated with
		 * @param array $product_ids An array of ticket post IDs that are being added to the cart
		 */
		$notify_url = apply_filters( 'tribe_tickets_commerce_paypal_notify_url', home_url(), $post, $product_ids );

		$custom_args = array( 'user_id' => get_current_user_id(), 'tribe_handler' => 'tpp' );
		$custom      = Tribe__Tickets__Commerce__PayPal__Custom_Argument::encode( $custom_args );

		$args = array(
			'cmd'           => '_cart',
			'add'           => 1,
			'business'      => urlencode( trim( tribe_get_option( 'ticket-paypal-email' ) ) ),
			'bn'            => 'ModernTribe_SP',
			'notify_url'    => urlencode( $notify_url ),
			'shopping_url'  => urlencode( $post_url ),
			'currency_code' => $currency_code ? $currency_code : 'USD',
			'custom'        => $custom,
			'invoice'       => $this->set_invoice_number(),
		);

		foreach ( $product_ids as $ticket_id ) {
			$ticket   = tribe( 'tickets.commerce.paypal' )->get_ticket( $post->ID, $ticket_id );
			$quantity = absint( $_POST[ "quantity_{$ticket_id}" ] );

			// skip if the ticket in no longer in stock or is not sellable
			if (
				! $ticket->is_in_stock()
				|| ! $ticket->date_in_range( $now )
			) {
				continue;
			}

			$inventory    = $ticket->inventory();
			$is_unlimited = $inventory === - 1;

			// if the requested amount is greater than remaining, use remaining instead
			if ( ! $is_unlimited && $quantity > $inventory ) {
				$quantity = $inventory;
			}

			// enforce purchase limit
			if ( $ticket->purchase_limit && $quantity > $ticket->purchase_limit ) {
				$quantity = $ticket->purchase_limit;
			}

			// if the ticket doesn't have a quantity, skip it
			if ( empty( $quantity ) ) {
				continue;
			}

			$args['quantity']    = $quantity;
			$args['amount']      = $ticket->price;
			$args['item_number'] = "{$post->ID}:{$ticket->ID}";
			$args['item_name']   = urlencode( wp_kses_decode_entities( $this->get_product_name( $ticket, $post ) ) );

			// we can only submit one product at a time. Bail if we get to here because we have a product
			// with a requested quantity
			break;
		}

		// If there isn't a quantity at all, then there's nothing to purchase. Redirect with an error
		if ( empty( $args['quantity'] ) || ! is_numeric( $args['quantity'] ) || (int) $args['quantity'] < 1 ) {
			/**
			 * @see Tribe__Tickets__Commerce__PayPal__Main::add_error_message for error codes
			 */
			wp_safe_redirect( add_query_arg( array( 'tpp_error' => 3 ), $post_url ) );
			die;
		}

		/**
		 * Filters the arguments passed to PayPal while adding items to the cart
		 *
		 * @since TBD
		 *
		 * @param array   $args
		 * @param array   $data POST data from buy now submission
		 * @param WP_Post $post Post object that has tickets attached to it
		 */
		$args = apply_filters( 'tribe_tickets_commerce_paypal_add_to_cart_args', $args, $_POST, $post );

		$url = add_query_arg( $args, $url );

		wp_redirect( $url );
		die;
	}

	/**
	 * Parses PayPal transaction data into a more organized structure
	 *
	 * @since TBD
	 *
	 * @link https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/
	 *
	 * @param array $transaction Transaction data from PayPal in key/value pairs
	 *
	 * @return array|false The parsed transaction data or `false` if the transaction could not be processed for any reason.
	 */
	public function parse_transaction( array $transaction ) {
		if ( ! empty( $transaction['custom'] ) ) {
			$decoded_custom = Tribe__Tickets__Commerce__PayPal__Custom_Argument::decode( $transaction['custom'], true );

			if ( empty( $decoded_custom['tribe_handler'] ) || 'tpp' !== $decoded_custom['tribe_handler'] ) {
				return false;
			}
		}

		if ( $this->handler instanceof Tribe__Tickets__Commerce__PayPal__Handler__Invalid_PDT ) {
			$this->handler->save_transaction();

			return false;
		}

		$item_indexes = array(
			'item_number',
			'item_name',
			'quantity',
			'mc_handling',
			'mc_shipping',
			'tax',
			'mc_gross_',
		);

		$item_indexes_regex = '/(' . implode( '|', $item_indexes ) . ')(\d)/';

		$data = array(
			'items' => array(),
		);


		foreach ( $transaction as $key => $value ) {
			if ( ! preg_match( $item_indexes_regex, $key, $matches ) ) {
				$data[ $key ] = $value;
				continue;
			}

			$index = $matches[2];
			$name = trim( $matches[1], '_' );

			if ( ! isset( $data['items'][ $index ] ) ) {
				$data['items'][ $index ] = array();
			}

			$data['items'][ $index ][ $name ] = $value;
		}

		foreach ( $data['items'] as &$item ) {
			if ( ! isset( $item['item_number'] ) ) {
				continue;
			}

			list( $item['post_id'], $item['ticket_id'] ) = explode( ':', $item['item_number'] );

			$item['ticket'] = tribe( 'tickets.commerce.paypal' )->get_ticket( $item['post_id'], $item['ticket_id'] );
		}

		return $data;
	}

	/**
	 * Sets transaction data from PayPal as a class property
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	public function set_transaction_data( $data ) {
		/**
		 * Filters the transaction data as it is being set
		 *
		 * @since TBD
		 *
		 * @param array $data
		 */
		$this->transaction_data = apply_filters( 'tribe_tickets_commerce_paypal_set_transaction_data', $data );
	}

	/**
	 * Gets PayPal transaction data
	 *
	 * @since TBD
	 *
	 * @param array $data
	 */
	public function get_transaction_data() {
		/**
		 * Filters the transaction data as it is being retrieved
		 *
		 * @since TBD
		 *
		 * @param array $transaction_data
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_get_transaction_data', $this->transaction_data );
	}

	/**
	 * Gets the full PayPal product name
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Ticket_Object $ticket Ticket whose name is being generated
	 * @param WP_Post $post Post that the tickets are attached to
	 *
	 * @return string
	 */
	public function get_product_name( $ticket, $post ) {
		$title = get_the_title( $post->ID );
		$name  = $ticket->name;

		$product_name = "{$name} - {$title}";

		/**
		 * Filters the product name for PayPal's cart
		 *
		 * @since TBD
		 *
		 * @param string $product_name
		 * @param Tribe__Tickets__Ticket_Object
		 */
		return apply_filters( 'tribe_tickets_commerce_paypal_product_name', $product_name, $ticket );
	}

	/**
	 * Sets an invoice number (generating it if one doesn't exist) in the cookies.
	 *
	 * @since TBD
	 *
	 * @return string The invoice alpha-numeric identifier
	 */
	public function set_invoice_number() {
		$invoice = $this->get_invoice_number();

		// set the cookie (if it was already set, it'll extend the lifetime)
		setcookie( self::$invoice_cookie_name, $invoice, DAY_IN_SECONDS );

		return $invoice;
	}

	/**
	 * Purges an invoice cookie
	 *
	 * @since TBD
	 */
	public function reset_invoice_number() {
		if ( empty( $_COOKIE[ self::$invoice_cookie_name ] ) ) {
			return;
		}

		unset( $_COOKIE[ self::$invoice_cookie_name ] );
		setcookie( self::$invoice_cookie_name, null, -1 );
	}

	/**
	 * Returns the PayPal cart URL
	 *
	 * @since TBD
	 *
	 * @param string $path An optional path to append to the URL
	 *
	 * @return string
	 */
	public function get_cart_url( $path = '' ) {
		$path = '/' . ltrim( $path, '/' );

		return tribe_get_option( 'ticket-paypal-sandbox' )
			? $this->sandbox_cart_url . $path
			: $this->cart_url . $path;
	}

	/**
	 * Returns the PyaPal base URL
	 *
	 * @since TBD
	 *
	 * @param string $path An optional path to append to the URL
	 *
	 * @return string
	 */
	public function get_base_url( $path = '' ) {
		$path = '/' . ltrim( $path, '/' );

		return tribe_get_option( 'ticket-paypal-sandbox' )
			? $this->sandbox_base_url . $path
			: $this->base_url . $path;
	}

	/**
	 * Returns the PayPal URL to a transaction details.
	 *
	 * @since TBD
	 *
	 * @param string $transaction The transaction alpha-numeric identifier
	 *
	 * @return string
	 */
	public function get_transaction_url( $transaction ) {
		return $this->get_base_url( "activity/payment/{$transaction}" );
	}

	/**
	 * Builds the correct handler depending on the request type and options.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Tickets__Commerce__PayPal__Handler__Interface The handler instance.
	 */
	public function build_handler() {
		$handler = $this->get_handler_slug();

		if ( $handler === 'pdt' && ! empty( $_GET['tx'] ) ) {
			// looks like a PDT request
			if ( ! empty( $this->identity_token ) ) {
				// if there's an identity token set we handle payment confirmation with PDT
				$this->handler = tribe( 'tickets.commerce.paypal.handler.pdt' );
			} else {
				// if there is not an identity token set we log a missed transaction and show a notice
				$this->notices->show_missing_identity_token_notice();
				$this->handler = new Tribe__Tickets__Commerce__PayPal__Handler__Invalid_PDT( $_GET['tx'] );
			}
		} else {
			// we use IPN otherwise
			$this->handler = tribe( 'tickets.commerce.paypal.handler.ipn' );
		}

		return $this->handler;
	}

	/**
	 * Returns the invoice number reading it from the cookie or generating a new one.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function get_invoice_number() {
		$invoice_length = 127;

		if (
			! empty( $_COOKIE[ self::$invoice_cookie_name ] )
			&& strlen( $_COOKIE[ self::$invoice_cookie_name ] ) === $invoice_length
		) {
			$invoice = $_COOKIE[ self::$invoice_cookie_name ];
		} else {
			$invoice = wp_generate_password( $invoice_length, false );
		}

		return $invoice;
	}

	/**
	 * Returns the slug of the default payment handler.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_handler_slug() {
		/**
		 * Filters which PayPal payment method should be used.
		 *
		 * @since TBD
		 *
		 * @param string $handler One of `pdt` or `ipn`
		 */
		$handler = apply_filters( 'tribe_tickets_commerce_paypal_handler', 'ipn' );

		$handler = in_array( $handler, array( 'pdt', 'ipn' ) ) ? $handler : 'ipn';

		return $handler;
	}
}
