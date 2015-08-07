<?php

/**
 * test actions.
 *
 * @package    sf_sandbox
 * @subpackage test
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 9301 2008-05-27 01:08:46Z dwhittle $
 */
class testActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
   
   protected function prepare() {
      
      header('Content-type: text/html; charset=utf8');
      set_time_limit(0);
      $con = Propel::getConnection();
      $this->con = $con;
      $con->prepareStatement('SET NAMES UTF8')->executeQuery();
      
      $this->table = "noibai_hanoi_calc"; 
      $this->col = "hn_nb";
      $this->type = PlugAbstractHanoiNoibai::TYPE_TO_AIRPORT;
      
   }
   public function executeRetest() {
      
      $this->prepare();
      $sql = "SELECT id, ".$this->col."_json ".
              "FROM ".$this->table." ".
              "WHERE ".$this->col." > 0 AND rebot = 0 ORDER BY RAND() LIMIT 10";
      $con = $this->con;
      $rs = $con->prepareStatement($sql)->executeQuery();
      while($rs->next()) {
         $id = $rs->get('id');
         $json = json_decode($rs->get($this->col.'_json'), true); 
         unset($json['selected']);
          
          
         $min = 0;
         $key = '';
         foreach($json as $k => $ret) {
            if(isset($ret['total_met']) && ($ret['total_met'] < $min || $min == 0)) {
               $min = $ret['total_met'];
               $key = $k;
            }
         }
         if($key) {
            $json ['selected'] = $json[$key];
            
            $sql = 
               "UPDATE ".$this->table." ".
               "SET ".$this->col." = ".$min.", ".$this->col."_json = '".json_encode($json)."', rebot = 1 ".
               "WHERE id = ".$id;
            $con->prepareStatement($sql)->executeUpdate();
         }
      }
      return $this->renderText('<meta http-equiv="refresh" content="0" />');
   }
   
   public function executeIndex($request) {
      
      $this->prepare();
      
      $type = $this->type; 
      $con = $this->con;  
      $table = $this->table; 
      $col = $this->col;
      
      $sql = "SELECT id, street_name, village_name, district_name ".
              "FROM ".$table." ".
              "WHERE ".$col." = 0 ORDER BY RAND() LIMIT 1";
      
      $rs = $con->prepareStatement($sql)->executeQuery();
      
      echo '<pre>';
      while($rs->next()) {
         $id = $rs->get('id');
         $sName = $rs->get('street_name');
         $vName = $rs->get('village_name');
         $dName = $rs->get('district_name');
         $addr = $sName.', '.$vName.', '.$dName;
         $addr = str_replace(array('Phường ', 'Thành phố ', 'Xã '), '', $addr);
         
         try {
            
            $ret = PlugHanoiNoibai::getMet($addr, $type);
            $json = json_encode($ret); 
            
            $sql = 
               "UPDATE ".$table." ".
               "SET ".$col." = ".$ret['selected']['total_met'].", ".$col."_json = '".$json."' ".
               "WHERE id = ".$id;

            $con->prepareStatement($sql)->executeUpdate();
            
            print_r($ret);
         }
         catch(Exception $e) {
            echo $e->getMessage();
         }
          
         
      } 
      echo '</pre>';
      $sql = "select count(*) as c from ".$table." WHERE ".$col." = 0";
      $rs = $con->prepareStatement($sql)->executeQuery();
      if($rs->next()) {
         echo "Left: ".$rs->get('c');
      }
      return $this->renderText('<meta http-equiv="refresh" content="0" />');
//      $promotion = new PlugPromotion(array(
//          
//         'partner_id' => 85, 
//         'city_id' => 24,
//         'chunk_id' => 1,
//         'ride_method_id' => 1,
//         'current_date' => date('Y-m-d'), 
//         'depart_date' => date('Y-m-d', time()+30*86400), 
//         'range_day' => 30, 
//         'day_indexs' => array(1, 3, 6, 7), 
//         'total_cost' => 100000));
//
//      $ret = $promotion->results();
//      print_r($ret);
//      
//      return $this->renderText('<html><head></head><body> </body></html>');
   }
}
