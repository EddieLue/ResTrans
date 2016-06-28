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
use ResTrans\Core\TranslatorApi;

class TxtParser extends ParserConventions {

  public $lines = 0;

  public $translatable = 0;

  public function parse () {
    $content            = fread($this->resource, filesize($this->path));
    $this->encoding     = $sourceEncoding = mb_detect_encoding($content, "UTF-8");
    if (!strlen($content)) throw new Core\CommonException("length_error");
    if (!$sourceEncoding) throw new Core\CommonException("encoding_error");
    
    mb_substitute_character("none");
    /** 原始编码 (s)ource (e)ncoding */
    $this->result["se"] = $sourceEncoding;
    $content            = mb_convert_encoding($content, "UTF-8");
    $this->result["eol"] = "unix";
    if (false !== strpos($content, "\r\n")) {
      $this->result["eol"] = "windows";
    }
    $content            = str_replace(["\r\n", "\r", "\n"], "\n", $content);
    $translatorProvider = new TranslatorApi\ApiProvider($this->appi);
    $linesArray         = explode("\n", $content);
    $currentPosition    = 0;
    $linesCount         = $this->lines = count($linesArray);
    $translated         = [];
    // 保证在api耗尽的情况下不会陷入死循环
    try {
      while ($currentPosition < $linesCount) {
        try {
          $translator = $translatorProvider->getApi();
          $partOfResult = $translator
            ->setOriginalLanguage($this->originalLanguage)
            ->setTargetLanguage($this->targetLanguage)
            ->translate(array_slice($linesArray, $currentPosition));
          $translated    = array_merge($translated, $partOfResult);
          $currentPosition = $linesCount;
        } catch (Core\CommonException $e) {
          $translator->nextApi();
        } catch (Core\ApiException $e) {
          $currentPosition = $e->lastParsedLine();
          $translated      = $e->lastResult();
          $translator      = $translatorProvider->nextApi();
        }
      }
    } catch (Core\ApiResourceExhaustedException $e) {}

    $lines = [];
    foreach ($linesArray as $pos => $line) {
      /** 行号 (l)ine (n)umber */
      $result["ln"] = $pos + 1; 
      /** 原始文本 (o)riginal (t)ext */
      $result["ot"]     = $line;
      /** 是否需要翻译 (tr)anslate */
      $result["tr"]   = !!strlen($line);
      $result["tr"] && $this->translatable++;
      isset($translated[$pos]) && $result = array_merge($result, $translated[$pos]);
      array_push($lines, $result);
    }

    $this->result["ls"] = $lines;
    return $this;
  }

  public function check ($file) {
    if (class_exists("finfo", false)) {
      $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
      if ($fileInfo->file($file["tmp_name"]) !== "text/plain" ) {
        throw new Core\CommonException("check_file_type_failed");
      }
    } else {
      if ($file["type"] !== "text/plain" || !preg_match("/(.+)\.txt$/i", $file["name"])) {
        throw new Core\CommonException("check_file_type_failed");
      }
    }

    return $this;
  }

  public function build (
    array $buildConfiguration, /** 构建配置 */
    $bestTranslations = [], /** 最佳译文 */
    $newestTranslations = [] /** 最新译文 */
  ) {
    $result = [];
    if (!$fileContent = $this->getFileContent()) {
      throw new Core\CommonException("build_failed");
    }

    for ($ln = 1; $ln <= $buildConfiguration["line"]; $ln++) {
      if ($buildConfiguration["best_translation"] && isset($bestTranslations[$ln])) {
        $result[] = $bestTranslations[$ln]->text;
        continue;
      } elseif ($buildConfiguration["newest_translation"] && isset($newestTranslations[$ln])) {
        $result[] = $newestTranslations[$ln]->text;
        continue;
      } elseif ($buildConfiguration["machine_translation"]) {
        $currentLine = $fileContent->lines[$ln - 1];
        $result[] = property_exists($currentLine, "machine_translation") ?
          $currentLine->machine_translation : "";
        continue;
      } elseif ($buildConfiguration["source"]) {
        $currentLine = $fileContent->lines[$ln - 1];
        $result[] = isset($currentLine->text) ? $currentLine->text : "";
      }
    }

    return $result;
  }

  public function output ($built) {
    $built = mb_convert_encoding(
      implode($this->eol === "unix" ? "\n" : "\r\n", $built),
      $this->encoding,
      "UTF-8"
    );
    header("Content-Length: ". strlen($built));
    echo $built;
  }

  public function readable ($file) {
    $file->source_encoding = 
    property_exists($file, "se") ? $file->se : "utf-8";
    $file->lines = property_exists($file, "ls") ? $file->ls : [];
    unset($file->se, $file->ls);

    array_walk($file->lines, function (&$line) {
      $line->line_number = $line->ln;
      $line->text = $line->ot;
      $line->translate = $line->tr;
      $line->machine_translation = property_exists($line, "mt") ? $line->mt : "";
      $line->machine_translation_provider = property_exists($line, "mtp") ? $line->mtp : "";

      unset($line->ln, $line->ot, $line->tr, $line->mt, $line->mtp);
    });

    return $file;
  }
}
