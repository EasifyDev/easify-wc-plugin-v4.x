<?php

class Easify_Generic_Easify_Order_Model{
    // Composed classes
    public $Customer; // Easify_Order_Customer
    public $OrderDetails; // Array of Easify_Order_Order_Details
    public $Payments; // Array of Easify_Order_Payments
    
    // Order class variables
    public $ExtOrderNo;
    public $ExtCustomerId;
    public $DatePlaced;
    public $StatusId;
    public $Paid;
    public $CustomerRef;
    public $Invoiced;
    public $DateInvoiced;
    public $Comments; // Required field
    public $Notes;
    public $DateOrdered;
    public $DatePaid;
    public $DueDate;
    public $DueTime;
    public $Scheduled;
    public $Duration;
    public $Priority;
    public $Recurring;
    public $RecurTimePeriod;
    public $UseTradeMargins;
    public $DueDate2;
    public $DueTime2;
    public $DueDuration2;
    public $OrderType;
    public $PaymentTermsId;
    
    
    public function __construct() {
        $this->Customer = new Easify_Order_Customer();
        $this->OrderDetails = array();
        $this->Payments = array();               
    }
}

    class Easify_Order_Customer{
        public $ExtCustomerId;
        public $CompanyName; // Customer must have CompanyName, FirstName or Surname
        public $Title;
        public $FirstName; // Customer must have CompanyName, FirstName or Surname
        public $Surname; // Customer must have CompanyName, FirstName or Surname
        public $JobTitle;
        public $Address1;
        public $Address2;
        public $Address3;
        public $Town;
        public $County;
        public $Postcode;
        public $Country;
        public $HomeTel;
        public $Email;
        public $DeliveryFirstName;
        public $DeliverySurname;
        public $DeliveryCompanyName;
        public $DeliveryAddress1;
        public $DeliveryAddress2;
        public $DeliveryAddress3;
        public $DeliveryTown;
        public $DeliveryCounty;
        public $DeliveryPostcode;
        public $DeliveryCountry;
        public $DeliveryTel;
        public $DeliveryEmail;
        public $SubscribeToNewsletter;
        public $TradeAccount;
        public $CreditLimit;
        public $CustomerTypeId;
        public $PaymentTermsId;
        public $RelationshipId;   
    }
    
    class Easify_Order_Order_Details{
        public $Sku; // Required field
        public $Qty;
        public $Price;
        public $Comments;
        public $TaxRate;
        public $TaxId;
        public $Spare;
        public $ExtParentId;
        public $ExtOrderDetailsId;
        public $ExtOrderNo;
        public $AutoAllocateStock;       
    }
    
    class Easify_Order_Payments{
        public $PaymentDate;
        public $PaymentAccountId;
        public $TransactionRef;
        public $PaymentMethodId;
        public $PaymentTypeId;
        public $ExtOrderNo;
        public $Comments; 
        public $Amount;       
    }
    
    
    
?>