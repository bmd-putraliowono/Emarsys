<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Google extends CI_Controller {
	public function __construct(){  
        parent::__construct();
        
        $this->load->helper(array('xml'));
    }

	function index() {
		ini_set('memory_limit', '-1');
		header("Content-Type: text/xml");
		$this->data['google_feed'] = $this->model->get_google_feed();
		$this->load->view('content/google_view', $this->data);
	}
}