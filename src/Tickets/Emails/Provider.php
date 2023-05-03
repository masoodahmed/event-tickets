<?php
/**
 * The main service provider for the Tickets Emails.
 *
 * @since   5.5.6
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use tad_DI52_ServiceProvider;

/**
 * Service provider for the Tickets Emails.
 *
 * @since   5.5.6
 * @package TEC\Tickets\Emails
 */
class Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.5.6
	 */
	public function register(): void {
		$this->register_assets();
		$this->register_hooks();

		// Register singletons.
		$this->container->singleton( static::class, $this );

		// Dispatcher is not a singleton!
		$this->container->bind( Dispatcher::class, Dispatcher::class );

		$this->container->singleton( Post_Type::class );

		$this->container->singleton( Legacy_Hijack::class );

		$this->container->singleton( Admin\Emails_Tab::class );

		$this->container->singleton( Admin\Preview_Modal::class );

		$this->container->register( Email_Handler::class );

		$this->container->singleton( Web_View::class );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Emails.
	 *
	 * @since 5.5.6
	 */
	protected function register_assets(): void {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Emails.
	 *
	 * @since 5.5.6
	 */
	protected function register_hooks(): void {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having them registered to the container.
		$this->container->singleton( Hooks::class, $hooks );
	}
}
