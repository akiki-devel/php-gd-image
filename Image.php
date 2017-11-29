<?php

namespace akiki\gd;

// require "bmp.php";
// require "bmp2.php";
// require "bmp3.php";
require "ImageBmp.php";

use akiki\gd\bmp;

class Image
{
	private $img = null;
	private $width;
	private $height;
	private $shapeColor;
	
	function __construct($widthOfile = null, $height = 0) {
		if ($widthOfile && gettype($widthOfile) == "string") {
			$this->file($widthOfile);
		}
		else if ($widthOfile && gettype($widthOfile) == "integer" && $height) {
			$this->create($widthOfile, $height);
		}
		else {
			$this->create(1, 1);
		}
		$this->setShapeColor(); // デフォルト
	}
		
	function __destruct() {
		$this->delete();
	}

	private
	function delete() {
		if ($this->img) {
			ImageDestroy($this->img);
			$this->img = null;
		}
	}

	private
	function size() {
		if ($this->img) {
			$this->width  = ImageSX($this->img);
			$this->height = ImageSY($this->img);
		}
	}

	public
	function file($file) {
		$this->delete();
		$info = getimagesize($file);
		switch ($info['mime']) {
		case 'image/jpeg':
			$this->img = imagecreatefromjpeg($file);
			break;
		case 'image/png':
			$this->img = imagecreatefrompng($file);
			break;
		case 'image/gif':
			$this->img = imagecreatefromgif($file);
			break;
		case 'image/x-ms-bmp':
			// $this->img = ImageCreateFromBMP($file);
			$this->img = ImageCreateFromBMP($file);
			break;
			// ? image/bmp
			// ? image/x-bmp
			// ? image/x-bitmap
			// ? image/x-xbitmap
			// ? image/x-win-bitmap
			// ? image/x-windows-bmp
			// ? image/ms-bmp
			// o image/x-ms-bmp
			// ? application/bmp
			// ? application/x-bmp
			// ? application/x-win-bitmap
		default:
			echo 'Error: ' . $file . ':' . $info['mime'] . " not compatible\n";
			exit(1);
		}
		$this->size();
	}

	public
	function create($width, $height) {
		$this->delete();
		$this->img = ImageCreateTrueColor($width, $height);
		$this->size();
		$this->clear();
	}

	public
	function scale($scale) {
		$timg = new Image();
		$timg->img = ImageScale($this->img,
										(int) $this->width * $scale,
										(int) $this->height * $scale,
										IMG_BICUBIC
										);
		// IMG_NEAREST_NEIGHBOUR、 IMG_BILINEAR_FIXED、 IMG_BICUBIC、 IMG_BICUBIC_FIXED 
		$timg->size();
		return $timg;
	}

	public
	function rotate(float $angle) {
		$timg = new Image();
		$timg->img = ImageRotate($this->img, $angle, $this->packColor());
		$timg->size();
		return $timg;
	}

	public
	function clear($r = 0.0, $g = 0.0, $b = 0.0, $a = 0.0) {
		imagealphablending($this->img, false);
		$rect = $this->getRect();
		$col  = $this->packColor($r, $g, $b, $a);
		ImageFilledRectangle($this->img,
									0, 0, $rect->width, $rect->height,
									$col);
		imagealphablending($this->img, true);
	}

	public
	function packColor($rOrgb = 0.0, $g = 0.0, $b = 0.0, $a = 0.0) {
		$r = $rOrgb;
		if (gettype($rOrgb) == "object") {
			$r = $rOrgb->r;
			$g = $rOrgb->g;
			$b = $rOrgb->b;
			$a = $rOrgb->a;
		}
		$color = ImageColorAllocateAlpha($this->img,
													(int) (255 * $r),
													(int) (255 * $g),
													(int) (255 * $b),
													(int) (127-127*$a));
		return $color;
	}

	public
	function setShapeColor($rOrgb = 0.0, $g = 0.0, $b = 0.0, $a = 1.0) {
		$this->shapeColor = $this->packColor($rOrgb, $g, $b, $a);
	}
	
	public
	function getSize() {
		$obj = [ "width" => $this->width,
					"height" => $this->height ];
		return (object) $obj;
	}
	public
	function getRect() {
		$obj = ["x"     => 0,
				  "y"     => 0,
				  "width" => $this->width,
				  "height" => $this->height ];
		return (object) $obj;
	}
	
	public
	function fGrayscale() { // グレースケール
		ImageFilter($this->img, IMG_FILTER_GRAYSCALE);
	}

	public
	function fSepia() {
		$this->fGrayscale();
		for ($y = 0; $y < $this->height; $y++) {
			for ($x = 0; $x < $this->width; $x++) {
				$pix = ImageColorAt($this->img, $x, $y);
				$n = $pix & 0xff; // グレースケールなのでrgbどの値も同じ
				$a = ($pix >> 24) & 0xff;
				// セピアカラー R:98 G:45 B:24

				// 各値の乗算する数値は、ある程度好みであるようです
				$r = ($n * 1);
				$g = ($n * 0.8);
				$b = ($n * 0.55);
				
				$col =
					ImageColorAllocateAlpha($this->img, $r, $g, $b, $a);
				ImageSetPixel($this->img, $x, $y, $col);
			}
		}
	}

	public // $r $g $b (0.0〜1.0) -> 255段階なら $r /= 255 とした値;
	function rgb2hsv($rOcol, $g = 0, $b = 0) {
		$r = $rOcol;
		if (gettype($rOcol) == "object") {
			$r = $rOcol->r;
			$g = $rOcol->g;
			$b = $rOcol->b;
		}

		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$h = $max - $min;

		if ($h > 0.0) {
			if ($max == $r) {
				$h = (60 * ($g - $b) / $h);
				if ($h < 0.0) {
					$h += 360.0;
				}
			}
			else if ($max == $g) {
				$h = 120 + (60 * ($b - $r) / $h);
			}
			else {
				$h = 240 + (60 * ($r - $g) / $h);
			}
		}
		$s = $max - $min;
		if ($max != 0.0) $s /= $max;
		$v = $max;
				
		return (object) [ "h" => $h, "s" => $s, "v" => $v ];
	}

	public // $h(0.0〜360.0) $s,$v(0.0〜1.0)
	function hsv2rgb($hOhsv, $s = 0, $v = 0) {
		$h = $hOhsv;
		if (gettype($hOhsv) == "object") {
			$h = $hOhsv->h;
			$s = $hOhsv->s;
			$v = $hOhsv->v;
		}

		$h %= 360;
		$r = $v;
		$g = $v;
		$b = $v;
		if ($s > 0) {
			$h /= 60;
			$i = (int) $h;
			$f = $h - $i;
			
			switch ($i) {
			default:
			case 0:
				$g *= 1 - $s * (1 - $f);
				$b *= 1 - $s;
				break;
			case 1:
				$r *= 1 - $s * $f;
				$b *= 1 - $s;
				break;
			case 2:
				$r *= 1 - $s;
            $b *= 1 - $s * (1 - $f);
				break;
			case 3:
				$r *= 1 - $s;
				$g *= 1 - $s * $f;
				break;
			case 4:
				$r *= 1 - $s * (1 - $f);
				$g *= 1 - $s;
				break;
			case 5:
				$g *= 1 - $s;
				$b *= 1 - $s * $f;
				break;
			}
		}
		return (object) [
							  "r" => $r,
							  "g" => $g,
							  "b" => $b,
							  "a" => 1.0
							  ];
	}

	public
	function fNegate() { // 色反転
		ImageFilter($this->img, IMG_FILTER_NEGATE);
	}

	public
	function fBrightness($brightness = 0.0) { // 輝度 -1.0 〜 1.0
		ImageFilter($this->img, IMG_FILTER_BRIGHTNESS,
						(int) (255 * $brightness));
	}

	public
	function fContrast($contrast) {
		ImageFilter($this->img, IMG_FILTER_CONTRAST, $contrast);
	}

	public
	function fColorize($r = 0.0, $g = 0.0, $b = 0.0, $a = 1.0) {
		ImageFilter($this->img, IMG_FILTER_COLORIZE,
						(int) (255 * $r),
						(int) (255 * $g),
						(int) (255 * $b),
						(int) (int) (127-127*$a));
	}

	public
	function fEdgedetect() {
		ImageFilter($this->img, IMG_FILTER_EDGEDETECT);
	}

	public
	function fEmboss() {
		ImageFilter($this->img, IMG_FILTER_EMBOSS);
	}

	public
	function fGaussianblur($n = 1) {
		for ($i = 0; $i < $n; $i++) {
			ImageFilter($this->img, IMG_FILTER_GAUSSIAN_BLUR);
		}
	}

	public
	function fSelectiveblur($n = 1) {
		for ($i = 0; $i < $n; $i++) {
			ImageFilter($this->img, IMG_FILTER_SELECTIVE_BLUR);
		}
	}

	public
	function fMeanremoval($n = 1) {
		for ($i = 0; $i < $n; $i++) {
			ImageFilter($this->img, IMG_FILTER_MEAN_REMOVAL);
		}
	}

	public
	function fSmooth($weight) { // $weight (0.0〜2048)
		return ImageFilter($this->img, IMG_FILTER_SMOOTH, $weight);
	}

	public
	function fPixelate($size, $advanced = true) {
		ImageFilter($this->img, IMG_FILTER_PIXELATE,
						$size, $advanced);
	}

	public
	function flipV() {
		ImageFlip($this->img, IMG_FLIP_VERTICAL);
	}

	public
	function flipH() {
		ImageFlip($this->img, IMG_FLIP_HORIZONTAL);
	}
	
	public
	function draw($image, $dx = 0, $dy = 0) {
		if (gettype($image) == "string") {
			$file = $image;
			$image = new Image();
			$image->file($file);
		}
		ImageCopy($this->img, $image->img,
					 $dx, $dy, // dest pos
					 0, 0,
					 $image->width, $image->height);
	}
	public
	function drawRect($image, $srect, $dx = 0, $dy = 0, $pct = 1.0) {
		if (gettype($image) == "string") {
			$file = $image;
			$image = new Image();
			$image->file($file);
		}
		if ($pct == 1.0) {
			ImageCopy($this->img, $image->img,
						 $dx, $dy, // dest pos
						 $srect->x, $srect->y,
						 $srect->width,
						 $srect->height);
		}
		else {
			ImageCopyMerge($this->img, $image->img,
								$dx, $dy, // dest pos
								$srect->x, $srect->y,
								$srect->width,
								$srect->height,
								(int) ($pct * 100 + 0.5) );
		}
	}
	
	public
	function disp() {
		header("Content-type: image/png");
		header("Cache-control: no-cache");
		ImageSaveAlpha($this->img, TRUE);
		ImagePng($this->img);
	}

	public
	function save($file, $quality = 0.92) {
		if (preg_match('/\.(.+)$/', $file, $matches)) {
			// echo var_dump($matches);
			$mime = $matches[1];
			$mime = mb_strtolower($mime);

			ImageSaveAlpha($this->img, TRUE);
			switch ($mime) {
			case 'png':
				ImagePng($this->img, $file);
				break;
			case 'jpg':
			case 'jpeg':
				ImageJpeg($this->img, $file, (int) ($quality * 100));
				break;
			case 'gif':
				ImageGif($this->img, $file);
				break;
			default:
				error_log("Unknown mime type -> " . $mime);
			}
		}
		else {
			error_log("Unknown extension -> " . $file);
		}
	}

	public
	function dataUriScheme($mime = 'png', $quality = 0.92) {
		ImageSaveAlpha($this->img, TRUE);

		$mime = mb_strtolower($mime);
		ob_start();
		$base64 = null;
		$flag = true;
		
		switch($mime) {
		case 'png':
			ImagePng($this->img);
			break;
		case 'jpg':
			$mime = 'jpeg';
		case 'jpeg':
			ImageJpeg($this->img, null, (int) ($quality * 100));
			break;
		case 'gif':
			ImageGif($this->img);
			break;
		defaut:
			$flag = false;
			break;
		}
		if ($flag) {
			$base64 = base64_encode(ob_get_contents());
			ob_end_clean();
			return "data:image/" . $mime . ";base64," . $base64;
		}
		else {
			ob_end_clean();
		}
		return null;
	}

	public
	function gdResource() {
		return $this->img;
	}

	public // ret rgba (0.0〜1.0)
	function getRgba($x, $y) {
		$pix = ImageColorAt($this->img, $x, $y);
		return (object) [ "r" => (($pix >> 16) & 0xff) / 255,
								"g" => (($pix >> 8)  & 0xff) / 255,
								"b" => (($pix >> 0)  & 0xff) / 255,
								"a" => ((127 - ($pix >> 24) & 0xff)) / 127 ];
	}

	private
	function hsvproc($callback, $par1 = 0, $par2 = 0) {
		$size = $this->getSize();
		for ($y = 0; $y < $size->height; $y++) {
			for ($x = 0; $x < $size->width; $x++) {
				$col = $this->getRgba($x, $y);
				$hsv = $this->rgb2hsv($col);

				$callback($hsv, $par1, $par2);

				$cola = $this->hsv2rgb($hsv);
				$cola->a = $col->a;
				$colb = $this->packColor($cola);
				ImageSetPixel($this->img, $x, $y, $colb);
			}
		}
	}

	public // 色相を回す // $rotate (デグリー 0〜360)
	function rotateHue($rotate = 240) {
		$this->hsvproc(function ($hsv, $rot) {
								$hsv->h += $rot;
							}, $rotate);
	}

	public // 彩度明度に値を乗算 // 彩度 xS (0.0〜) 明度 xV (0.0〜)
	function xSV($xS = 1.0, $xV = 1.0) {
		$this->hsvproc(function ($hsv, $xs, $xv) {
								$hsv->s = min($hsv->s * $xs, 1.0);
								$hsv->v = min($hsv->v * $xv, 1.0);
							}, $xS, $xV);
	}

	public // 彩度を指定値にする
	function setSaturation($saturation) {
		$this->hsvproc(function ($hsv, $satu) {
								$hsv->s = $satu;
							}, $saturation);
	}

	public // 色相を指定値にする
	function setHue($angle) {
		$this->hsvproc(function ($hsv, $ang) {
								$hsv->h = $ang;
							}, $angle);
	}

	// 色相・彩度でセピアにするには(順番大事)
	//  [彩度] の値を 45
	//  [色相] の値を 30
	// 
	// $image->setSaturation(0.45);
	// $image->setHue(30);
	// 

	public // 差分画像を作成(違う部分だけを抜き出した画像 同じ部分はアルファ0としている)
	function diffImage($image, $rect = null) {
		$as = $this->getSize();
		$bs = $image->getSize();
		if ($as->width  != $bs->width ||
			 $as->height != $bs->height) {
			echo "Error: size no match\n";
			return false;
		}
		if ($rect) {
			$trect = $this->getRect(); // x y を最大 width height を最小として使う
			$rect->width  = $trect->x;
			$rect->height = $trect->y;
			$rect->x      = $trect->width;
			$rect->y      = $trect->height;
		}

		$transparent_count = 0; // alpha != 0 && alpha != 1
		$dimg = new Image();
		$dimg->create($as->width, $as->height);

		for ($y = 0; $y < $as->height; $y++) {
			for ($x = 0; $x < $as->width; $x++) {
				$a_rgba = $this->getRgba($x, $y);
				$b_rgba = $image->getRgba($x, $y);
				if ($b_rgba->a > 0.0 &&
					 ($a_rgba->r != $b_rgba->r ||
					  $a_rgba->g != $b_rgba->g ||
					  $a_rgba->b != $b_rgba->b)) {
					$col = $this->packColor($b_rgba);
					ImageSetPixel($dimg->img, $x, $y, $col);

					if ($rect) {
						$rect->x      = min($x, $rect->x);
						$rect->width  = max($x, $rect->width);
						$rect->y      = min($y, $rect->y);
						$rect->height = max($y, $rect->height);
					}
						
					if ($b_rgba->a != 1.0) {
						$transparent_count++;
					}
				}
			}
		}
	
		if ($transparent_count) {
			echo "WARNING: transparent cont $transparent_count\n";
		}
		if ($rect) {
			if ($rect->x <= $rect->width) {
				$rect->width  -= $rect->x;
				$rect->height -= $rect->y;
				$rect->width++;
				$rect->height++;
			}
			else {
				$rect->x      = -1;
				$rect->y      = -1;
				$rect->width  = 0;
				$rect->height = 0;
			}
		}

		return $dimg;
	}

	private
	function isPixel($x, $y) {
		return ((ImageColorAt($this->img, $x, $y) >> 24) & 0x7f) != 127 ? 1 : 0;
	}

	public
	function shapeFill($x, $y) {
		ImageFill($this->img, $x, $y, $this->shapeColor);
	}
	
	public // アルファ抜き画像の輪郭画像
	function shapeOutlinesImage() {
		$size = $this->getSize();
		$oimage = new Image($size->width, $size->height);
		for ($y = 0; $y < $size->height; $y++) {
			for ($x = 0; $x < $size->width; $x++) {
				$bottom = $top = $right = $left = $pix = $this->isPixel($x, $y);
				if ($x) $left = $this->isPixel($x - 1, $y);
				if ($x + 1 < $size->width) $right = $this->isPixel($x + 1, $y);
				if ($y) $top = $this->isPixel($x, $y - 1);
				if ($y + 1 < $size->height) $bottom = $this->isPixel($x, $y + 1);
				
				if ($pix != $left ||
					 $pix != $right ||
					 $pix != $top ||
					 $pix != $bottom) {
					ImageSetPixel($oimage->img, $x, $y, $this->shapeColor);
				}
			}
		}
		return $oimage;
	}
	
	public // alpha 0 then rgb → 0 // return clean count
	function clean() {
		$size = $this->getSize();
		$cleancnt = 0;
		
		for ($y = 0; $y < $size->height; $y++) {
			for ($x = 0; $x < $size->width; $x++) {
				$rgba = $this->getRgba($x, $y);
				$cn = $rgba->r + $rgba->g + $rgba->b;
				
				if ($rgba->a == 0.0 && $cn != 0.0) {
					$cleancnt++;
					$rgba->r = 0.0;
					$rgba->g = 0.0;
					$rgba->b = 0.0;
					$col = $this->packColor($rgba);
					ImageSetPixel($this->img, $x, $y, $col);
				}
			}
		}
		return $cleancnt;
	}

	public
	function shapeRect($fill, $rectOx, $y = 0, $ex = 0, $ey = 0) {
		$x = $rectOx;
		if (gettype($rectOx) == "object") {
			$x = $rectOx->x;
			$y = $rectOx->y;
			$ex = $x + $rectOx->width;
			$ey = $y + $rectOx->height;
		}
		$fill ?
			ImageFilledRectangle($this->img, $x, $y, $ex, $ey, $this->shapeColor) :
			ImageRectangle($this->img, $x, $y, $ex, $ey, $this->shapeColor);
	}

	public
	function shapeLine($x, $y, $ex, $ey) {
		ImageLine($this->img, $x, $y, $ex, $ey, $this->shapeColor);
	}

	public
	function shapeEllipse($fill, $rectOcx, $cy = 0, $width = 0, $height = 0) {
		$cx = $rectOcx;
		if (gettype($rectOcx) == "object") {
			$cx = $rectOcx->x;
			$cy = $rectOcx->y;
			$width  = $rectOcx->width;
			$height = $rectOcx->height;
		}
		$fill ?
			ImageFilledEllipse($this->img, $cx, $cy, $width, $height, $this->shapeColor) :
			ImageEllipse($this->img, $cx, $cy, $width, $height, $this->shapeColor);
	}

	public // RegularPolygonN::  $vertexNum: 頂点数 中心座標 $cx $cy 半径 $r
	function shapeRegularPolygon($vertexNum, $cx, $cy, $r, $rotateDeg = 0, $fill = 0) {
		if ($vertexNum <= 0) return ;
		$angle = M_PI * 2 / $vertexNum;
		$r270 = M_PI * 2 / 4 * 3 + deg2rad($rotateDeg);
		
		$vs = array();
		for ($n = 0; $n < $vertexNum; $n++) {
			$x = $cx + cos($r270 + $angle * $n) * $r;
			$y = $cy + sin($r270 + $angle * $n) * $r;
			array_push($vs, $x, $y);
		}
		$fill ?
			ImageFilledPolygon($this->img, $vs, count($vs) / 2, $this->shapeColor) :
			ImagePolygon($this->img, $vs, count($vs) / 2, $this->shapeColor);
	}

	public
	function replaceColor($rgba, $torgba = null) {
		$col = $this->packColor($rgba);
		$tocol = $torgba ?
			$this->packColor($torgba):
			$this->packColor(); // ragba 0,0,0,0

		imagealphablending($this->img, false);
		for ($y = 0; $y < $this->height; $y++) {
			for ($x = 0; $x < $this->width; $x++) {
				 $ncol = ImageColorAt($this->img, $x, $y);
				 if ($ncol == $col) {
					 ImageSetPixel($this->img, $x, $y, $tocol);
				 }
			}
		}
		imagealphablending($this->img, true);
	}

	
}
