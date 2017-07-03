<?php
namespace Maalls\DomDocument;

use Maalls\Charset;
use Maalls\Curl;

class Factory {

  const ERROR_TYPE_CONNECTION = 1;
  const ERROR_TYPE_ENCODING = 2;
  const ERROR_TYPE_DOM_PARSING = 3;
  
  private $ch = null;
  private $logger = null;
  
  private $error;
  
  public function __construct($curlHandler = null, $logger = null) {
    
    $this->ch = $curlHandler ? $curlHandler : new Curl();
    $this->logger = $logger;
    
  }

  public function getCurlHandler() {
  
    return $this->ch;
    
  }
  
  public function setCurlHandler($ch) {
  
    $this->ch = $ch;
    
  }
  
  public function createFromUrl($url, $charset = null, $charset_hint = null) {
    
    if(!$this->ch) throw new ErrorException("Curl Handler required.");
    
    $this->ch->setOption(CURLOPT_URL,$url);
    $this->ch->execute();
    $code = $this->ch->getInfo(CURLINFO_HTTP_CODE);
    
    if($code != 200) {
      
      $this->logger->info("HTTP status : $code.");  
      $this->error = self::ERROR_TYPE_CONNECTION;
      return false;
      
    }
    else {
    
      return $this->createFromCurl($this->ch, $charset, $charset_hint);  
      
    }
          
  }
  
  public function createFromCurl($curl, $charset = null, $charset_hint = null) {
    
    if(!$charset) $charset = $this->detectCharsetFromCurl($curl);
    return $this->createFromHtml($curl->getContent(), $charset, $charset_hint);
    
        
  }
    
  public function createFromHtml($html, $charset = null, $charset_hint = null, $format = true) {
    
    if($format) $html = $this->formatHtml($html, $charset, $charset_hint);
    
    if(!$html) {
      
      $this->error = self::ERROR_TYPE_ENCODING;
      $doc = false;
      
    }
    else {
    
      $doc = new DOMDocument("1.0", "utf-8");
      
      if(@$doc->loadHtml($html)) {
        
        $this->error = false;
        
      }
      else {
       
        $this->error = self::ERROR_TYPE_DOM_PARSING;
        $doc = false; 
        
      }
      
    }
    
    return $doc;
    
  }
  
  public function formatHtml($html, $charset = null, $charset_hint = null) {
    
    $html = $this->toUTF8($html, $charset, $charset_hint);
          
    $tidy = new Tidy();
    $config = array("hide-comments" => true);
    $tidy->parseString($html, $config, 'UTF8');
    $tidy->cleanRepair();
    $html = (string)$tidy;
    $html = $this->moveMetaContentTypeToTop($html);
    $html = $this->formatDocType($html);
    
    return $html;
    
    
  }
  
  private function toUTF8($html, $charset = null, $charset_hint = null) {
    
    if(!$charset) $charset = $this->detectCharsetFromHtml($html);
    if(!$charset && $charset_hint) $charset = $charset_hint;
    
    if($charset && $charset != "utf-8") {
      
      $html = @iconv($charset, "UTF-8//IGNORE", $html);
      
    }
    
    return $html;
    
  }
  

  private function detectCharsetFromCurl($curl) {
    
    $charsetDetector = new Charset("", $curl->getInfo(CURLINFO_CONTENT_TYPE));
    return $charsetDetector->getCharset();
    
  }
  
  private function detectCharsetFromHtml($html, $charset_hint = null) {
    
    $charsetDetector = new Charset($html);
    return $charsetDetector->getCharset();
        
  }
    
  private function moveMetaContentTypeToTop($html) {
    
    preg_replace('/(<meta\s*http-equiv="Content-Type"\s*content=(\b)*"([^;]*;)?\s*charset=([^"]*?)(?:"|\;)[^>]*>)/is', '', $html);
    
    $patterns = array(
      '/(<meta\s*http-equiv="Content-Type"\s*content="[^;]*;\s*charset=([^"]*?)(?:"|\;)[^>]*>)/is',
      '/(<head[^>]*>)/is'
    );
    
    $replaces = array(
      '',
      "$1\n<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">"
    );
    
    $html = preg_replace($patterns, $replaces, $html);
    
    return $html;
    
  }
  
  private function formatDocType($html) {

    $html = preg_replace("@^.+?<html@is","<html", $html);
    $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN">' . PHP_EOL . $html;
    
    return $html;
    
  }
  
  public function getError() {
    
    return $this->error;
    
  }
  
  
}
