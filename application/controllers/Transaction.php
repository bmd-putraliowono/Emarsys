<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Transaction extends CI_Controller {
	function index() {
		ini_set('memory_limit', '-1');
		
		// output: bool(true) resource(71) of type (FTP Buffer)
		$this->load->model('model');
		$data = $this->model->get_transaction_data();
		
		$csv = "order,date,customer,item,c_sales_amount,quantity,c_shopcart_flag \n";//Column headers
		
		foreach ($data as $key => $value) {
			$csv.= $value['order'].",".$value['date'].",".$value['customer'].",".$value['item'].",".$value['c_sales_amount'].",".$value['quantity'].",".$value['c_shopcart_flag']."\n";
		}
		
		$file_name = date("Y").date("m").date("d").".csv";
		$csv_handler = fopen ('F:\Data\sales_item_'.$file_name,'w');
		fwrite ($csv_handler,$csv);
		fclose ($csv_handler);
	}

	function temp(){
		$this->load->model('model');
		$data = $this->model->get_transaction_data_temp();
		
		$csv = "order,date,customer,item,c_sales_amount,quantity,c_shopcart_flag \n";//Column headers
		
		foreach ($data as $key => $value) {
			$csv.= $value['order'].",".$value['date'].",".$value['customer'].",".$value['item'].",".$value['c_sales_amount'].",".$value['quantity'].",".$value['c_shopcart_flag']."\n";
		}
		
		$file_name = date("Y").date("m").date("d").".csv";
		$csv_handler = fopen ('F:\Data\sales_item_'.$file_name,'w');
		fwrite ($csv_handler,$csv);
		fclose ($csv_handler);
	}
}