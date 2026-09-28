/**
 * Brazil Checkout Essentials — classic checkout enhancements.
 *
 * Dependency-free (no jQuery). Progressive enhancement only: every rule is
 * enforced again on the server, so the checkout still works without JS.
 *
 *  - Input masks for CPF, CNPJ (numeric and 2026 alphanumeric), CEP and phone.
 *  - Shows the CPF or CNPJ field according to the selected person type.
 *  - Hides the document fields when the billing country is not Brazil.
 *
 * @package BrazilCheckoutEssentials
 */
( function () {
	'use strict';

	var config = window.wcbceCheckout || {};

	/**
	 * Mask patterns. Tokens: "0" = digit, "A" = digit or uppercase letter.
	 * Any other character is a literal inserted automatically.
	 */
	var PATTERNS = {
		cpf: '000.000.000-00',
		cnpj: 'AA.AAA.AAA/AAAA-00',
		cep: '00000-000',
		landline: '(00) 0000-0000',
		mobile: '(00) 00000-0000'
	};

	var TOKENS = {
		0: /[0-9]/,
		A: /[0-9A-Z]/
	};

	/**
	 * Applies a pattern to a raw value, dropping characters the pattern
	 * does not accept at each position.
	 *
	 * @param {string} value   Raw input.
	 * @param {string} pattern Mask pattern.
	 * @return {string} Masked value.
	 */
	function applyMask( value, pattern ) {
		var chars = String( value ).toUpperCase().replace( /[^0-9A-Z]/g, '' );
		var output = '';
		var cursor = 0;

		for ( var i = 0; i < pattern.length && cursor < chars.length; i++ ) {
			var token = TOKENS[ pattern.charAt( i ) ];

			if ( ! token ) {
				output += pattern.charAt( i );
				continue;
			}

			while ( cursor < chars.length && ! token.test( chars.charAt( cursor ) ) ) {
				cursor++;
			}

			if ( cursor < chars.length ) {
				output += chars.charAt( cursor++ );
			}
		}

		return output.replace( /[^0-9A-Z]+$/, '' );
	}

	/**
	 * Masks a phone number, dropping a leading +55 country code and picking
	 * the landline or mobile pattern from the number of digits typed so far.
	 *
	 * @param {string} value Raw input.
	 * @return {string} Masked value.
	 */
	function maskPhone( value ) {
		var digits = String( value ).replace( /\D/g, '' );

		if ( digits.length > 11 && digits.indexOf( '55' ) === 0 ) {
			digits = digits.slice( 2 );
		}

		return applyMask( digits, digits.length > 10 ? PATTERNS.mobile : PATTERNS.landline );
	}

	/**
	 * Returns the country selected for an address type.
	 *
	 * @param {string} type "billing" or "shipping".
	 * @return {string} Country code, or "BR" when the store sells to Brazil only.
	 */
	function countryOf( type ) {
		var field = document.getElementById( type + '_country' );

		return field ? field.value : 'BR';
	}

	/**
	 * Returns the masked value for an input, or null when no mask applies.
	 *
	 * @param {HTMLInputElement} input Input element.
	 * @return {string|null} Masked value.
	 */
	function maskedValueOf( input ) {
		var explicit = input.getAttribute( 'data-wcbce-mask' );

		if ( explicit && config.document && PATTERNS[ explicit ] ) {
			return applyMask( input.value, PATTERNS[ explicit ] );
		}

		if ( ! config.address ) {
			return null;
		}

		var match = /^(billing|shipping)_(postcode|phone)$/.exec( input.id );

		if ( ! match || countryOf( match[ 1 ] ) !== 'BR' ) {
			return null;
		}

		return match[ 2 ] === 'postcode' ? applyMask( input.value, PATTERNS.cep ) : maskPhone( input.value );
	}

	/**
	 * Masks the field that fired the event.
	 *
	 * @param {Event} event Input event.
	 */
	function onInput( event ) {
		var input = event.target;

		if ( ! ( input instanceof HTMLInputElement ) ) {
			return;
		}

		var masked = maskedValueOf( input );

		if ( masked !== null && masked !== input.value ) {
			input.value = masked;
		}
	}

	/**
	 * Shows/hides a form row.
	 *
	 * @param {string}  rowId   Row element id.
	 * @param {boolean} visible Visibility.
	 */
	function toggleRow( rowId, visible ) {
		var row = document.getElementById( rowId );

		if ( row ) {
			row.hidden = ! visible;
		}
	}

	/**
	 * Syncs document fields with person type and billing country.
	 */
	function syncDocumentFields() {
		if ( ! config.document ) {
			return;
		}

		var personType = document.getElementById( 'billing_persontype' );
		var isBrazil = countryOf( 'billing' ) === 'BR';
		var isCompany = !! personType && personType.value === String( config.companyPerson );

		toggleRow( 'billing_persontype_field', isBrazil );
		toggleRow( 'billing_cpf_field', isBrazil && ! isCompany );
		toggleRow( 'billing_cnpj_field', isBrazil && isCompany );
	}

	/**
	 * Re-applies masks to pre-filled values (returning customers).
	 */
	function maskPrefilled() {
		var selector = '[data-wcbce-mask], #billing_postcode, #shipping_postcode, #billing_phone, #shipping_phone';

		document.querySelectorAll( selector ).forEach( function ( input ) {
			var masked = input.value ? maskedValueOf( input ) : null;

			if ( masked !== null ) {
				input.value = masked;
			}
		} );
	}

	function init() {
		document.addEventListener( 'input', onInput );

		document.addEventListener( 'change', function ( event ) {
			var id = event.target && event.target.id;

			if ( id === 'billing_persontype' || id === 'billing_country' ) {
				syncDocumentFields();
			}
		} );

		// The country field is a select2/selectWoo widget that fires jQuery
		// events only; listen to them when jQuery happens to be present.
		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'country_to_state_changed updated_checkout', syncDocumentFields );
		}

		syncDocumentFields();
		maskPrefilled();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
