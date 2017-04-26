<?php
class model extends CI_Model {

    public function __construct()
    {
        $this->load->database();
    }

    function get_data_product() {
        /*$this->db->query('BI_Emarsys_Product_Catalog_Init');
        $query = $this->db->query('BI_Emarsys_Product_Catalog');*/
        $query = $this->db->query('select * from BI_emarsys_product_feeds');
        
    	if ($query->num_rows() > 0) {
			return $query->result_array();
		}
    }

    function get_transaction_data() {
        $query = $this->db->query('select [order], [date], [customer], [item], [c_sales_amount], [quantity], [c_shopcart_flag] from BI_Emarsys_SalesLog where convert(date, dtmUpload) = convert(date, getdate())');
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }

    function get_transaction_data_temp() {
        $query = $this->db->query("select [order], [date], [customer], [item], [c_sales_amount], [quantity], [c_shopcart_flag] from BI_Emarsys_SalesLog where convert(date, dtmUpload) between '2016-11-04' and '2016-11-15'");
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }
    
    function get_new_contact() {
        $query = $this->db->query('select * from BI_Minute_New_Contact');
        
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }

    function get_update_contact() {
        $query = $this->db->query('select * from BI_LKPP_ContactList where flag = 2');
        
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }

    function post_log($data) {
        $this->db->insert('BI_Contact_Log_API', $data);
    }


    function get_data_product_v2() {
        $this->db->query('BI_Facebook_Product_Catalog_Init');
        $query = $this->db->query('BI_Facebook_Product_Catalog');
        
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }

    function get_google_feed() {
        $query = $this->db->query('select
            [g:id] as id
            , [g:title] as title
            , [g:description] as [description]
            , [g:product_type] as product_type
            , [g:link] as link
            , [g:image_link] as image_link
            , [g:condition] as condition
            , [g:price] as price
            , [g:sale_price] as sale_price
            , [g:sale_price_effective_date] as sale_price_effective_date
            , [g:brand] as brand
            , [g:availability] as [availability] 
        from 
            BI_Data.[dbo].BI_Google_Product_Feed');
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }

    function get_facebook_feed() {
        $query = $this->db->query('select
            [g:id] as id
            , [g:title] as title
            , [g:description] as [description]
            , [g:product_type] as product_type
            , [g:link] as link
            , [g:image_link] as image_link
            , [g:condition] as condition
            , [g:price] as price
            , [g:sale_price] as sale_price
            , [g:sale_price_effective_date] as sale_price_effective_date
            , [g:brand] as brand
            , [g:availability] as [availability]
        from
            BI_Data.[dbo].[BI_Facebook_Product_Feed]');
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }

    function get_facebook_feed_filter($where) {
        $query = $this->db->query("select
            [g:id] as id
            , [g:title] as title
            , [g:description] as [description]
            , [g:product_type] as product_type
            , [g:link] as link
            , [g:image_link] as image_link
            , [g:condition] as condition
            , [g:price] as price
            , [g:sale_price] as sale_price
            , [g:sale_price_effective_date] as sale_price_effective_date
            , [g:brand] as brand
            , [g:availability] as [availability]
        from
            BI_Data.[dbo].[BI_Facebook_Product_Feed]
        WHERE "
            .$where);
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }

    function get_facebook_feed_rep($replace) {
        $query = $this->db->query("select
            [g:id] as id
            , [g:title] as title
            , [g:description] as [description]
            , [g:product_type] as product_type
            , ".$replace." as link
            , [g:image_link] as image_link
            , [g:condition] as condition
            , [g:price] as price
            , [g:sale_price] as sale_price
            , [g:sale_price_effective_date] as sale_price_effective_date
            , [g:brand] as brand
            , [g:availability] as [availability]
        from
            BI_Data.[dbo].[BI_Facebook_Product_Feed]");
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }

    function get_facebook_feed_fr($w,$r) {
        $query = $this->db->query("select
            [g:id] as id
            , [g:title] as title
            , [g:description] as [description]
            , [g:product_type] as product_type
            , ".$r." as link
            , [g:image_link] as image_link
            , [g:condition] as condition
            , [g:price] as price
            , [g:sale_price] as sale_price
            , [g:sale_price_effective_date] as sale_price_effective_date
            , [g:brand] as brand
            , [g:availability] as [availability]
        from
            BI_Data.[dbo].[BI_Facebook_Product_Feed]
        WHERE "
            .$w);
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
    }

    //DA insert ke DB azure
    function post_log_ecoupon($tableName,$data) {
        $this->db->insert($tableName, $data);
    }

    //DA select ecoupon yang udah kepake
    function used_ecoupon(){
        $query = $this->db->query
        (
            "select
                PromoCode
            from
                azswd01.DOS2_CBN.dbo.bhx_OrderItem bhx_OrderItem
                inner join azswd01.DOS2_CBN.dbo.bhx_Order bhx_Order on bhx_Order.KodeTrx = bhx_OrderItem.KodeTrx
            where
                PromoCode != ''
                and left(PromoCode,13) <> 'BhinnekaPoint'
                and convert(date, OrderDate) =  dateadd(d, -1, convert(date, GETDATE()))
                and OrderStatus = 2
            group by
                PromoCode"
        );

        if($query->num_rows() > 0){
            return $query->result_array();
        }
    }

    //DA tarik data validity ecoupon dari azure
    function get_validty_ecoupon_data($name){
        $this->db->select('ecouponCode, validityPeriod, date_time');
        $this->db->where('name', $name);
        $query = $this->db->get('azswd01.BI_Data.dbo.BI_Emarsys_Validity_Period_Ecoupon');

        return $query->result_array();
    }

    function get_ecoupon_header($name){
        $query = $this->db->query
        (
            "select top 1
                eCouponID
            from
                BCOMREADWRITE.DOS2_HO.dbo.bhx_eCoupon
            where
                eCouponName = '".$name."'
                and convert(date, CreatorDateTime) = convert(date, getdate())
            order by
                eCouponID desc"
        );
        return $query->result_array();
    }

    function get_ecoupon_all_header(){
        $query = $this->db->query
        (
            "select top 1
                eCouponID
            from
                BCOMREADWRITE.DOS2_HO.dbo.bhx_eCoupon
            order by
                eCouponID desc"
        );
        return $query->result_array();
    }

    function get_ecoupon_scope(){
        $query = $this->db->query
        (
            "SELECT TOP 1
                eCouponScopeID
                ,eCouponID
            FROM
                BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponScope
            ORDER BY
                eCouponScopeID DESC"
        );

        if($query->num_rows() > 0){
            return $query->result_array();
        }
    }

    function update_ecoupon_code($dataUpdate, $idUpdate){
        $this->db->where('eCouponCode', $idUpdate);
        $this->db->update('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponCode', $dataUpdate);
    }

    function get_payment_method($id){
        $this->db->select('paymentCode');
        $this->db->where_in('idPayment', $id);
        $query = $this->db->get('azswd01.BI_Data.dbo.BI_eCouponPaymentMethod');

        return $query->result_array();
    }

    function get_payment_method_exclude($id){
        $this->db->select('paymentCode');
        $this->db->where_not_in('idPayment', $id);
        $query = $this->db->get('azswd01.BI_Data.dbo.BI_eCouponPaymentMethod');

        return $query->result_array();
    }

    function get_shipping_method($id){
        $this->db->select('shippingDesc');
        $this->db->where_in('idShipping', $id);
        $query = $this->db->get('azswd01.BI_Data.dbo.BI_eCouponShippingMethod');

        return $query->result_array();
    }

    function get_shipping_method_exclude($id){
        $this->db->select('shippingDesc');
        $this->db->where_not_in('idShipping', $id);
        $query = $this->db->get('azswd01.BI_Data.dbo.BI_eCouponShippingMethod');

        return $query->result_array();
    }
    
    function get_field_name($tableName){
        $query = $this->db->field_data($tableName);
        return $query;
    }
    
    function get_data_auto_regis(){
        $query = $this->db->query(
                'select * from azswd01.BI_Data.dbo.BI_DATA_AUTO_REGISTER dar
                where not exists(
                    select * from azswd01.BI_Data.dbo.BI_SURVEY_RECIPIENT sr where sr.email = dar.email)'
        );

        if($query->num_rows() > 0){
            return $query->result_array();
        }
    }
}