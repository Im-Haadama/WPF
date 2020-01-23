<?php

class FreshDocumentType {
	const order = 1, // Client
		delivery = 2, // Client
		refund = 3, // Client
		invoice = 4, // Supplier
		supply = 5, // Supplier
		ship = 6,  // Legacy
		bank = 7,
		invoice_refund = 8, // Supplier
		count = 9;
}

class Fresh_DocumentOperation {
	const
		collect = 0, // From order to delivery, before collection
		create = 1, // From order to delivery. Expand basket
		show = 2,     // Load from db
		edit = 3,    // Load and edit
		check = 4;  // Checkup
	// packing = 4;

}

class eDeliveryFields {
	const
		/// User interface
		line_select = 0,
		/// Product info
		product_name = 1,
		product_id = 2,
		term = 3,
		// Order info
		order_q = 4, // Only display
		order_q_units = 5,
		delivery_q = 6,
		price = 7,
		order_line = 8,
		// Delivery info
		has_vat = 9,
		line_vat = 10,
		delivery_line = 11,
		// Refund info
		refund_q = 12,
		refund_line = 13,
		buy_price = 14,
		line_margin = 15,
		packing_info = 16,
		line_type = 17,
		max_fields = 18;
}
