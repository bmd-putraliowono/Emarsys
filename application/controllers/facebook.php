<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: putra.liowono
 * Date: 5/19/2016
 * Time: 11:29 AM
 */

class Facebook extends CI_Controller {
    public function __construct(){
        parent::__construct();

        $this->load->helper(array('xml'));
        $config['uri_protocol'] = "PATH_INFO";
        $config['enable_query_strings'] ="FALSE";
        parse_str($_SERVER['QUERY_STRING'],$_GET);
    }

    function index() {
        ini_set('memory_limit', '-1');
        header("Content-Type: text/xml");
        $this->data['facebook_feed'] = $this->model->get_facebook_feed();
        $this->load->view('content/facebook_view', $this->data);
    }

    function custom(){
        //tangkap filter dr URL String
        $filter = array(
            0 => isset($_GET['id']) && !empty($_GET['id'])? "[g:id] = '".$_GET['id']."'" : '',
            1 => isset($_GET['brand']) && !empty($_GET['brand'])? "[g:brand] = '".$_GET['brand']."'" : '',
            2 => isset($_GET['cat1']) && !empty($_GET['cat1'])? "[category_name_level_1] = '".$_GET['cat1']."'" : '',
            3 => isset($_GET['cat2']) && !empty($_GET['cat2'])? "[category_name_level_2] = '".$_GET['cat2']."'" : '',
            4 => isset($_GET['cat3']) && !empty($_GET['cat3'])? "[category_name_level_3] = '".$_GET['cat3']."'" : '',
            5 => isset($_GET['cat4']) && !empty($_GET['cat4'])? "[category_name_level_4] = '".$_GET['cat4']."'" : '',
        );
        //menggabungkan filter menjadi 1 string
        $where = "";
        foreach($filter as $key => $val){
            if($val == '')continue;
            if($where != ""){
                $tempWhere1 = $where;
                $tempWhere2 = $val;
                $where = $tempWhere1." and ".$tempWhere2;
            }else{
                $where = $val;
            }
        }

        //tangkap replace dr URL String
        $rep = array(
            0 => isset($_GET['source']) && !empty($_GET['source'])? "'facebook','".$_GET['source']."'" : '',
            1 => isset($_GET['campaign']) && !empty($_GET['campaign'])? "'dynamic_remarketing','".$_GET['campaign']."'" : '',
            2 => isset($_GET['medium']) && !empty($_GET['medium'])? "'CPC','".$_GET['medium']."'" : '',
//            3 => isset($_GET['content']) && !empty($_GET['content'])? "'utm_content='+dbo.udf_urlLink(lower([g:brand]+'_'+[g:title]), '_', default),'utm_content='+'".$_GET['content']."'" : '',
        );
        //menggabungkan replace menjadi 1 string
        $replace = "";
        foreach($rep as $key2 => $val2){
            if($val2 == '')continue;
            if($replace != ""){
                $tempRep1 = $replace;
                $replace = "replace(".$tempRep1.",".$val2.")";
            }else{
                $replace = "replace([g:link],".$val2.")";
            }
        }

        //start buat XML
        ini_set('memory_limit', '-1');
        header("Content-Type: text/xml");
        if($where != '' && $replace == '') {
            $this->data['facebook_feed'] = $this->model->get_facebook_feed_filter($where);
        }elseif($where == '' && $replace != ''){
            $this->data['facebook_feed'] = $this->model->get_facebook_feed_rep($replace);
        }else{
            $this->data['facebook_feed'] = $this->model->get_facebook_feed_fr($where,$replace);
        }
        $this->load->view('content/facebook_view', $this->data);
    }
}