<?php

/**
 * This class will grab a feed from Google Weather
 * caching is optional and achieved via apc / although it could just as easily be stored in a local database.
 *
 * the class will try to output cached data rather than refreshing it with every page load.
 * Only if the data is older than $CACHETIME will it refresh
 *
 * @author faffyman@gmail.com
 * @since 1st July 2008
 *
 *
 * Usage of Google Weather API
 * Google has never released this APi officially. It's main use is for showing weather on google search pages
 * and for use within iGoogle widgets.
 * Google may change or withdraw the the xml feed at anytime.
 *
 *
 * This code is released under the 2-Clause FreeBSD license.
 * So you're pretty much free to do whatever you like with it.
 *
 * Copyright 2008-2011 tswann. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 *  1. Redistributions of source code must retain the above copyright notice, this list of
 *     conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list
 *     of conditions and the following disclaimer in the documentation and/or other materials
 *     provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY tswann ''AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
 * FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL tswann OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are those of the
 * authors and should not be interpreted as representing official policies, either expressed
 * or implied, of tswann.
 *
 */


class GoogleWeather  {

  /**
   * location string that will be searchable on Google weather
   *
   * @var string
   */
  public    $sLocation = '';



  /**
   * HTTP HOST for the remote xml file
   *
   * @var string
   */
  protected $sQueryHost = 'www.google.co.uk';



  /**
   * URI under the HTTP HOST that points to the main XML file
   *
   * @var unknown_type
   */
  protected $sQueryResource = '/ig/api?weather=' ;


  /**
   * Cache lifetime in seconds
   *
   * @var int
   */
  protected $CACHE_TIME = 3600  ;



  /**
   * storage container for the captured data
   * usually passed to a view.
   *
   * @var array
   */
  public $aForecastData = array();






  /**
   * empty constructor
   *
   */
  public function __construct()
  {


  }//end constructor



  /**
   * Get weather data for a specified location
   *
   * @param string $sLocation  e.g. Limavady, Northern Ireland
   * @return array
   */
  public function getWeather($sLocation='London')
  {
    if (!empty($sLocation) || !empty($this->sLocation) ) {
      $this->sLocation = !empty($sLocation) ? $sLocation : $this->sLocation;

      $aForecast = $this->getWeatherFromCache() ;

      // if the cache returned nothing
      if (empty($aForecast) ) {
        //refresh the cache table
        $aForecast = $this->refreshCache();

      }
      $this->aForecastData = $aForecast ;
      return $aForecast ;

    } else {
      //throw an error;
      trigger_error("Please Specify sLocation", E_USER_ERROR);
      die();
    }


  }

  /**
   * Get matching data from the cache
   *
   */
  private function getWeatherFromCache()
  {
    $aForecast = array();
    if(function_exists('apc_fetch')) {
      $aForecast = apc_fetch($this->sLocation);
    }
    return $aForecast ;
  }




  /**
   * If cached Data is empty or too old then get it from the web sevice
   * and store it into the cache table
   *
   */
  private function refreshCache()
  {
    $sGuri = 'http://'.$this->sQueryHost.$this->sQueryResource.urlencode($this->sLocation) ;

    $sXml = file_get_contents($sGuri);

    $aForecast = $this->parseFeed($sXml);
    if(function_exists('apc_add')) {
      apc_add($aForecast,$this->sLocation,$this->CACHE_TIME);
    }
    return $aForecast;

  }



  /**
   * parse the feed grabbed from the web service
   *
   */
  private function parseFeed($sXml)
  {
    //load the xml into a simple XML object
    $oXml = new SimpleXMLElement($sXml) ;

    //search for errors
    if(isset($oXml->weather->problem_cause) ){
      trigger_error("Unspecified error retreiving weather data", E_USER_ERROR);
      return;
    }


    $oGoogleForecastInfo = $oXml->weather->forecast_information ;
    $this->recordConditions($oGoogleForecastInfo,$oForecastInfo);

    $oGCurrent = $oXml->weather->current_conditions ;

    //
    $oCurrentConditions = new WeatherConditions('C');
    $this->recordConditions($oGCurrent,$oCurrentConditions);

    //Initialise an array of all conditions to be passed back
    $aForecast= array();
    foreach ($oXml->weather->forecast_conditions as $oGoogleDay) {
      $i++;
      $oTempDay = new WeatherConditions('C');
      $this->recordConditions($oGoogleDay,$oTempDay);

      //do we need to convert temps?
      if ($oTempDay->units=='C' && !empty($oTempDay->low)) {
        $oTempDay->low = $this->converttemp($oTempDay->low);
        $oTempDay->high = $this->converttemp($oTempDay->high);
      }

      $aForecast['day'.$i] = $oTempDay;
      unset($oTempDay);
    }

    //convert Temps



    $aForecast['current'] = $oCurrentConditions;
    $aForecast['info'] = $oForecastInfo;

    return $aForecast;


  }



  private function recordConditions($oGoogleConditions,&$oWeatherConditions)
  {

    foreach ($oGoogleConditions->children() as $oGoogleCondition) {
      $sConditionLabel = $oGoogleCondition->getname();
      $oWeatherConditions->$sConditionLabel = (string)$oGoogleCondition->attributes()->data;
    }

  }

  /**
   * simple convertor for Farenheight to celsius
   *
   * @param int $nFaren
   * @return int
   */
  private function converttemp($nFaren)
  {
    $nCelsius = (($nFaren-32) * 5)/9 ;
    return (int)$nCelsius ;
  }




}//end class




class WeatherConditions {

  var $temp_f;
  var $temp_c;
  var $wind_condition;
  var $humidity;
  var $icon;
  var $condition;
  var $day_of_week;
  var $low;
  var $high;
  var $units;



  /**
   * units can be [C]elsius or [F]arenheit
   *
   * @param string $unit
   */
  public function __construct($unit='C') {
    $this->units = $unit;
  }

}

?>