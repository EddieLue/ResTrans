<?php
/**
 * --------------------------------------------------
 * (c) ResTrans 2016
 * --------------------------------------------------
 * Apache License 2.0
 * --------------------------------------------------
 * get.restrans.com
 * --------------------------------------------------
*/

/**
 * 此翻译 API 的部分代码来自 
 * https://msdn.microsoft.com/en-us/library/ff512423.aspx
 */
namespace ResTrans\Core\TranslatorApi;
use ResTrans\Core;

class MicrosoftTranslator extends ApiConventions {

  public $translatorName = "mst";

  public $getAccessTokenAt;

  public $lastAccessToken = "";

  public $totalTranslated = 0;

  public $defaultConfig = [ "connect_timeout" => 10, "timeout" => 20 ];

  public $languageMapping = ["en-US" => "en", "en-UK" => "en", "ja-JP" => "ja", "de-DE" => "de",
    "ru-RU" => "ru", "fr-FR" => "fr", "ko-KR" => "ko", "zh-CN" => "zh-CHS", "zh-HK" => "zh-CHT",
    "zh-TW" => "zh-CHT"];

  public function getAccessToken () {
    if ( $this->getAccessTokenAt && time() > ($this->getAccessTokenAt + 600) ) {
      return $this->lastAccessToken;
    }

    $params = http_build_query([ "scope" => "http://api.microsofttranslator.com",
      "grant_type" => "client_credentials", "client_id" => $this->config["client_id"], 
      "client_secret" => $this->config["secret"] ]);
    $this->getAccessTokenAt = time();

    $curl = curl_init("https://datamarket.accesscontrol.windows.net/v2/OAuth2-13");
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->config["connect_timeout"]);
    curl_setopt($curl, CURLOPT_TIMEOUT, $this->config["timeout"]);
    $response = curl_exec($curl);

    if (curl_errno($curl)) {
      $this->getAccessTokenAt = 0;
      throw new Core\ApiException("get_token_failed");
    }

    curl_close($curl);
    $responseJSON = json_decode($response);
    if (json_last_error() !== JSON_ERROR_NONE || isset($responseJSON->error)) {
      $this->getAccessTokenAt = 0;
      throw new Core\ApiException("get_token_failed");
    }

    return ($this->lastAccessToken = $responseJSON->access_token);
  }

  public function init () {
    set_time_limit(0);
    $this->getAccessTokenAt = 0;
    $this->lastAccessToken  = "";
    $this->config           = array_merge($this->defaultConfig, $this->config);
    $this->getAccessToken();
    return $this;
  }

  public function translate ($lines) {
    $toBeTranslated = [];
    $result = [];
    $translateGroup = 0;
    $charCount = 0;
    $currentPosition = 0;

    foreach ($lines as $line) {
      $totalChar = mb_strlen($line);
      if ( isset($toBeTranslated[$translateGroup]) &&
           ( ($charCount + $totalChar) > 10000 || 
             count($toBeTranslated[$translateGroup]) > 2000) ) {
        $translateGroup++;
        $charCount = 0;
      }
      $toBeTranslated[$translateGroup][] = $line;
      $charCount += $totalChar;
    }

    try {
      foreach ($toBeTranslated as $pol) {
        $result = array_merge($result, $this->request($pol));
        $currentPosition = count($result);
      }
    } catch (Core\ApiException $e) {
      $e->lastResult($result);
      $e->lastParsedLine($currentPosition);
      throw $e;
    }

    return $result;
  }

  public function request ($lines) {
    $parsedLines = "";
    $result = [];
    foreach ($lines as $lines) {
      $parsedLines .= "<string xmlns=\"http://schemas.microsoft.com/2003/10/Serialization/Arrays\">";
      $parsedLines .= strip_tags($lines) . "</string>\n";
    }

    $requestXml = "
      <TranslateArrayRequest>
          <AppId/>
          <From>{$this->languageMapping[$this->originalLanguage]}</From>
          <Options>
          <Category xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />
            <ContentType xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\">text/plain</ContentType>
            <ReservedFlags xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />
            <State xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />
            <Uri xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />
            <User xmlns=\"http://schemas.datacontract.org/2004/07/Microsoft.MT.Web.Service.V2\" />
         </Options>
          <Texts>
            {$parsedLines}
          </Texts>
          <To>{$this->languageMapping[$this->targetLanguage]}</To>
        </TranslateArrayRequest>";

    $curl = curl_init("http://api.microsofttranslator.com/V2/Http.svc/TranslateArray");
    $headers = ["Content-Type: text/xml", "Authorization: Bearer " . $this->getAccessToken()];
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $requestXml);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($curl);

    if (curl_errno($curl)) throw new Core\ApiException();
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($response);
    if (false === $xml || !isset($xml->TranslateArrayResponse)) throw new Core\ApiException();
    foreach ($xml->TranslateArrayResponse as $pos => $translation) {
      if (!isset($translation->TranslatedText)) throw new Core\ApiException();
      $text = (array)$translation->TranslatedText;
      $result[] = [
        "mt" => isset($text[0]) ? html_entity_decode($text[0]) : "",
        "mtp" => $this->translatorName
      ];
    }
    return $result;
  }
}