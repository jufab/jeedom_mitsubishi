<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class mitsubishi extends eqLogic {

  public static function cron5() {
    if (config::byKey('token', 'mitsubishi', '') == '') {
      mitsubishi::getToken();
    }
    mitsubishi::refreshAll();
  }

  public static function refreshAll() {
    $json = mitsubishi::callMelcloud('https://app.melcloud.com/mitsubishi.Wifi.Client/User/ListDevices',array('X-MitsContextKey: ' . config::byKey('token', 'mitsubishi')),array());
    //log::add('mitsubishi', 'debug', 'Retrieve ' . print_r($json, true));
    log::add('mitsubishi', 'debug', 'Retrieve ' . config::byKey('token', 'mitsubishi'));
    foreach ($json as $building) {
      log::add('mitsubishi', 'debug', 'Building ' . $building['ID']);
      foreach ($json as $building) {
        foreach ($building['Structure']['Floor'] as $floor) {
          foreach ($floor['Areas'] as $area) {
            foreach ($area['Devices'] as $device) {
              log::add('mitsubishi', 'debug', 'Retrieve ' . print_r($device, true));
              $mitsubishi=mitsubishi::byLogicalId($building['ID'] . $device['ID'], 'xiaomihome');
              /*if (!is_object($mitsubishi)) {
                $mitsubishi = new mitsubishi();
                $mitsubishi->setEqType_name('mitsubishi');
                $mitsubishi->setLogicalId($building['ID'] . $device['ID']);
                $mitsubishi->setIsEnable(1);
                $mitsubishi->setIsVisible(1);
                $mitsubishi->setName($device['Name'] . ' ' . $building['Name']);
                $mitsubishi->setConfiguration('deviceID', $device['ID']);
                $mitsubishi->setConfiguration('buildingID', $building['ID']);
                $mitsubishi->save();
              }*/
            }
          }
        }
      }
    }
  }

  public static function getToken() {
    if (config::byKey('password', 'mitsubishi') != '' && config::byKey('mail', 'mitsubishi') != '') {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://app.melcloud.com/Mitsubishi.Wifi.Client/Login/ClientLogin");
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS,
      "Email=" . config::byKey('mail', 'mitsubishi') . "&Password=" . config::byKey('password', 'mitsubishi') . "&Language=7&AppVersion=1.10.1.0&Persist=true&CaptchaChallenge=null&CaptchaChallenge=null");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $server_output = curl_exec($ch);
      curl_close($ch);
      $json = json_decode($server_output, true);
      //$data = 'Email='. config::byKey('mail', 'mitsubishi') . '&Password=' . config::byKey('password', 'mitsubishi') . '&Language=7&AppVersion=1.7.1.0&Persist=true&CaptchaChallenge=null&CaptchaResponse=null';
      //$json = mitsubishi::callMelcloud('https://app.melcloud.com/mitsubishi.Wifi.Client/Login/ClientLogin',array(),$data);
      log::add('mitsubishi', 'debug', 'Retrieve ' . print_r($json, true));
      if ($json['ErrorId'] == null) {
        config::save("token", $json['LoginData']['ContextKey'], 'mitsubishi');
      } else {
        log::add('mitsubishi', 'debug', 'Connexion error');
      }
    }
  }

  public static function callMelcloud($_url = '', $_header = array(), $_data = array()) {
    $request_http = new com_http($_url);
    if (!empty($_data)) {
      $request_http->setPost($_data);
    }
    if (!empty($_header)) {
      $request_http->setHeader($_header);
    }
    $output = $request_http->exec(30);
    return json_decode($output, true);
  }

}

class mitsubishiCmd extends cmd {
  public function execute($_options = array()) {
    if ($this->getType() == 'action') {
      $Eqlogic = $this->getEqLogic();
      if ($this->getSubType() == 'slider') {
        $option = $_options['slider'];
      } else {
        $option = $this->getConfiguration('option');
      }
      $Eqlogic->SetModif($option,$this->getConfiguration('flag'),$this->getConfiguration('idflag'));
      mitsubishi::refreshAll();
    }
  }

}

?>
