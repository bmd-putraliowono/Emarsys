<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class AutoRegis extends CI_Controller {
    
    function get_data_regis(){
//        $this->load->library('curl');
        $this->load->database();
        $this->load->model('model');
        $data = $this->model->get_data_auto_regis();
        $dataOnArray = array();

        foreach($data as $key => $value){
            $dataOnArray[] = array(
                'email' => $value['Email'],
                'firstName' => $value['FirstName']
            );
        }
        foreach ($dataOnArray as $item => $val){
            $firstName = $val['firstName'];
            $email = $val['email'];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://link.bhinneka.com/u/register.php?CID=524852232&f=1868&p=2&a=r&SID=&el=&llid=&counted=&c=&inp_1=".$firstName."&inp_3=".$email."");
            curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $raw_data = curl_exec($ch);
            curl_close($ch);
            $fields = $this->db->field_data('BI_SURVEY_RECIPIENT');
            $fieldsOnArray = array();
            foreach($fields as $item){
                $fieldsOnArray[] = $item->name;
            }
            $log = array(
                $fieldsOnArray[0] => 'Abandon Payment',
                $fieldsOnArray[1] => $email,
                $fieldsOnArray[2] => $firstName,
                $fieldsOnArray[3] => date('Y-m-d H:i:s')
            );

            $this->model->post_log_ecoupon("BI_SURVEY_RECIPIENT",$log);
        }
    }

    function gen_code() {
            $e = $_GET['e'];
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, "https://link.bhinneka.com/u/register.php?CID=524852232&f=1877&p=2&a=r&SID=&el=&llid=&counted=&c=&inp_3=".$e."");
            curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $raw_data = curl_exec($ch);
            curl_close($ch);
            header("Location: http://www.bhinneka.com/aspx/bhindexpc.aspx");
            exit();
    }
}
?>