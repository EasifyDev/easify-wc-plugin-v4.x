<?php

require __DIR__ . "/../../includes/class-easify-wc-coupon-splitter.php";
require __DIR__ . "/../../includes/class-easify-generic-easify-order-model.php";

use PHPUnit\Framework\TestCase;

class WooCommerceCouponSplitterTests extends TestCase {

	protected function setUp() : void {
	}

    protected function tearDown() : void {
    }

    public function testCtor_NotThrowException() {
        $sut = new Easify_WC_Coupon_Splitter;
        $this->assertInstanceOf("Easify_WC_Coupon_Splitter", $sut);
    }

    public function testSplitCoupons_PassedEmptyArray_ReturnsEmptyArray() {
        $sut = new Easify_WC_Coupon_Splitter;

        $easifyOrderDetails = array();
        $wooCommerceCoupons = array();

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertIsArray($ret);
        $this->assertEquals(0, sizeof($ret));
    }

    public function testSplitCoupons_PassedEasifyOrderDetailsAndCoupons_ReturnsNonEmptyArray() {
        $sut = new Easify_WC_Coupon_Splitter;

        $easifyOrderDetails = array($this->getEasifyOrderDetail(1, 20.0, 10.0, 1));

        $wooCommerceCoupons = array($this->getTestWooCommerceCoupon(1, 20, 10, "TEST"));

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertGreaterThanOrEqual(1, sizeof($ret));
    }

    public function testSplitCoupons_EasifyOrderDetailsOneVatCode_ReturnsSingleCouponProduct() {
        $sut = new Easify_WC_Coupon_Splitter;

        $easifyOrderDetails = array(
            $this->getEasifyOrderDetail(1, 20.0, 5.0, 1),
            $this->getEasifyOrderDetail(1, 20.0, 10.0, 1));

        $wooCommerceCoupons = array($this->getTestWooCommerceCoupon(1, 20, 10, "TEST"));

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertEquals(1, sizeof($ret));
        $this->assertInstanceOf("SplitCoupon", $ret[0]);
    }

    public function testSplitCoupons_EasifyOrderDetailsOneVatCode_NoWooCoupons_ReturnsEmptyArray() {
        $sut = new Easify_WC_Coupon_Splitter;

        $easifyOrderDetails = array(
            $this->getEasifyOrderDetail(1, 20.0, 5.0, 1),
            $this->getEasifyOrderDetail(1, 20.0, 10.0, 1));

        $wooCommerceCoupons = array();

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertIsArray($ret);
        $this->assertEquals(0, sizeof($ret));
    }

    public function testSplitCoupons_EasifyOrderDetailsTwoVatCodes_ReturnsTwoCouponProducts() {
        $sut = new Easify_WC_Coupon_Splitter;

        $easifyOrderDetails = array(
            $this->getEasifyOrderDetail(1, 20.0, 5.0, 1),
            $this->getEasifyOrderDetail(2, 5.0, 10.0, 1));

        $wooCommerceCoupons = array($this->getTestWooCommerceCoupon(1, 20, 10, "TEST"));

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertEquals(2, sizeof($ret));
        $this->assertInstanceOf("SplitCoupon", $ret[0]);
        $this->assertInstanceOf("SplitCoupon", $ret[1]);
    }

    public function testEasifyOrderDetailsOneVatCode_OneCoupon_ReturnsExpectedSplitCoupon() {
        $sut = new Easify_WC_Coupon_Splitter;

        $easifyOrderDetails = array(
            $this->getEasifyOrderDetail(1, 20.0, 5.0, 1),
            $this->getEasifyOrderDetail(1, 20.0, 10.0, 1));

        $wooCommerceCoupons = array($this->getTestWooCommerceCoupon(1, 20, 5, "TEST"));

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertEquals(1, $ret[0]->tax_id);
        $this->assertEquals(20, $ret[0]->tax_rate);
        $this->assertEquals(5, $ret[0]->amount);
        $this->assertEquals("TEST", $ret[0]->code);
    }

    public function testEasifyOrderDetailsTwoVatCodes_OneCoupon_ReturnsExpectedSplitCoupons() {
        $sut = new Easify_WC_Coupon_Splitter;
        $couponAmount = 10.0;

        $easifyOrderDetails = array(
            $this->getEasifyOrderDetail(1, 20.0, 90.0, 1),
            $this->getEasifyOrderDetail(2, 5.0, 10.0, 1));

        $wooCommerceCoupons = array($this->getTestWooCommerceCoupon(1, 20, $couponAmount, "TEST"));

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertEquals(1, $ret[0]->tax_id);
        $this->assertEquals(20, $ret[0]->tax_rate);
        $this->assertEquals(9, $ret[0]->amount);

        $this->assertEquals(2, $ret[1]->tax_id);
        $this->assertEquals(5, $ret[1]->tax_rate);
        $this->assertEquals(1, $ret[1]->amount);

        // Ensure split coupons add up to exact value of original coupon
        $this->assertEquals($couponAmount, $ret[0]->amount + $ret[1]->amount);
    }

    public function testEasifyOrderDetailsThreeVatCodes_OneCoupon_ReturnsExpectedSplitCoupons() {
        $sut = new Easify_WC_Coupon_Splitter;
        $couponAmount = 10.0;

        $easifyOrderDetails = array(
            $this->getEasifyOrderDetail(1, 20.0, 70.0, 1),
            $this->getEasifyOrderDetail(2, 5.0, 10.0, 1),
            $this->getEasifyOrderDetail(3, 10.0, 20.0, 1));

        $wooCommerceCoupons = array($this->getTestWooCommerceCoupon(1, 20, $couponAmount, "TEST"));

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertEquals(1, $ret[0]->tax_id);
        $this->assertEquals(20, $ret[0]->tax_rate);
        $this->assertEquals(7, $ret[0]->amount);

        $this->assertEquals(2, $ret[1]->tax_id);
        $this->assertEquals(5, $ret[1]->tax_rate);
        $this->assertEquals(1, $ret[1]->amount);

        $this->assertEquals(3, $ret[2]->tax_id);
        $this->assertEquals(10, $ret[2]->tax_rate);
        $this->assertEquals(2, $ret[2]->amount);

        // Ensure split coupons add up to exact value of original coupon
        $this->assertEquals($couponAmount, $ret[0]->amount + $ret[1]->amount + $ret[2]->amount);
    }


    public function testEasifyOrderDetailsTwoVatCodes_OneCoupon_CouponAmountRoundingCorrect() {
        $sut = new Easify_WC_Coupon_Splitter;
        $couponAmount = 10.0;

        $easifyOrderDetails = array(
            $this->getEasifyOrderDetail(1, 20.0, 100.0, 1),
            $this->getEasifyOrderDetail(2, 5.0, 10.0, 1));

        $wooCommerceCoupons = array($this->getTestWooCommerceCoupon(1, 20, $couponAmount, "TEST"));

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertEquals(1, $ret[0]->tax_id);
        $this->assertEquals(20, $ret[0]->tax_rate);
        $this->assertEquals(9.09, $ret[0]->amount);

        $this->assertEquals(2, $ret[1]->tax_id);
        $this->assertEquals(5, $ret[1]->tax_rate);
        $this->assertEquals(0.91, $ret[1]->amount);

        // Ensure split coupons add up to exact value of original coupon
        $this->assertEquals($couponAmount, $ret[0]->amount + $ret[1]->amount);
    }

    public function testEasifyOrderDetailsTwoVatCodes_MultipleLineItemQuantities_OneCoupon_ReturnsExpected() {
        $sut = new Easify_WC_Coupon_Splitter;
        $couponAmount = 2.1; // Ten percent of £21

        $easifyOrderDetails = array(
            $this->getEasifyOrderDetail(1, 5.0, 5, 3),
            $this->getEasifyOrderDetail(2, 20.0, 3, 2));

        $wooCommerceCoupons = array($this->getTestWooCommerceCoupon(1, 20, $couponAmount, "TEST"));

        $ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

        $this->assertEquals(1, $ret[0]->tax_id);
        $this->assertEquals(5, $ret[0]->tax_rate);
        $this->assertEquals(1.5, $ret[0]->amount);

        $this->assertEquals(2, $ret[1]->tax_id);
        $this->assertEquals(20, $ret[1]->tax_rate);
        $this->assertEquals(0.6, $ret[1]->amount);

        // Ensure split coupons add up to exact value of original coupon
        $this->assertEquals($couponAmount, $ret[0]->amount + $ret[1]->amount);
    }

	public function testEasifyOrderDetailsTwoVatCodes_ThreeLines_OneCoupon_ReturnsTwoSplitCoupons() {
		$sut = new Easify_WC_Coupon_Splitter;
		$couponAmount = 10.0;

		$easifyOrderDetails = array(
			$this->getEasifyOrderDetail(1, 20.0, 100.0, 1),
			$this->getEasifyOrderDetail(2, 5.0, 10.0, 1),
			$this->getEasifyOrderDetail(1, 20.0, 11.0, 5));

		$wooCommerceCoupons = array($this->getTestWooCommerceCoupon(1, 20, $couponAmount, "TEST"));

		$ret = $sut->split_coupons($easifyOrderDetails, $wooCommerceCoupons);

		$this->assertEquals(2, count($ret));
	}

    /***************************
     *** HELPERS
     ***************************/
    private function getTestWooCommerceCoupon(int $taxId, float $taxRate, float $amount, string $code) : WooCommerceTestCoupon
    {
        $coupon = new WooCommerceTestCoupon();
        $coupon->coupon_value = $amount;
        $coupon->coupon_code = $code;

        return $coupon;
    }

    private function getEasifyOrderDetail(int $taxId, float $taxRate, float $price, int $qty) : Easify_Order_Order_Details
    {
        $easifyOrderDetail = new Easify_Order_Order_Details();
        $easifyOrderDetail->Sku = 123456;
        $easifyOrderDetail->TaxId = $taxId;
        $easifyOrderDetail->TaxRate = $taxRate;
        $easifyOrderDetail->Price = $price;
        $easifyOrderDetail->Qty = $qty;

        return $easifyOrderDetail;
    }
}

class WooCommerceTestCoupon
{
    public $coupon_value;
    public $coupon_code;
}
