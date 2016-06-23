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

class Captcha {

  protected $font = "";

  protected $height;

  protected $width;

  protected $maxFontSize;

  public $result;

  protected $operatorName;

  public $lines = 0;

  public $noise = 10;

  public function __construct() {

    $this->height = 25;

    $this->width = 80;

    $this->maxFontSize = 18;

    $this->font = realpath( realpath( __DIR__ ) . "/font/webfont.ttf" );

  }

  public function generateFormula() {

    $operatorName = array( "addition", "subtraction", "multiplication", "division" );
    $this->operatorName = $randOperatorName = $operatorName[ mt_rand( 0, 3 ) ];

    $operationMethod = $randOperatorName . "Formula";
    $operands = $this->$operationMethod();

    switch ( $randOperatorName ) {
      case 'addition':
        $this->result = $operands["left"] + $operands["right"];
        break;
      case 'subtraction':
        $this->result = $operands["left"] - $operands["right"];
        break;
      case 'multiplication':
        $this->result = $operands["left"] * $operands["right"];
        break;
      case 'division':
        $this->result = $operands["left"] / $operands["right"];
        break;
    }

    return $operands;

  }

  protected function additionFormula() {

    $operand = mt_rand(1, 50);
    $secondOperand = mt_rand(1,50);

    return array(
      "left"  => $operand,
      "right" => $secondOperand
      );

  }

  protected function subtractionFormula() {

    $additionFormula = $this->additionFormula();
    while ( $additionFormula["right"] > $additionFormula["left"] ) {
      $additionFormula = $this->additionFormula();
    }

    return array(
      "left"  => $additionFormula["left"],
      "right" => $additionFormula["right"]
      );

  }

  protected function multiplicationFormula() {

    $operand = mt_rand(1, 10);
    $secondOperand = mt_rand(1,10);

    return array(
      "left"  => $operand,
      "right" => $secondOperand
      );

  }

  protected function divisionFormula() {

    $multiplicationFormula = $this->multiplicationFormula();
    $multiResult = $multiplicationFormula["left"] * $multiplicationFormula["right"];

    return array(
      "left" => $multiResult,
      "right" => $multiplicationFormula["left"]
      );

  }

  public function getOperatorName() {
    return $this->operatorName;
  }

  public function generateOperator( $operatorName ) {

    $operators = array(
      "addition" => "＋",
      "subtraction" => "－",
      "multiplication" => "×",
      "division" => "÷"
      );

    return $operators[$operatorName];
  }

  public function captcha( $operands, $operator ) {

    $image = imagecreatetruecolor( $this->width , $this->height );
    // 创建了图像
    $white = imagecolorallocate( $image, 255, 255, 255 );
    imagefilledrectangle( $image,
                           0,
                           0,
                           $this->width,
                           $this->height,
                           $white );
    // 填充底色
    $chars = str_split( $operands["left"] );
    $chars[] = $operator;
    $right = str_split( $operands["right"] );
    $chars = array_merge( $chars, $right );

    $this->writeChars( $image, $chars );
    $this->writeLines( $image );
    $this->writeNoise( $image );

    return $image;

  }

  public function writeChars( $image, $chars ) {

    $baseX = 0;
    foreach ( $chars as $char ) {

      $angle      = mt_rand( -15, 15 );
      $charSize   = mt_rand( ( $this->maxFontSize - 5 ), $this->maxFontSize );
      $charBound  = imagettfbbox( $charSize,
                                 $angle,
                                 $this->font,
                                 $char );
      $charWidth  = abs( $charBound[4] - $charBound[0] );
      $charHeight = abs( $charBound[5] - $charBound[1] );

      // 计算位置的
      $charX =  $baseX + mt_rand( round($charWidth * 0.2), round($charWidth * 0.8) );
      $baseX += mt_rand( $charWidth, round( $charWidth * 1.5 ) );
      $charY =  $charHeight + mt_rand( 0, abs( $this->height - $charHeight ) );

      $randColor = imagecolorallocate( $image,
                                       mt_rand(50, 200),
                                       mt_rand(50, 200),
                                       mt_rand(50, 200) );

      imagettftext( $image,
                    $charSize,
                    $angle,
                    $charX,
                    $charY,
                    $randColor,
                    $this->font,
                    $char );

    }

  }

  public function writeLines( $image ) {

    imageantialias( $image, true );
    for ( $line = 0; $line < $this->lines ; $line++ ) { 

      $lineColor = imagecolorallocate( $image,
                                       mt_rand(150, 250),
                                       mt_rand(150, 250),
                                       mt_rand(150, 250) );

      $leftStart  = mt_rand( 0, round( $this->width / 2 ) );
      $rightStart = mt_rand( round( $this->width / 2 ), $this->width );

      imageline( $image,
                 $leftStart,
                 mt_rand( 0, $this->height ),
                 $rightStart,
                 mt_rand( 0,$this->height ),
                 $lineColor );

    }

  }

  public function writeNoise( $image ) {

    for ( $noise = 0; $noise < $this->noise ; $noise++ ) { 
      
      imagesetpixel( $image,
                     mt_rand( 0, $this->width ),
                     mt_rand( 0, $this->height ),
                     imagecolorallocate($image , 255, 255, 255) );

    }

  }

  public function output( $image ) {

    header( "Content-Type: image/png" );
    imagepng( $image );
    imagedestroy( $image );

  }

}
?>