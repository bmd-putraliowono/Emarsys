<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require "application/libraries/suiteAPI.php";

define('EMARSYS_API_USERNAME', 'bhinneka002');
define('EMARSYS_API_SECRET', 'zSfh67AnQKcQK6HJZViO');


class Contact extends CI_Controller {
	function index() {
		$this->load->model('model');

		$new = $this->model->get_update_contact();

		$body = array();
		if (count($new) > 0) {
			foreach ($new as $value) {
				/*array_push($body, array(
					1 => $value['first name'],
					2 => $value['last name'],
					3 => $value['email'],
					4 => $value['date of birth'],
					5 => $value['gender'],
					31 => $value['opt-in'],
					46 => $value['salutation'],
					5805 => $value['signup source'],
					5806 => $value['create date'],
					8035 => $value['Bhinneka Point'],
					8036 => $value['Expiring Point'],
					8037 => $value['Expiring Date'],
					8038 => $value['Category']	
				));*/
				array_push($body, array(
					3 => $value['email'],
					31 => $value['opt-in'],
					8287 => $value['LKPP']
				));
			}
		}
		
		$req_body = array('contacts' => $body);
		$res = $this->_contact($req_body, 'PUT');
		$res = explode('charset=utf-8', $res);
		$ret = json_decode($res[1], true);
		
		$err_msg = $this->_generate_msg($ret['data']['errors']);

		$log = array(
				'replyCode' => $ret['replyCode'],
				'replyText' => $ret['replyText'],
				'ids' 		=> count($ret['data']['ids']) > 0 ? '' : implode(',', $ret['data']['ids']),
				'errors' 	=> $err_msg,
				'Date_Time' => date('Y-m-d H:i:s')
			);
		$this->model->post_log($log);
	}
	function getfields() {
        $res = $this->_field('GET');
        $res = explode('charset=utf-8', $res);
        $ret = json_decode($res[1], true);
        $coll = array();
        foreach ($ret['data'] as $key => $value) {
            if ($value['application_type'] == "voucher") {
                array_push($coll, array(
                    'id' => $value['id'],
                    'name' => $value['name']
                ));
            }
        }
		return $coll;
    }
	
	function updsegment() {
		$criteria = array();
		//10384
		//11515
		array_push($criteria, array(
			"type" => "criteria",
			"field" => 10384,
			"operator" => "not_empty"
		));

		$req_body = array(
			"type" => "and",
			"children" => $criteria
		);
		$res = $this->_segment('PUT', $req_body);
		print_r($res);
	}

	function getcriteria() {
		$res = $this->_segment('GET');
		print_r($res);
	}

	function getcontact() {
		$st = 0;

		while ($st == 0) {
			$res = $this->_contact_from_segment('GET');	
			$res = explode('charset=utf-8', $res);
			$ret = json_decode($res[1], true);
			if ($ret['replyCode'] == 0) {
				$st = 1;
			}
			sleep(3);
		}
		print_r($ret);
	}

    //get semua ecoupon yang ada di emarsys
    function  get_ecoupon_emarsys(){
        $fields = $this->getfields();
        $ecoupon = array();

        foreach($fields as $key => $value){
            $id = $value['id'];
            $response = $this->_ecoupon_emarsys('GET',$id);
            $response = explode('charset=utf-8', $response);
            $res = json_decode($response[1]);
            $countRes = count($res->data->result);
            if($countRes == 0)continue;
            foreach($res->data->result as $key2 => $value2){
                if ($value2->$id == "NULL")continue;
                $ecoupon[] = $value2->$id;
            }           
        }
       return($ecoupon);
    }

    //get semua ecoupon yang udah kepake
    function get_used_ecoupon(){
        $this->load->model('model');
        $ue = $this->model->used_ecoupon();
        $promocode = array();

        foreach($ue as $key => $value){
            $promocode[] = $value['PromoCode'];
        }
       return($promocode);
    }

    //update ecoupon di emarsys
    function update_ecoupon_emarsys(){
        $this->load->model('model');
        $ecBmd = $this->get_used_ecoupon();
        $field = array([
                "id" => "17585"
                , "name" => "optinmonster17585"
            ],
            [
                "id" => "17586"
                , "name" => "optinmonster17586"
            ]);
        $resQueryContact = array();
        $rules = array();

        //get data2 yang diperlukan untuk update
        foreach($field as $value){
            $fl = $value['id'];
            $el = 3;
            $fi = 'id';
            $nf = $value['name'];
            $fu = 16532;
            $req_body = array(
                "keyId" => $fl,
                "keyValues" => $ecBmd,
                "fields" => [$el,$fl,$fu]
            );

            $res = $this->_query_contacts($req_body, 'POST');
            $res = explode('charset=utf-8', $res);
            $ret = json_decode($res[1]);
            if(!empty($ret->data->result)) {
                foreach ($ret->data->result as $key => $value2) {
                    $resQueryContact[] = array(
                        'id' => $value2->$fi,
                        'email' => $value2->$el,
                        'ecoupon' =>  $value2->$fl,
                        'idField' => $fl,
                        'nameField' => $nf,
                        'usedEcoupon' => $value2->$fu
                    );
                }
            }
        }

        //buat array data update
        for($n = 0; $n < count($resQueryContact); $n++) {
            $ue = $resQueryContact[$n]['usedEcoupon'];
            $ifs = $resQueryContact[$n]['idField'];
            $usedVoucher = "";
            if ($ue != "") {
                $usedVoucher = $ue." | ".$ifs;
            } else {
                $usedVoucher = $ifs;
            }        
            array_push($rules, array(
                3 => $resQueryContact[$n]['email'],
                16532 => $usedVoucher
            ));
        }
        //update emarsys
        $req_body = array('contacts' => $rules);
        $res = $this->_contact($req_body, 'PUT');
        $res = explode('charset=utf-8', $res);
        $ret = json_decode($res[1], true);
            
        //input log update ke BMD
        if(!empty($ret['data']['ids'])){
            foreach ($ret['data']['ids'] as $value){
                foreach($resQueryContact as $key => $val){
                    if($value != $val['id']) continue;
                    $logIds = array(
                        'id' => $val['id'],
                        'email' => $val['email'],
                        'ecouponCode' => $val['ecoupon'],
                        'idFieldEcoupon' => $val['idField'],
                        'ecouponType'   => $val['nameField'],
                        'errorMsg'  => '',
                        'date_time' => date('Y-m-d H:i:s')
                    );
                    $this->model->post_log_ecoupon('azswd01.BI_Data.dbo.BI_Emarsys_Update_Ecoupon_Log',$logIds);
                }
            }
        }
        if(!empty($ret['data']['errors'])){
            $error = $ret['data']['errors'];
            $a=0;
            $errMsg = array();
            foreach ($error as $key) {
                $errMsg[$a] = array_keys($error)[$a]." : ";
                $b=0;
                foreach ($key as $value) {
                    $errMsg[$a] = $errMsg[$a].array_keys($key)[$b]."-";
                    $errMsg[$a] = count($value) > 1? $errMsg[$a].$value."," : $errMsg[$a].$value;
                    $b++;
                }
                foreach ($resQueryContact as $item => $val) {
                    $errKey = array_keys($error)[$a];
                    if($errKey != $val['email']) continue;
                    $logErr = array(
                        'id' => $val['id'],
                        'email' => $val['email'],
                        'ecouponCode' => $val['ecoupon'],
                        'idFieldEcoupon' => $val['idField'],
                        'ecouponType' => $val['nameField'],
                        'errorMsg' => $errMsg[$a],
                        'date_time' => date('Y-m-d H:i:s')
                    );
                    $this->model->post_log_ecoupon('azswd01.BI_Data.dbo.BI_Emarsys_Update_Ecoupon_Log',$logErr);
                }
                $a++;
            }
        } 
    }

    public function create_header_ecoupon(){
        $this->load->helper('url');
        $this->load->model('model');
        $ecAndExp = array();
        $arDiffDb = array();
        $arDiffEm = array();

        //ketentuan header
        $query = array(
            'idFields' => $this->uri->segment(3),
            'validityPeriod' => $this->uri->segment(4),
            'discValue' => $this->uri->segment(5),
            'minValue' => $this->uri->segment(6),
            'name' => $this->uri->segment(7),
            'desc' => $this->uri->segment(8),
            'offset' => $this->uri->segment(9)
        );

        //get ecoupon code dr emarsys sesuai ketentutan header
        $id = $query['idFields'];
        $offset = $query['offset'];
        $res = $this->_ecoupon_emarsys('GET',$id, $offset);
        $res = explode('charset=utf-8', $res);
        $ec = json_decode($res[1]);
        $dbData = $this->model->get_validty_ecoupon_data($query['name']);
        foreach($ec->data->result as $key2 => $val){
            $ecAndExp[] = array(
                'ecouponCode' => $val->$id,
                'validityPeriod' => $query['validityPeriod'] + 1,
                'name' => $query['name'],
                'desc' => $query['desc'],
                'discValue' => $query['discValue'],
                'minValue' => $query['minValue'],
                'date_time' => date('Y-m-d H:i:s')
            );
            $arDiffEm[] = $val->$id;
        }

        //get ecoupon code yang sudah pernah dibuatkan header dr BMD
        if (count($dbData) > 0) {
            foreach ($dbData as $key => $value){
                $arDiffDb[] = $value['ecouponCode'];
            }
        }

        //bandingin ecoupon yang sudah ada header dengan belum dan ambil yang belum ada headernya
        $result = array_diff($arDiffEm, $arDiffDb);

        //proses buat header
        foreach ($ecAndExp as $item => $value){
            foreach($result as $val){
                $id = $value['ecouponCode'];
                if($id != $val) continue;
                $name = $value['name'];
                $startEc = date_format(date_create($value['date_time']), 'Y-m-d');
                $endEc = date_format(date_add(date_create($value['date_time']),date_interval_create_from_date_string($value['validityPeriod'].'days')), 'Y-m-d');
                $prefixEc = 'ECO'.date_format(date_create($startEc), 'ym');
                $header = $this->model->get_ecoupon_header($name);
                $ecouponId = '';
                if (!empty($header)) {
                    $ecouponId = $header['0']['eCouponID'];
                }
                else{
                    $headAll = $this->model->get_ecoupon_all_header();
                    
                    if (!empty($headAll)) {
                        $prefixHd = substr($headAll['0']['eCouponID'], 0, 7);
                        $idHd = substr($headAll['0']['eCouponID'], 7, 5);
                        $no = str_pad($idHd + 1, 5, 0, STR_PAD_LEFT);
                        $ecouponId = $prefixHd.$no;
                    }
                    else{
                        $ecouponId = $prefixEc.'00001';
                    }
                    
                    $dataHeader = array(
                                        'eCouponID' => $ecouponId,
                                        'eCouponName' => $value['name'],
                                        'Description'  => $value['desc'],
                                        'DiscountType'  => 0,
                                        'DiscountCurrencyID' => 'CUR01',
                                        'DiscountPriceValue' => $value['discValue'],
                                        'MaximumDiscountCurrencyID' => 'CUR01',
                                        'MaximumDiscountValue' => 0,
                                        'MinimumItemPriceCurrencyID' => 'CUR01',
                                        'MinimumItemPriceValue' => $value['minValue'],
                                        'MinimumTransactionPriceCurrencyID' => 'CUR01',
                                        'MinimumTransactionPriceValue' => 0,
                                        'StartDate' => $startEc,
                                        'EndDate' => $endEc,
                                        'EmailCc' => 'andy.putra@bhinneka.com, putra.liowono@bhinneka.com',
                                        'Limit' => 1,
                                        'MaxQtyPerProduct' => 1,
                                        'MaxOrderPerPerson' => 0,
                                        'UseAdvancedConstraintsForMaxOrderPerPerson' => 0,
                                        'ResetMaxOrderPerPersonAfter' => 0,
                                        'MaxSKUPerOrder' => 0,
                                        'IsValidWithSpecialPromo' => 1,
                                        'IsValidForAppsOnly' => 0,
                                        'Enabled' => 1,
                                        'CreatorID' => 'USR100800004',
                                        'CreatorIP' => null,
                                        'CreatorDateTime' => date('Y-m-d H:i:s'),
                                        'EditorID' => null,
                                        'EditorIP' => null,
                                        'EditorDateTime' => null
                                        );
                    $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCoupon', $dataHeader);

                    $payment = array(
                    'eCouponID' => $ecouponId,
                    'PaymentMethod' => '0',
                    'SubPaymentMethod' => ''
                    );
                    $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponPaymentMethod', $payment);
                    $ship = array(
                        'eCouponID' => $ecouponId,
                        'ShippingMethod' => "ALL"
                    );
                    $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponShippingMethod', $ship);
                    $scopeDb = $this->model->get_ecoupon_scope();
                    if ($ecouponId != $scopeDb['0']['eCouponID']) {
                        $prefixSc = substr($scopeDb['0']['eCouponScopeID'], 0, 7);
                        $idHd = substr($scopeDb['0']['eCouponScopeID'], 7, 5);
                        $noScope = str_pad($idHd + 1, 5, 0, STR_PAD_LEFT);
                        $idScope = $prefixSc.$noScope;
                        $scope = array(
                        'eCouponScopeID' => $idScope,
                        'eCouponID' => $ecouponId,
                        'Type' => '-1',
                        'TypeID' => null,
                        'TypeName' => null,
                        'CreatorID' => 'APIEmarsys',
                        'CreatorIP' => '::1',
                        'CreatorDateTime' => $value['date_time'],
                        'EditorID' => null,
                        'EditorIP' => null,
                        'EditorDateTime' => null,
                    );
                    $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponScope', $scope);
                    }
                }
                $dataCode = array(
                    'eCouponID' => $ecouponId,
                    'EditorDateTime' => date('Y-m-d H:i:s')
                    );
                $this->model->update_ecoupon_code($dataCode, $id);
                $data = array(
                    'ecouponCode' => $value['ecouponCode'],
                    'validityPeriod' => $value['validityPeriod'],
                    'name' => $value['name'],
                    'desc' => $value['desc'],
                    'discValue' => $value['discValue'],
                    'minValue' => $value['minValue'],
                    'date_time' => $value['date_time']
                );
                $this->model->post_log_ecoupon('azswd01.BI_Data.dbo.BI_Emarsys_Validity_Period_Ecoupon', $data);
            }
        }
    }

    public function create_header_ecoupon_v2(){
        $this->load->helper('url');
        $this->load->model('model');
        $ecAndExp = array();
        $arDiffDb = array();
        $arDiffEm = array();

        //ketentuan header
        $query = array(
            'idFields' => $this->uri->segment(3),
            'validityPeriod' => $this->uri->segment(4),
            'discValue' => $this->uri->segment(5),
            'minValue' => $this->uri->segment(6),
            'name' => $this->uri->segment(7),
            'desc' => $this->uri->segment(8),
            'offset' => $this->uri->segment(9)
        );

        //variable penampung ketentuan payment, shipping, & type ecoupon
        parse_str(substr(strrchr($_SERVER['REQUEST_URI'], "?"), 1), $_GET);
        $pay = $_GET['payment'];
        $payMethod = explode(',',$pay);
        $ship = $_GET['shipping'];
        $shipMethod = explode(',',$ship);
        $vType = $_GET['type'];
        $expType = explode(',',$vType);
        $discType = $expType[0];
        $discCurrID = $discType == '1'? '' : 'CUR01';
        $maxDiscCurrID = $discType == '1'? 'CUR01' : '';
        $maxDiscVal = $discType == '1'? $expType[1] : 0;

        //validasi ALL di shipping dan payment method
        if(in_array(99,$shipMethod) && count($shipMethod) > 1) {
            $errShip = array(
                'log' => "When you choosing ALL, you cannot choose another shipping methods",
                'flag' => 0,
                'dateTime' => date('Y-m-d H:i:s')
            );
            $this->model->post_log_ecoupon('AZSWD01.BI_Data.dbo.BI_eCoupon_pay_and_ship_log', $errShip);
            exit;
        }
        if(in_array(99,$payMethod) && count($payMethod) > 1){
            $errPay = array(
                'log' => "When you choosing ALL, you cannot choose another payment methods",
                'flag' => 0,
                'dateTime' => date('Y-m-d H:i:s')
            );
            $this->model->post_log_ecoupon('AZSWD01.BI_Data.dbo.BI_eCoupon_pay_and_ship_log', $errPay);
            exit;
        }
        $flagPay = strpos($payMethod[0],'-');
        foreach($payMethod as $item){
            $fp = strpos($item,'-');
            if($fp !== $flagPay){
                $errPay = array(
                    'log' => "Wrong type payment method",
                    'flag' => 0,
                    'dateTime' => date('Y-m-d H:i:s')
                );
                $this->model->post_log_ecoupon('AZSWD01.BI_Data.dbo.BI_eCoupon_pay_and_ship_log', $errPay);
                exit;
            }
        }
        if ($flagPay !== false){
            $typePay = 'payment exclude';
        }else{
            $typePay = 'payment include';
        }
        $flagShip = strpos($shipMethod[0],'-');
        foreach($shipMethod as $value){
            $fs = strpos($value,'-');
            if($fs !== $flagShip){
                $errShip = array(
                    'log' => "Wrong type shipping method",
                    'flag' => 0,
                    'dateTime' => date('Y-m-d H:i:s')
                );
                $this->model->post_log_ecoupon('AZSWD01.BI_Data.dbo.BI_eCoupon_pay_and_ship_log', $errShip);
                exit;
            }
        }
        if ($flagShip !== false){
            $typeShip = 'shipping exclude';
        }else{
            $typeShip = 'shipping include';
        }

        //select payment & shipping method dr DB
        if($typePay == 'payment exclude'){
            $pc = array();
            foreach($payMethod as $item){
                $pc[] = substr($item,1);
            }
            $pc[] = '99';
            $dbPayment = $this->model->get_payment_method_exclude($pc);
        }else{
            $dbPayment = $this->model->get_payment_method($payMethod);
        }
        if($typeShip == 'shipping exclude'){
            $sc = array();
            foreach($shipMethod as $item){
                $sc[] = substr($item,1);
            }
            $sc[] = '99';
            $dbShip = $this->model->get_shipping_method_exclude($sc);
        }else{
            $dbShip = $this->model->get_shipping_method($shipMethod);
        }

        //get ecoupon code dr emarsys sesuai ketentutan header
        $id = $query['idFields'];
        $offset = $query['offset'];
        $res = $this->_ecoupon_emarsys('GET',$id, $offset);
        $res = explode('charset=utf-8', $res);
        $ec = json_decode($res[1]);
        $dbData = $this->model->get_validty_ecoupon_data($query['name']);
        foreach($ec->data->result as $key2 => $val){
            $ecAndExp[] = array(
                'ecouponCode' => $val->$id,
                'validityPeriod' => $query['validityPeriod'] + 1,
                'name' => $query['name'],
                'desc' => $query['desc'],
                'discValue' => $query['discValue'],
                'minValue' => $query['minValue'],
                'date_time' => date('Y-m-d H:i:s')
            );
            $arDiffEm[] = $val->$id;
        }

        //get ecoupon code yang sudah pernah dibuatkan header dr BMD
        if (count($dbData) > 0) {
            foreach ($dbData as $key => $value){
                $arDiffDb[] = $value['ecouponCode'];
            }
        }

        //bandingin ecoupon yang sudah ada header dengan belum dan ambil yang belum ada headernya
        $result = array_diff($arDiffEm, $arDiffDb);

        //proses buat header, payment, shipping, scope, & update log
        foreach ($ecAndExp as $item => $value){
            foreach($result as $val){
                $id = $value['ecouponCode'];
                if($id != $val) continue;
                $name = $value['name'];
                $startEc = date_format(date_create($value['date_time']), 'Y-m-d');
                $endEc = date_format(date_add(date_create($value['date_time']),date_interval_create_from_date_string($value['validityPeriod'].'days')), 'Y-m-d');
                $prefixEc = 'ECO'.date_format(date_create($startEc), 'ym');
                $header = $this->model->get_ecoupon_header($name);
                $ecouponId = '';
                if (!empty($header)) {
                    $ecouponId = $header['0']['eCouponID'];
                }
                else{
                    $headAll = $this->model->get_ecoupon_all_header();
                    
                    if (!empty($headAll)) {
                        $prefixHd = substr($headAll['0']['eCouponID'], 0, 7);
                        $idHd = substr($headAll['0']['eCouponID'], 7, 5);
                        $no = str_pad($idHd + 1, 5, 0, STR_PAD_LEFT);
                        $ecouponId = $prefixHd.$no;
                    }
                    else{
                        $ecouponId = $prefixEc.'00001';
                    }
                    
                    $dataHeader = array(
                                        'eCouponID' => $ecouponId,
                                        'eCouponName' => $value['name'],
                                        'Description'  => $value['desc'],
                                        'DiscountType'  => $discType,
                                        'DiscountCurrencyID' => $discCurrID,
                                        'DiscountPriceValue' => $value['discValue'],
                                        'MaximumDiscountCurrencyID' => $maxDiscCurrID,
                                        'MaximumDiscountValue' => $maxDiscVal,
                                        'MinimumItemPriceCurrencyID' => 'CUR01',
                                        'MinimumItemPriceValue' => $value['minValue'],
                                        'MinimumTransactionPriceCurrencyID' => 'CUR01',
                                        'MinimumTransactionPriceValue' => 0,
                                        'StartDate' => $startEc,
                                        'EndDate' => $endEc,
                                        'EmailCc' => 'andy.putra@bhinneka.com, putra.liowono@bhinneka.com, jessica.kurniawan@bhinneka.com',
                                        'Limit' => 1,
                                        'MaxQtyPerProduct' => 1,
                                        'MaxOrderPerPerson' => 0,
                                        'UseAdvancedConstraintsForMaxOrderPerPerson' => 0,
                                        'ResetMaxOrderPerPersonAfter' => 0,
                                        'MaxSKUPerOrder' => 0,
                                        'IsValidWithSpecialPromo' => 1,
                                        'IsValidForAppsOnly' => 0,
                                        'Enabled' => 1,
                                        'CreatorID' => 'USR100800004',
                                        'CreatorIP' => null,
                                        'CreatorDateTime' => date('Y-m-d H:i:s'),
                                        'EditorID' => null,
                                        'EditorIP' => null,
                                        'EditorDateTime' => null
                                        );
                    $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCoupon', $dataHeader);

                    foreach($dbPayment as $pm) {
                        $exPm = explode('_',$pm['paymentCode']);
                        $subPm = count($exPm) > 1 ? $exPm[1] : ' ';
                        $payment = array(
                            'eCouponID' => $ecouponId,
                            'PaymentMethod' => $exPm[0],
                            'SubPaymentMethod' => $subPm
                        );
                        $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponPaymentMethod', $payment);
                    }

                    foreach($dbShip as $sm) {
                        $ship = array(
                            'eCouponID' => $ecouponId,
                            'ShippingMethod' => $sm['shippingDesc']
                        );
                        $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponShippingMethod', $ship);
                    }

                    $scopeDb = $this->model->get_ecoupon_scope();
                    if ($ecouponId != $scopeDb['0']['eCouponID']) {
                        $prefixSc = substr($scopeDb['0']['eCouponScopeID'], 0, 7);
                        $idHd = substr($scopeDb['0']['eCouponScopeID'], 7, 5);
                        $noScope = str_pad($idHd + 1, 5, 0, STR_PAD_LEFT);
                        $idScope = $prefixSc.$noScope;
                        $scope = array(
                        'eCouponScopeID' => $idScope,
                        'eCouponID' => $ecouponId,
                        'Type' => '-1',
                        'TypeID' => null,
                        'TypeName' => null,
                        'CreatorID' => 'APIEmarsys',
                        'CreatorIP' => '::1',
                        'CreatorDateTime' => $value['date_time'],
                        'EditorID' => null,
                        'EditorIP' => null,
                        'EditorDateTime' => null,
                    );
                    $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponScope', $scope);
                    }
                }
                $dataCode = array(
                    'eCouponID' => $ecouponId,
                    'EditorDateTime' => date('Y-m-d H:i:s')
                );
                $this->model->update_ecoupon_code($dataCode, $id);
                $data = array(
                    'ecouponCode' => $value['ecouponCode'],
                    'validityPeriod' => $value['validityPeriod'],
                    'name' => $value['name'],
                    'desc' => $value['desc'],
                    'discValue' => $value['discValue'],
                    'minValue' => $value['minValue'],
                    'date_time' => $value['date_time']
                );
                $this->model->post_log_ecoupon('azswd01.BI_Data.dbo.BI_Emarsys_Validity_Period_Ecoupon', $data);
            }
        }
    }

    public function create_header_ecoupon_v3(){
        $this->load->helper('url');
        $this->load->model('model');
        $ecAndExp = array();
        $arDiffDb = array();
        $arDiffEm = array();

        //ketentuan header
        $query = array(
            'idFields' => $this->uri->segment(3),
            'validityPeriod' => $this->uri->segment(4),
            'discValue' => $this->uri->segment(5),
            'minValue' => $this->uri->segment(6),
            'name' => $this->uri->segment(7),
            'desc' => $this->uri->segment(8),
            'offset' => $this->uri->segment(9)
        );

        //variable penampung ketentuan payment, shipping, type ecoupon, & min item or min transaction
        parse_str(substr(strrchr($_SERVER['REQUEST_URI'], "?"), 1), $_GET);
        if(empty($_GET)){
            $pay = 99;
            $ship = 99;
            $vType = 0;
            $min = 0;
        }else{
            $pay = empty($_GET['payment']) ? 99 : $_GET['payment'];
            $ship = empty($_GET['shipping']) ? 99 : $_GET['shipping'];
            $vType = empty($_GET['type']) ? 0 : $_GET['type']; // 0 = discount with value, (1,value) = discount with percent
            $min = empty($_GET['min']) ? 0 : $_GET['min']; // 0 = min item value, 1 = min transaction value
        }

        $payMethod = explode(',',$pay);
        $shipMethod = explode(',',$ship);
        $expType = explode(',',$vType);
        $discType = $expType[0];
        $discCurrID = $discType == '1'? '' : 'CUR01';
        $maxDiscCurrID = $discType == '1'? 'CUR01' : '';
        $maxDiscVal = $discType == '1'? $expType[1] : 0;

        //validasi ALL di shipping dan payment method
        if(in_array(99,$shipMethod) && count($shipMethod) > 1) {
            $errShip = array(
                'log' => "When you choosing ALL, you cannot choose another shipping methods",
                'flag' => 0,
                'dateTime' => date('Y-m-d H:i:s')
            );
            $this->model->post_log_ecoupon('AZSWD01.BI_Data.dbo.BI_eCoupon_pay_and_ship_log', $errShip);
            exit;
        }
        if(in_array(99,$payMethod) && count($payMethod) > 1){
            $errPay = array(
                'log' => "When you choosing ALL, you cannot choose another payment methods",
                'flag' => 0,
                'dateTime' => date('Y-m-d H:i:s')
            );
            $this->model->post_log_ecoupon('AZSWD01.BI_Data.dbo.BI_eCoupon_pay_and_ship_log', $errPay);
            exit;
        }
        $flagPay = strpos($payMethod[0],'-');
        foreach($payMethod as $item){
            $fp = strpos($item,'-');
            if($fp !== $flagPay){
                $errPay = array(
                    'log' => "Wrong type payment method",
                    'flag' => 0,
                    'dateTime' => date('Y-m-d H:i:s')
                );
                $this->model->post_log_ecoupon('AZSWD01.BI_Data.dbo.BI_eCoupon_pay_and_ship_log', $errPay);
                exit;
            }
        }
        if ($flagPay !== false){
            $typePay = 'payment exclude';
        }else{
            $typePay = 'payment include';
        }
        $flagShip = strpos($shipMethod[0],'-');
        foreach($shipMethod as $value){
            $fs = strpos($value,'-');
            if($fs !== $flagShip){
                $errShip = array(
                    'log' => "Wrong type shipping method",
                    'flag' => 0,
                    'dateTime' => date('Y-m-d H:i:s')
                );
                $this->model->post_log_ecoupon('AZSWD01.BI_Data.dbo.BI_eCoupon_pay_and_ship_log', $errShip);
                exit;
            }
        }
        if ($flagShip !== false){
            $typeShip = 'shipping exclude';
        }else{
            $typeShip = 'shipping include';
        }

        //select payment & shipping method dr DB
        if($typePay == 'payment exclude'){
            $pc = array();
            foreach($payMethod as $item){
                $pc[] = substr($item,1);
            }
            $pc[] = '99';
            $dbPayment = $this->model->get_payment_method_exclude($pc);
        }else{
            $dbPayment = $this->model->get_payment_method($payMethod);
        }
        if($typeShip == 'shipping exclude'){
            $sc = array();
            foreach($shipMethod as $item){
                $sc[] = substr($item,1);
            }
            $sc[] = '99';
            $dbShip = $this->model->get_shipping_method_exclude($sc);
        }else{
            $dbShip = $this->model->get_shipping_method($shipMethod);
        }

        //get ecoupon code dr emarsys sesuai ketentutan header
        $id = $query['idFields'];
        $offset = $query['offset'];
        $res = $this->_ecoupon_emarsys('GET',$id, $offset);
        $res = explode('charset=utf-8', $res);
        $ec = json_decode($res[1]);
        $dbData = $this->model->get_validty_ecoupon_data($query['name']);
        foreach($ec->data->result as $key2 => $val){
            $ecAndExp[] = array(
                'ecouponCode' => $val->$id,
                'validityPeriod' => $query['validityPeriod'] + 1,
                'name' => $query['name'],
                'desc' => $query['desc'],
                'discValue' => $query['discValue'],
                'minValue' => $query['minValue'],
                'date_time' => date('Y-m-d H:i:s')
            );
            $arDiffEm[] = $val->$id;
        }

        //get ecoupon code yang sudah pernah dibuatkan header dr BMD
        if (count($dbData) > 0) {
            foreach ($dbData as $key => $value){
                $arDiffDb[] = $value['ecouponCode'];
            }
        }

        //bandingin ecoupon yang sudah ada header dengan belum dan ambil yang belum ada headernya
        $result = array_diff($arDiffEm, $arDiffDb);

        //proses buat header, payment, shipping, scope, & update log
        foreach ($ecAndExp as $item => $value){
            foreach($result as $val){
                $id = $value['ecouponCode'];
                if($id != $val) continue;
                $name = $value['name'];
                $startEc = date_format(date_create($value['date_time']), 'Y-m-d');
                $endEc = date_format(date_add(date_create($value['date_time']),date_interval_create_from_date_string($value['validityPeriod'].'days')), 'Y-m-d');
                $prefixEc = 'ECO'.date_format(date_create($startEc), 'ym');
                $header = $this->model->get_ecoupon_header($name);
                $ecouponId = '';
                if (!empty($header)) {
                    $ecouponId = $header['0']['eCouponID'];
                }
                else{
                    $headAll = $this->model->get_ecoupon_all_header();
                    
                    if (!empty($headAll)) {
                        $prefixHd = substr($headAll['0']['eCouponID'], 0, 7);
                        $idHd = substr($headAll['0']['eCouponID'], 7, 5);
                        $no = str_pad($idHd + 1, 5, 0, STR_PAD_LEFT);
                        $ecouponId = $prefixHd.$no;
                    }
                    else{
                        $ecouponId = $prefixEc.'00001';
                    }
                    
                    $dataHeader = array(
                                        'eCouponID' => $ecouponId,
                                        'eCouponName' => $value['name'],
                                        'Description'  => $value['desc'],
                                        'DiscountType'  => $discType,
                                        'DiscountCurrencyID' => $discCurrID,
                                        'DiscountPriceValue' => $value['discValue'],
                                        'MaximumDiscountCurrencyID' => $maxDiscCurrID,
                                        'MaximumDiscountValue' => $maxDiscVal,
                                        'MinimumItemPriceCurrencyID' => 'CUR01',
                                        'MinimumItemPriceValue' => $min == 0 ? $value['minValue'] : 0,
                                        'MinimumTransactionPriceCurrencyID' => 'CUR01',
                                        'MinimumTransactionPriceValue' => $min == 1 ? $value['minValue'] : 0,
                                        'StartDate' => $startEc,
                                        'EndDate' => $endEc,
                                        'EmailCc' => 'andy.putra@bhinneka.com, putra.liowono@bhinneka.com, jessica.kurniawan@bhinneka.com',
                                        'Limit' => 1,
                                        'MaxQtyPerProduct' => 1,
                                        'MaxOrderPerPerson' => 0,
                                        'UseAdvancedConstraintsForMaxOrderPerPerson' => 0,
                                        'ResetMaxOrderPerPersonAfter' => 0,
                                        'MaxSKUPerOrder' => 0,
                                        'IsValidWithSpecialPromo' => 1,
                                        'IsValidForAppsOnly' => 0,
                                        'Enabled' => 1,
                                        'CreatorID' => 'USR100800004',
                                        'CreatorIP' => null,
                                        'CreatorDateTime' => date('Y-m-d H:i:s'),
                                        'EditorID' => null,
                                        'EditorIP' => null,
                                        'EditorDateTime' => null
                                        );
                    $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCoupon', $dataHeader);
                    //$this->model->post_log_ecoupon('AZSWD01.DOS2_CBN.dbo.bhx_eCoupon_1', $dataHeader);

                    foreach($dbPayment as $pm) {
                        $exPm = explode('_',$pm['paymentCode']);
                        $subPm = count($exPm) > 1 ? $exPm[1] : ' ';
                        $payment = array(
                            'eCouponID' => $ecouponId,
                            'PaymentMethod' => $exPm[0],
                            'SubPaymentMethod' => $subPm
                        );
                        $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponPaymentMethod', $payment);
                        //$this->model->post_log_ecoupon('AZSWD01.DOS2_CBN.dbo.bhx_eCouponPaymentMethod_1', $payment);
                    }

                    foreach($dbShip as $sm) {
                        $ship = array(
                            'eCouponID' => $ecouponId,
                            'ShippingMethod' => $sm['shippingDesc']
                        );
                        $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponShippingMethod', $ship);
                        //$this->model->post_log_ecoupon('AZSWD01.DOS2_CBN.dbo.bhx_eCouponShippingMethod_1', $ship);
                    }

                    $scopeDb = $this->model->get_ecoupon_scope();
                    if ($ecouponId != $scopeDb['0']['eCouponID']) {
                        $prefixSc = substr($scopeDb['0']['eCouponScopeID'], 0, 7);
                        $idHd = substr($scopeDb['0']['eCouponScopeID'], 7, 5);
                        $noScope = str_pad($idHd + 1, 5, 0, STR_PAD_LEFT);
                        $idScope = $prefixSc.$noScope;
                        $scope = array(
                            'eCouponScopeID' => $idScope,
                            'eCouponID' => $ecouponId,
                            'Type' => '-1',
                            'TypeID' => null,
                            'TypeName' => null,
                            'CreatorID' => 'APIEmarsys',
                            'CreatorIP' => '::1',
                            'CreatorDateTime' => $value['date_time'],
                            'EditorID' => null,
                            'EditorIP' => null,
                            'EditorDateTime' => null,
                        );
                    $this->model->post_log_ecoupon('BCOMREADWRITE.DOS2_HO.dbo.bhx_eCouponScope', $scope);
                    //$this->model->post_log_ecoupon('AZSWD01.DOS2_CBN.dbo.bhx_eCouponScope_1', $scope);
                    }
                }
                $dataCode = array(
                    'eCouponID' => $ecouponId,
                    'EditorDateTime' => date('Y-m-d H:i:s')
                );
                $this->model->update_ecoupon_code($dataCode, $id);
                $data = array(
                    'ecouponCode' => $value['ecouponCode'],
                    'validityPeriod' => $value['validityPeriod'],
                    'name' => $value['name'],
                    'desc' => $value['desc'],
                    'discValue' => $value['discValue'],
                    'minValue' => $value['minValue'],
                    'date_time' => $value['date_time']
                );
                $this->model->post_log_ecoupon('azswd01.BI_Data.dbo.BI_Emarsys_Validity_Period_Ecoupon', $data);
                //$this->model->post_log_ecoupon('azswd01.BI_Data.dbo.BI_Emarsys_Validity_Period_Ecoupon_1', $data);
            }
        }
    }

    function _ecoupon_emarsys($type, $idField, $offset){
        $handshake = new SuiteApi(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);
        $res = $handshake->send($type, "contact/query/return=".$idField."&offset=".$offset."&excludeempty=true");

        return $res;
    }

    function _query_contacts($req_body, $type){
        $handshake = new SuiteApi(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);
        $req_body = json_encode($req_body);

        $response = $handshake->send($type, 'contact/getdata', $req_body);

        return $response;
    }

	function _contact($req_body, $type) {
        $handshake = new SuiteApi(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);
        $req_body = json_encode($req_body);

		$response = $handshake->send($type, 'contact', $req_body);

		return $response;
	}

	function _field($type) {
		$handshake = new SuiteApi(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);
		$response = $handshake->send($type, 'field/translate/en');

		return $response;
	}

	function _segment($type, $req_body = '') {
		$handshake = new SuiteApi(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);
		$req_body = json_encode($req_body);
		
		$response = $handshake->send($type, 'filter/64221/contact_criteria', $req_body);

		return $response;
	}

    function andy() {
        $handshake = new SuiteApi(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);
        
        
        $response = $handshake->send('GET', 'event', '');

        print_r($response);
    }

	function _contact_from_segment($type, $req_body = '') {
		$handshake = new SuiteApi(EMARSYS_API_USERNAME, EMARSYS_API_SECRET);
		$req_body = json_encode($req_body);
		
		$response = $handshake->send($type, 'filter/64221/contacts/offset=0&limit=1000', $req_body);

		return $response;
	}

	function _generate_msg($body) {
		$msg = '';

		if (count($body) > 0 ) {
			$a=0;
			foreach ($body as $key) {
				$key_index = array_keys($body)[$a];
				$msg = $msg.$key_index." : ";
				
				$b=0;
				foreach ($key as $val) {
					$msg = $msg.array_keys($key)[$b]."-";
					$msg = $msg.$val.",";
					$b++;
				}
				$a ++;
			}
		}

		return $msg;
	}
}

