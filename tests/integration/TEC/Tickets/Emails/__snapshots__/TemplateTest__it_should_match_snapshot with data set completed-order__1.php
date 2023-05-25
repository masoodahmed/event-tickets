<?php return '<style type="text/css">
	body {
		color: #3C434A;
		margin: 0;
		padding: 0;
		text-align: left;
	}

	body, table, td, div, h1, p { font-family: Helvetica, Arial, sans-serif; }
	table { width: 100% }
	table, td { border: 0; border-spacing: 0; }

	.tec-tickets__email-body a,
	.tec-tickets__email-body a:focus,
	.tec-tickets__email-body a:visited {
		color: #141827;
		text-decoration: underline;
	}

	.tec-tickets__email-body,
	div.tec-tickets__email-body {
		background: #f0eeeb;
		border: 0;
		border-collapse: collapse;
		border-spacing: 0;
		color: #3C434A;
		padding-bottom: 30px;
		padding-top: 30px;
		width: 100%;
	}

	.tec-tickets__email-preview-link,
	div.tec-tickets__email-preview-link {
		font-size: 11px;
		padding: 15px;
		margin: 0 auto;
		max-width: 600px;
		text-align: center;
	}

	.tec-tickets__email-table-main,
	table.tec-tickets__email-table-main {
		background: #ffffff;
		border: 0;
		border-collapse: collapse;
		border-spacing: 0;
		margin:0 auto;
		max-width: 600px;
		text-align: left;
		width: 100%;
	}

	.tec-tickets__email-table-main-header,
	td.tec-tickets__email-table-main-header {
		background: #000000;
		padding: 5px 5px 0px 5px;
		text-align: center;
	}

	.tec-tickets__email-table-content,
	table.tec-tickets__email-table-content {
		border: 0;
		border-spacing: 0;
		padding: 15px 30px;
		width: 100%;
	}

	.tec-tickets__email-table-content,
	.tec-tickets__email-table-content table {
		font-size: 14px;
	}

	.tec-tickets__email-table-content-title,
	h1.tec-tickets__email-table-content-title {
		color: #141827;
		font-size: 28px;
		font-weight: 700;
		letter-spacing: 0px;
		line-height: 1.25;
		margin: 0;
		padding: 24px 0 !important;
		text-align: left;
	}

	.tec-tickets__email-table-content-event-title,
	h3.tec-tickets__email-table-content-event-title {
		color: #141827;
		font-size: 18px;
		font-style: normal;
		font-weight: 700;
		margin: 0;
		padding: 20px 0 !important;
	}

	.tec-tickets__email-table-content-tickets-total {
		background-color: #000000;
		color: #000000;
		font-weight: 700;
		padding:10px;
		text-align: center;
	}

	.tec-tickets__email-table-content-tickets tbody > tr:last-child > td {
		border-bottom: 0;
	}

	.tec-tickets__email-table-content-ticket,
	td.tec-tickets__email-table-content-ticket {
		background:#000000;
		border-bottom: 1px solid rgba(0,0,0,.08);
		padding: 20px 25px;
	}

	.tec-tickets__email-table-content-ticket-holder-name,
	h2.tec-tickets__email-table-content-ticket-holder-name {
		background:transparent;
		color: #ffffff;
		font-size: 21px;
		font-weight: 700;
		line-height: 24px;
		margin: 0;
		padding: 0;
	}

	.tec-tickets__email-table-content-ticket-type-name,
	div.tec-tickets__email-table-content-ticket-type-name {
		color: #ffffff;
		font-size: 16px;
		margin-top: 8px;
		padding: 0;
	}

	.tec-tickets__email-table-content-ticket-security-code,
	div.tec-tickets__email-table-content-ticket-security {
		color: #ffffff;
		display: block;
		font-size: 14px;
		font-weight: 400;
		margin: 0 !important;
		opacity: .7;
		padding: 15px 0 0 0 !important;
	}

	.tec-tickets__email-table-content-ticket-number-from-total,
	div.tec-tickets__email-table-content-ticket-number-from-total {
		color: #ffffff;
		font-size: 14px;
		font-weight: 700;
		display: block;
		margin: 0 !important;
		padding: 15px 0 0 0 !important;
	}

	.tec-tickets__email-table-main-footer,
	td.tec-tickets__email-table-main-footer {
		background-color: #000000;
		border-top: 1px solid #efefef;
		padding: 0px 20px 10px 20px;
	}

	.tec-tickets__email-table-main-footer table {
		font-size: 12px;
	}

	.tec-tickets__email-table-content-order-attendees-table-container,
	td.tec-tickets__email-table-content-order-attendees-table-container {
		padding-bottom: 50px;
	}

	.tec-tickets__email-table-content-order-attendees-table,
	table.tec-tickets__email-table-content-order-attendees-table {
		border-collapse: collapse;
		margin-top: 10px;
	}

	.tec-tickets__email-table-content-order-attendees-table-header-row,
	tr.tec-tickets__email-table-content-order-attendees-table-header-row {
		border: 1px solid #d5d5d5;
		color: #727272;
		font-size: 12px;
		font-weight: 400;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-attendees-table-header-cell,
	td.tec-tickets__email-table-content-order-attendees-table-header-cell {
		padding:0 6px;
	}

	.tec-tickets__email-table-content-order-attendee-info-row,
	tr.tec-tickets__email-table-content-order-attendee-info-row {
		border: 1px solid #d5d5d5;
		font-size: 12px;
		font-weight: 400;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-attendee-info,
	td.tec-tickets__email-table-content-order-attendee-info {
		line-height: 18px;
		padding: 0 6px;
		vertical-align: top;
	}

	.tec-tickets__email-table-content-align-left {
		text-align: left;
	}

	.tec-tickets__email-table-content-align-center {
		text-align: center;
	}

	.tec-tickets__email-table-content-align-right {
		text-align: right;
	}

	.tec-tickets__email-table-content-order-error-top-text {
		font-size: 14px;
		font-weight: 400;
		padding-top: 10px;
	}

	.tec-tickets__email-table-content-order-error-bottom-text,
	td.tec-tickets__email-table-content-order-error-bottom-text {
		color: #da394d;
		font-size: 14px;
		font-weight: 700;
		padding: 24px 0 40px;
	}

	.tec-tickets__email-table-content-order-event-title,
	td.tec-tickets__email-table-content-order-event-title {
		font-size: 16px;
		font-weight: 700;
		padding-top:43px;
	}

	.tec-tickets__email-table-content-order-total-container,
	td.tec-tickets__email-table-content-order-total-container {
		padding-top: 20px;
		text-align: right;
	}

	.tec-tickets__email-table-content-order-total-table {
		display: inline-block;
		width: auto;
	}

	.tec-tickets__email-table-content-order-total-left-cell,
	td.tec-tickets__email-table-content-order-total-left-cell {
		font-size: 14px;
		font-weight: 400;
		line-height: 24px;
		padding-right: 10px;
	}

	.tec-tickets__email-table-content-order-total-right-cell {
		font-size: 16px;
		font-weight: 700;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-payment-info-container,
	td.tec-tickets__email-table-content-order-payment-info-container {
		font-size: 14px;
		font-weight: 400;
		padding: 20px 0 50px;
		text-align: right;
	}

	.tec-tickets__email-table-content-order-purchaser-details-top {
		font-size: 16px;
		font-weight: 700;
		line-height: 23px;
	}

	.tec-tickets__email-table-content-order-purchaser-details-bottom {
		font-size: 14px;
		font-weight: 400;
		line-height: 23px;
	}

	.tec-tickets__email-table-content-order-ticket-totals-header-row,
	tr.tec-tickets__email-table-content-order-ticket-totals-header-row {
		border: 1px solid #d5d5d5;
		color: #727272;
		font-size: 12px;
		font-weight: 400;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-ticket-totals-ticket-row,
	tr.tec-tickets__email-table-content-order-ticket-totals-ticket-row {
		border: 1px solid #d5d5d5;
		font-size: 14px;
		font-weight: 400;
		line-height: 24px;
	}

	.tec-tickets__email-table-content-order-ticket-totals-cell,
	th.tec-tickets__email-table-content-order-ticket-totals-cell,
	td.tec-tickets__email-table-content-order-ticket-totals-cell {
		padding: 0 6px;
	}

	.tec-tickets__email-table-content-greeting-container,
	td.tec-tickets__email-table-content-greeting-container {
		line-height: 21px;
		padding-bottom: 40px;
	}

	.tec-tickets__email-table-content-additional-content-container,
	td.tec-tickets__email-table-content-additional-content-container {
		padding-bottom: 40px;
	}
</style>
<div class="tec-tickets__email-body">

		
		<table role="presentation" class="tec-tickets__email-table-main">

			<tr>
	<td
		class="tec-tickets__email-table-main-header"
		align="center"
	>
			</td>
</tr>

			<tr>
				<td>
					<table role="presentation" class="tec-tickets__email-table-content">
<tr>
	<td>
		<h1 class="tec-tickets__email-table-content-title">
					</h1>
	</td>
</tr>
<tr>
	<td>
		<table>
			<tr>
				<td class="tec-tickets__email-table-content-order-purchaser-details-top tec-tickets__email-table-content-align-left" align="left">
	Order #-99</td>
				<td class="tec-tickets__email-table-content-order-purchaser-details-top tec-tickets__email-table-content-align-right" align="right">
	John Doe</td>
			</tr>
			<tr>
				<td class="tec-tickets__email-table-content-order-purchaser-details-bottom tec-tickets__email-table-content-align-left" align="left">
	April 17, 2023</td>
				<td class="tec-tickets__email-table-content-order-purchaser-details-bottom tec-tickets__email-table-content-align-right" align="right">
	john@doe.com</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td>
		<table style="border-collapse:collapse;margin-top:10px">
			<tr class="tec-tickets__email-table-content-order-ticket-totals-header-row">
	<th style="width: 80%" class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-left" align="left">
		Ticket	</th>
	<th class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-center" align="center">
		Qty	</th>
	<th class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-right" align="right">
		Price	</th>
</tr>
							<tr class="tec-tickets__email-table-content-order-ticket-totals-ticket-row">
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-left" align="left">
	General Admission</td>
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-center" align="center">
	2</td>
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-right" align="right">
	&#x24;50.00</td>
</tr>
					</table>
	</td>
</tr>
<tr>
	<td class="tec-tickets__email-table-content-order-total-container" align="right">
		<table class="tec-tickets__email-table-content-order-total-table">
			<tr>
				<td class="tec-tickets__email-table-content-order-total-left-cell">
					Order Total				</td>
				<td class="tec-tickets__email-table-content-order-total-right-cell">
					&#x24;100.00				</td>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td class="tec-tickets__email-table-content-order-attendees-table-container">
		<table class="tec-tickets__email-table-content-order-attendees-table">
			<tr class="tec-tickets__email-table-content-order-attendees-table-header-row">
	<td class="tec-tickets__email-table-content-order-attendees-table-header-cell tec-tickets__email-table-content-align-left" align="left">
		Attendee	</td>
	<td class="tec-tickets__email-table-content-order-attendees-table-header-cell tec-tickets__email-table-content-align-center" align="center">
		Type	</td>
	<td class="tec-tickets__email-table-content-order-attendees-table-header-cell tec-tickets__email-table-content-align-right" align="right">
		Ticket ID	</td>
</tr>
							<tr class="tec-tickets__email-table-content-order-attendee-info-row">
	<td class="tec-tickets__email-table-content-order-attendee-info tec-tickets__email-table-content-align-left" align="left">
		<div>
	John Doe</div>
		<div>
	john@doe.com</div>
			</td>
	<td class="tec-tickets__email-table-content-order-attendee-info tec-tickets__email-table-content-align-center" align="center">
	General Admission</td>
	<td class="tec-tickets__email-table-content-order-attendee-info tec-tickets__email-table-content-align-right" align="right">
	abcde12345</td>
</tr>
							<tr class="tec-tickets__email-table-content-order-attendee-info-row">
	<td class="tec-tickets__email-table-content-order-attendee-info tec-tickets__email-table-content-align-left" align="left">
		<div>
	Jane Doe</div>
		<div>
	jane@doe.com</div>
			</td>
	<td class="tec-tickets__email-table-content-order-attendee-info tec-tickets__email-table-content-align-center" align="center">
	General Admission</td>
	<td class="tec-tickets__email-table-content-order-attendee-info tec-tickets__email-table-content-align-right" align="right">
	12345abcde</td>
</tr>
					</table>
	</td>
</tr>
					</table>
				</td>
			</tr>
			<tr>
	<td class="tec-tickets__email-table-main-footer">
		<table role="presentation" style="width:100%;border-collapse:collapse;border:0;border-spacing:0;">
						<tr>
	<td style="padding:10px 0px 0px 0px;text-align:right;color:#000000;" align="right">
		Powered by <a href="https://evnt.is/et-in-app-email-credit" style="color:#000000">Event Tickets</a>	</td>
</tr>
		</table>
	</td>
</tr>
		</table>
</div>
';