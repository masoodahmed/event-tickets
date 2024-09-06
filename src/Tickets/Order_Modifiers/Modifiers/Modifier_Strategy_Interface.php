<?php

namespace TEC\Tickets\Order_Modifiers\Modifiers;

/**
 * Strategy Interface for Order Modifiers.
 *
 * Defines the methods that concrete strategies (such as Coupon or Booking Fee)
 * must implement for inserting, updating, and validating order modifiers.
 *
 * @since TBD
 */
interface Modifier_Strategy_Interface {

	/**
	 * Gets the modifier type (e.g., 'coupon', 'fee').
	 *
	 * This method ensures that each strategy explicitly defines
	 * its modifier type, allowing the system to identify and handle
	 * different modifier types correctly.
	 *
	 * @since TBD
	 *
	 * @return string The modifier type (e.g., 'coupon', 'fee').
	 */
	public function get_modifier_type(): string;

	/**
	 * Inserts a new Order Modifier into the system.
	 *
	 * @since TBD
	 *
	 * @param array $data The data for the order modifier to insert.
	 *
	 * @return mixed The result of the insertion, typically the inserted order modifier or an empty array on failure.
	 */
	public function insert_modifier( array $data ): mixed;

	/**
	 * Updates an existing Order Modifier in the system.
	 *
	 * @since TBD
	 *
	 * @param array $data The data for the order modifier to update.
	 *
	 * @return mixed The result of the update, typically the updated order modifier or an empty array on failure.
	 */
	public function update_modifier( array $data ): mixed;

	/**
	 * Validates the provided data for the order modifier.
	 *
	 * This method ensures that the data contains all required fields and
	 * that the values are valid before insertion or update.
	 *
	 * @since TBD
	 *
	 * @param array $data The data to validate.
	 *
	 * @return bool True if the data is valid, false otherwise.
	 */
	public function validate_data( array $data ): bool;
}
