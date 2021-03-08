<?php
/**
 * Copyright (C) 2021  Easify Ltd (email:support@easify.co.uk)
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @version     4.26
 * @package     easify-woocommerce-connector
 * @author      Easify
 * @link        https://www.easify.co.uk
 */

/**
 * Easify_WC_Coupon_Splitter class.
 *
 * The WooCommerceCouponSplitter is used to ensure that when a WooCommerce coupon
 * has been used on an order that contains multiple tax codes, we split the coupon
 * into multiple coupons, one for each tax code, so that these can be sent to the
 * order in Easify. In Easify we record each voucher as a separate voucher product
 * each with its own tax code so that we can correctly account for the VAT
 * content of each voucher.
 *
 * Currently we only support single coupons per order as this is the most likely
 * use-case for WooCommerce orders...
 *
 * @Category class
 * @class Easify_WC_Coupon_Splitter
 */
class Easify_WC_Coupon_Splitter {
	/**
	 * Contains the coupons we split out for each tax code.
	 *
	 * @var SplitCoupon[]
	 */
	private $split_coupons = array();

	/**
	 * Used to group line item totals by tax code.
	 *
	 * @var OrderDetailsTotal[]
	 */
	private $order_details_totals = array();

	/**
	 * The value of the original voucher (net ex tax).
	 *
	 * @var float
	 */
	private $coupon_value;

	/**
	 * The original coupon code.
	 *
	 * @var string
	 */
	private $coupon_code;

	/**
	 * Pass this method a collection of Easify order details, and a collection
	 * of WooCommerce vouchers, and it will return a collection of SplitVouchers,
	 * where each SplitVoucher represents the amount of discount per tax code on
	 * the order.
	 *
	 * @param array $easify_order_details Easify order details.
	 * @param array $woocommerce_coupons  WooCommerce coupons.
	 *
	 * @return SplitCoupon[]
	 */
	public function split_coupons( $easify_order_details, $woocommerce_coupons ): array {
		if ( count( $easify_order_details ) > 0 && count( $woocommerce_coupons ) > 0 ) {
			$this->coupon_value = $woocommerce_coupons[0]->coupon_value;
			$this->coupon_code  = $woocommerce_coupons[0]->coupon_code;

			foreach ( $easify_order_details as $order_detail ) {
				// Create one split coupon per vat rate.
				$this->add_split_coupon_to_array( $order_detail );

				// Keep running total of all order details by Taxid.
				$this->add_order_detail_total_to_array( $order_detail );
			}

			/*
			Here we have an array of vouchers with no amounts, one for each
			taxId and we have a list of order details totals for each tax id.
			Now we need to process them so that each split coupon has the correct
			proportion of the amount.
			*/

			// Assign the amounts of the split coupons as proportion of total...
			$this->proportion_amounts_to_split_coupons();

			return $this->split_coupons;
		} else {
			return array();
		}
	}

	/**
	 * A bit of a gotcha with this one, we iterate each tax code we have recorded
	 * in the orderDetailsTotals[] array and work out what proportion of the
	 * voucher each one should receive. However to avoid rounding issues for the last
	 * item in the list we calculate the amount by subtracting the running split
	 * vouchers total from the total value of the original coupon. This ensures that all the split
	 * vouchers will always add up to the value of the original voucher.
	 */
	private function proportion_amounts_to_split_coupons(): void {
		if ( count( $this->split_coupons ) === 1 ) {
			// If one split coupon, no need to split the coupon as there is only one
			// tax code in the order.
			$this->split_coupons[0]->amount = $this->coupon_value;
		} else {
			// Need to proportion coupon values between all coupon amounts...
			$split_vouchers_running_total = 0;

			$order_details_count = count( $this->order_details_totals );
			for ( $i = 0; $i < $order_details_count; $i ++ ) {
				if ( $i < count( $this->order_details_totals ) - 1 ) {
					// Not last value in list.
					$order_detail_proportion_of_total = $this->get_order_detail_proportion_of_total( $this->order_details_totals[ $i ] );

					$this->split_coupons[ $i ]->amount = $this->round_to_two_dps( $this->coupon_value * $order_detail_proportion_of_total );

					$split_vouchers_running_total += $this->split_coupons[ $i ]->amount;
				} else {
					// Last split coupon value is total coupon value - running total to avoid rounding errors.
					$this->split_coupons[ $i ]->amount = $this->coupon_value - $split_vouchers_running_total;
				}
			}
		}
	}

	/**
	 * Rounds the passed in float to 2 decimal places. Uses rounding down from mid-point as the value will
	 * be converted to a -ve value later.
	 *
	 * @param float $amount the amount that is to be rounded.
	 *
	 * @return float
	 */
	private function round_to_two_dps( float $amount ): float {
		return round( $amount, 2, PHP_ROUND_HALF_DOWN );
	}

	/**
	 * Pass in the total amount of a line on the order and this method will calculate
	 * what proportion of the order total it represents.
	 *
	 * @param OrderDetailsTotal $order_details_total $order_details_total total of the order details line.
	 *
	 * @return float
	 */
	private function get_order_detail_proportion_of_total( OrderDetailsTotal $order_details_total ): float {
		return $order_details_total->amount / $this->get_order_details_total();
	}

	/**
	 * Returns true if a split voucher with a matching tax code to the one passed in
	 * is found in the $split_coupons collection.
	 *
	 * @param int $tax_code The tax code to search split coupons for.
	 *
	 * @return bool
	 */
	private function split_coupons_contains_vat_code( int $tax_code ): bool {
		foreach ( $this->split_coupons as $value ) {
			if ( $value->tax_id == $tax_code ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Searches for a matching tax id in the order_details_totals collection.
	 * Returns the index if found, else returns -1 if not found.
	 *
	 * @param int $tax_id The tax code to search order details totals for.
	 *
	 * @return int
	 */
	private function order_details_totals_exists( int $tax_id ): int {
		$order_details_total_count = count( $this->order_details_totals );
		for ( $i = 0; $i < $order_details_total_count; $i ++ ) {
			if ( $this->order_details_totals[ $i ]->tax_id == $tax_id ) {
				return $i;
			}
		}

		return -1;
	}

	/**
	 * Adds data from the specified Easify order details object to the collection
	 * of $order_details_total. Each element in the collection maintains a
	 * running total of price for the tax code that it relates to.
	 *
	 * @param Easify_Order_Order_Details $order_detail Easify order detail containing price and tax info.
	 */
	private function add_order_detail_total_to_array( Easify_Order_Order_Details $order_detail ): void {
		// See if value already exists, if so update it...
		$existing_index = $this->order_details_totals_exists( $order_detail->TaxId );
		if ( $existing_index >= 0 ) {
			// Update.
			$this->order_details_totals[ $existing_index ]->amount += $order_detail->Price * $order_detail->Qty;
		} else {
			// Add new.
			$order_details_total         = new OrderDetailsTotal();
			$order_details_total->tax_id = $order_detail->TaxId;
			$order_details_total->amount = $order_detail->Price * $order_detail->Qty;
			array_push( $this->order_details_totals, $order_details_total );
		}
	}

	/**
	 * Totalises the prices * qty of all the order details and returns
	 * the total.
	 *
	 * @return float
	 */
	private function get_order_details_total(): float {
		$ret = 0;
		foreach ( $this->order_details_totals as $order_details_total ) {
			$ret += $order_details_total->amount;
		}

		return $ret;
	}

	/**
	 * Adds data from the specified Easify order details object to the collection
	 * of $split_coupon. Each element in the collection is used to determine the amount
	 * of each split voucher that is to be assigned to a particular tax code.
	 *
	 * @param Easify_Order_Order_Details $order_detail Easify order detail containing price and tax info.
	 */
	private function add_split_coupon_to_array( Easify_Order_Order_Details $order_detail ): void {
		if ( $this->split_coupons_contains_vat_code( $order_detail->TaxId ) ) {
			// array already contains tax rate - do nothing.
			return;
		}

		// Array doesn't yet contain tax rate add it.
		$split_coupon           = new SplitCoupon();
		$split_coupon->tax_id   = $order_detail->TaxId;
		$split_coupon->tax_rate = $order_detail->TaxRate;
		$split_coupon->code     = $this->coupon_code;
		array_push( $this->split_coupons, $split_coupon );
	}
}

class OrderDetailsTotal {
	/**
	 * @var float
	 */
	public $amount;

	/**
	 * @var int
	 */
	public $tax_id;
}

class SplitCoupon {
	/**
	 * @var float
	 */
	public $amount;

	/**
	 * @var int
	 */
	public $tax_id;

	/**
	 * @var float
	 */
	public $tax_rate;

	/**
	 * @var string
	 */
	public $code;
}
