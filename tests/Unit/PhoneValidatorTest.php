<?php
/**
 * Tests for PhoneValidator.
 *
 * @package BrazilCheckoutEssentials
 */

declare(strict_types=1);

namespace BrazilCheckoutEssentials\Tests\Unit;

use BrazilCheckoutEssentials\Validation\PhoneValidator;
use PHPUnit\Framework\TestCase;

/**
 * Fixtures use fictional subscriber numbers.
 *
 * @covers \BrazilCheckoutEssentials\Validation\PhoneValidator
 */
final class PhoneValidatorTest extends TestCase {

	/**
	 * System under test.
	 *
	 * @var PhoneValidator
	 */
	private $validator;

	protected function setUp(): void {
		$this->validator = new PhoneValidator();
	}

	/**
	 * @dataProvider mobiles
	 *
	 * @param string $phone     Input.
	 * @param string $formatted Expected mask.
	 */
	public function test_accepts_mobile( string $phone, string $formatted ): void {
		$this->assertTrue( $this->validator->is_valid( $phone ) );
		$this->assertTrue( $this->validator->is_mobile( $phone ) );
		$this->assertFalse( $this->validator->is_landline( $phone ) );
		$this->assertSame( $formatted, $this->validator->format( $phone ) );
	}

	/**
	 * @return array<string, array{0:string, 1:string}>
	 */
	public static function mobiles(): array {
		return [
			'masked'         => [ '(11) 91234-5678', '(11) 91234-5678' ],
			'digits only'    => [ '21987654321', '(21) 98765-4321' ],
			'country code'   => [ '+55 61 99876-5432', '(61) 99876-5432' ],
			'trunk prefix'   => [ '0 (48) 91234-0000', '(48) 91234-0000' ],
			'dots as groups' => [ '85.91234.5678', '(85) 91234-5678' ],
		];
	}

	/**
	 * @dataProvider landlines
	 *
	 * @param string $phone     Input.
	 * @param string $formatted Expected mask.
	 */
	public function test_accepts_landline( string $phone, string $formatted ): void {
		$this->assertTrue( $this->validator->is_valid( $phone ) );
		$this->assertTrue( $this->validator->is_landline( $phone ) );
		$this->assertFalse( $this->validator->is_mobile( $phone ) );
		$this->assertSame( $formatted, $this->validator->format( $phone ) );
	}

	/**
	 * @return array<string, array{0:string, 1:string}>
	 */
	public static function landlines(): array {
		return [
			'masked'       => [ '(11) 2345-6789', '(11) 2345-6789' ],
			'digits only'  => [ '3133334444', '(31) 3333-4444' ],
			'country code' => [ '+55 51 5555-0000', '(51) 5555-0000' ],
			'trunk prefix' => [ '0 41 4000-1234', '(41) 4000-1234' ],
		];
	}

	/**
	 * @dataProvider invalid_phones
	 *
	 * @param string $phone Invalid input.
	 */
	public function test_rejects_invalid_phone( string $phone ): void {
		$this->assertFalse( $this->validator->is_valid( $phone ) );
		$this->assertSame( $phone, $this->validator->format( $phone ) );
		$this->assertSame( '', $this->validator->to_e164( $phone ) );
	}

	/**
	 * @return array<string, array{0:string}>
	 */
	public static function invalid_phones(): array {
		return [
			'empty'                    => [ '' ],
			'unallocated area code 20' => [ '(20) 91234-5678' ],
			'unallocated area code 10' => [ '(10) 2345-6789' ],
			'mobile without leading 9' => [ '(11) 81234-5678' ],
			'landline starting with 6' => [ '(11) 6345-6789' ],
			'too short'                => [ '(11) 1234-567' ],
			'too long'                 => [ '(11) 912345-67890' ],
			'letters'                  => [ '(11) 9123A-5678' ],
			'foreign number'           => [ '+1 415 555 0100' ],
		];
	}

	public function test_normalize_returns_national_number(): void {
		$this->assertSame( '11912345678', $this->validator->normalize( '+55 (11) 91234-5678' ) );
		$this->assertSame( '1123456789', $this->validator->normalize( '011 2345-6789' ) );
	}

	public function test_to_e164(): void {
		$this->assertSame( '+5511912345678', $this->validator->to_e164( '(11) 91234-5678' ) );
		$this->assertSame( '+551123456789', $this->validator->to_e164( '(11) 2345-6789' ) );
	}
}
