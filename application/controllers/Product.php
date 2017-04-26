<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Product extends CI_Controller {
	function index() {
		ini_set('memory_limit', '1024M');

		// output headers so that the file is downloaded rather than displayed
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=Product_Catalog.csv');

		// create a file pointer connected to the output stream
		$output = fopen('php://output', 'w');

		// output the column headings
		fputcsv($output, array('item', 'title', 'description', 'category', 'link', 'image', 'msrp', 'price', 'brand', 'available'));
		
		$this->load->model('model');
		$data = $this->model->get_data_product();
		foreach ($data as $value) {
			fputcsv($output, $value);
		}
		fclose($output);
	}

	function facebook() {
		ini_set('memory_limit', '-1');
		
		// output: bool(true) resource(71) of type (FTP Buffer)
		$this->load->model('model');
		$data = $this->model->get_data_product_v2();
		
		$csv = "g:id,g:title,g:description,g:product_type,g:link,g:image_link,g:condition,g:price,g:sale_price,g:sale_price_effective_date,g:brand,g:availability\n";//Column headers
		
		foreach ($data as $key => $value) {
			$csv.= $value['g:id'].",".$value['g:title'].",".$value['g:description'].",".$value['g:product_type'].",".$value['g:link'].",".$value['g:image_link'].",".$value['g:condition'].",".$value['g:price'].",".$value['g:sale_price'].",".$value['g:sale_price_effective_date'].",".$value['g:brand'].",".$value['g:availability']."\n";
		}
		
		$csv_handler = fopen ('F:\Data\Product_Facebook.csv','w');
		fwrite ($csv_handler,$csv);
		fclose ($csv_handler);
	}
}