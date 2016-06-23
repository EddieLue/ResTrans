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

namespace ResTrans\Core\Parser;
use ResTrans\Core;

abstract class ParserConventions {

  public $app;

  public $dataPath;

  public $result;

  public $path;

  public $resource;

  public $contentCache;

  public $encoding;

  public $eol;

  public $originalLanguage;

  public $targetLanguage;

  public function __construct($path, Core\App $app) {
    $this->appi = $app;
    $this->dataPath = DATA_PATH;
    $this->result = [];
    $this->path = $path;
    $this->resource = @fopen($path, "rb");
    if (!$this->resource) throw new Core\CommonException("file_resource_error");
  }

  public function save() {
    $savedFileName = $this->appi->sha1Gen() . $this->appi->charsGen(40) . ".json";
    $this->lastSavedFileName = $savedFileName;

    is_dir("{$this->dataPath}/{$savedFileName[0]}") || mkdir("{$this->dataPath}/{$savedFileName[0]}");
    file_exists("{$this->dataPath}/{$savedFileName[0]}/index.html") || file_put_contents("{$this->dataPath}/{$savedFileName[0]}/index.html", "");

    $json = json_encode($this->result, JSON_UNESCAPED_UNICODE);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Core\CommonException("save_error");
    }

    file_put_contents("{$this->dataPath}/{$savedFileName[0]}/{$savedFileName}", $json);
    return $this;
  }

  public function setLanguage($originalLanguage, $targetLanguage) {
    $this->originalLanguage = $originalLanguage;
    $this->targetLanguage = $targetLanguage;
    return $this;
  }

  public function unsave() {
    unlink("{$this->dataPath}/{$this->lastSavedFileName[0]}/{$this->lastSavedFileName}.json");
    return $this;
  }

  public function getFileContent ($force = false) {
    if (!$force && $this->contentCache) return $this->contentCache;
    if ( !$file = fread($this->resource, filesize($this->path)) ) {
      throw new Core\CommonException("parse_failed");
    }

    $json = @json_decode($file);
    if (!$json || json_last_error() !== JSON_ERROR_NONE) {
      throw new Core\CommonException("parse_failed");
    }

    $json = $this->readable($json);

    $this->encoding = isset($json->source_encoding) ? $json->source_encoding : "utf-8";
    $this->eol = isset($json->eol) ? $json->eol : "unix";
    return $json;
  }

  public function read($start = 1, $end = 100, $sort = true) {
    $json = $this->getFileContent();
    $sort && usort($json->lines, function ($line1, $line2) {
      return $line1->line_number - $line2->line_number;
    });

    $result = array_slice(
      $json->lines,
      $start - 1,
      $end - $start < 1 ? 1 : ($end - $start) + 1,
      true
    );

    return $result;
  }

  public function combine ($lines, $translations) {
    foreach ($translations as $translation) {
      isset($translation->line) && isset($lines[$translation->line - 1]) &&
      ($lines[$translation->line - 1]->translations[] = $translation);
    }
    return array_values($lines);
  }
}