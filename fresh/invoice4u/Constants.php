<?php

/**
 * User: Shiran
 * Date: 02/19/2015
 */
abstract class Language {
	const Hebrew = 1;
	const English = 2;
}

abstract class DocumentType {
	const Invoice = 1;
	const Receipt = 2;
	const InvoiceReceipt = 3;
	const InvoiceCredit = 4;
	const ProformaInvoice = 5;
	const InvoiceOrder = 6;
	const InvoiceShip = 8;
	const Deposits = 9;
}

abstract class PaymentType {
	const CreditCard = 1;
	const Check = 2;
	const MoneyTransfer = 3;
	const Cash = 4;
	const Credit = 5;
}

?>