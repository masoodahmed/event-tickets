<?php
/**
 * This template renders the event content
 *
 * @version 4.9
 *
 */
?>
<div class="tribe-block__tickets__registration__summary">

	<?php $this->template( 'registration/summary/toggle-handler' ); ?>

	<?php $this->template( 'registration/summary/registration-status' ); ?>

	<?php $this->template( 'registration/summary/title', array( 'event_id' => $event_id ) ); ?>

	<?php $this->template( 'registration/summary/description', array( 'event_id' => $event_id ) ); ?>

	<?php $this->template( 'registration/summary/tickets', array( 'tickets' => $tickets ) ); ?>

</div>
