<?php

namespace akiki\gd\bmp;

use akiki\gd\bmp;

class File {
	private $file;
	private $name;
	
	function __construct() {
		$this->file = null;
		$this->name = null;
	}
	function __destruct() {
		if ($this->file) fclose($this->file);
	}
	public
	function open($filename, $mode = "rb") {
		$this->file = fopen($filename, $mode);
		return $this->file != null;
	}
	public
	function read($bytes) {
		if (!$this->file) return ""; // nullの方がいいか？
		return fread($this->file, $bytes);
	}
}

function ImageCreateFromBMP($filename) {
	$file = new File();
	if (!$file->open($filename)) {
		echo "Error: $filename not found \n";
		return false;
	}

	// unpack v:uint16 V:uint32 リトルエンディアン
	// --file header
	$data = $file->read(14);
	$fileHeader = (object) unpack('vtype/Vsize/Vreserved/VbitmapOffset', $data);
	$t = (object) unpack('vtype', 'BM');
	if ($fileHeader->type != $t->type) {
		echo "Error: type\n";
		return false;
	}

	$data = $file->read(4);
	$info = (object) unpack('Vsize', $data);

	// --info header
	$infoHeader = null;
	if ($info->size >= 40) {
		// win  40以降？
		$data = $file->read($info->size - 4);
		$infoHeader = (object) unpack('Vwidth/Vheight/vplanes/vbitPixel/Vcompression/' .
												'VsizeImage/VpixPerMeterX/VpicPerMeterY/VclutUsed/VclutImportant',
												$data);
	}
	elseif ($info->size == 12) {
		// os2
		$data = $file->read(12 - 4);
		$infoHeader = (object) unpack('vwidth/vheight/vplanes/vbitPixel');
	}
	else {
		echo "Error: info $info->size \n";
		return false;
	}
	if (!$infoHeader->sizeImage) {
		$infoHeader->sizeImage = $fileHeader->size - $fileHeader->bitmapOffset;
	}

	$infoHeader->colors = 1 << $infoHeader->bitPixel; // 色数

	// 4bytes alignment -> 32bit
	$infoHeader->decalBit = 32 - $infoHeader->width * $infoHeader->bitPixel % 32;
	if ($infoHeader->decalBit == 32) $infoHeader->decalBit = 0;
	$infoHeader->decal = $infoHeader->decalBit >> 3;
	
	// --palette
	$palette = null;
	if ($infoHeader->bitPixel < 16) {
		$data = $file->read($infoHeader->colors * 4);
		$palette = unpack('V' . $infoHeader->colors, $data);
	}

	// --image
	$img = $file->read($infoHeader->sizeImage);
	$gd = ImageCreateTrueColor($infoHeader->width, $infoHeader->height);

	switch ($infoHeader->bitPixel) {
	case 32: // 32bit color
		image32bit($gd, $infoHeader, $img); break;
	case 24: // 24bit color
		image24bit($gd, $infoHeader, $img); break;
	case 16: // 16bit color (palette)
		image16bit($gd, $infoHeader, $img); break;
	case 8:  // 8bit color  (palette) 256色
		image8bit($gd, $infoHeader, $img, $palette);  break;
	case 4:  // 4bit color  (palette)  16色
		image4bit($gd, $infoHeader, $img, $palette);  break;
	case 1:  // 1bit color  (palette)   2色 (モノクロカラー)
		image1bit($gd, $infoHeader, $img, $palette);  break;
	}

	return $gd; // resource gd image
}

function image32bit($gd, $infoHeader, $img) {
	$p = 0;
	for ($y = $infoHeader->height - 1; $y >= 0; $y--) {
		// 4byte --> 1pixel
		for ($x = 0; $x < $infoHeader->width; $x++) {
			$col = unpack("V", substr($img, $p, 4));
			ImageSetPixel($gd, $x, $y, $col[1]);
			$p += 4;
		}
		$p	+= $infoHeader->decal;
	}
}

function image24bit($gd, $infoHeader, $img) {
   $rs = chr(0);
	$p = 0;
	for ($y = $infoHeader->height - 1; $y >= 0; $y--) {
		// 3byte --> 1pixel
		for ($x = 0; $x < $infoHeader->width; $x++) {
			$col = unpack("V", substr($img, $p, 3) . $rs);
			ImageSetPixel($gd, $x, $y, $col[1]);
			$p += 3;
		}
		$p	+= $infoHeader->decal;
	}
}

function image16bit($gd, $infoHeader, $img) {
	$p = 0;
	for ($y = $infoHeader->height - 1; $y >= 0; $y--) {
		// 2byte --> 1pixel (argb 1555)
		for ($x = 0; $x < $infoHeader->width; $x++) {
			$col = unpack("v", substr($img, $p, 2));

			$a = ($col[1] >> 15 & 0x1) ? 0 : 127; // 注:逆になるかも
			$r = ($col[1] >> 10 & 0x1f) << 3; // 注:rgbの順番が逆かも
			$g = ($col[1] >> 5  & 0x1f) << 3;
			$b = ($col[1] >> 0  & 0x1f) << 3;

			$col = ImageColorAllocateAlpha($gd, $r, $g, $b, $a);
			ImageSetPixel($gd, $x, $y, $col);
			$p += 2;
		}
		$p	+= $infoHeader->decal;
	}
}

function image8bit($gd, $infoHeader, $img, $palette) {
	$p = 0;
	if ($infoHeader->compression == 1) {
		// 圧縮展開 rel8 (run length encoded)
		$uimg = array();
		for ($p = 0; $p < $infoHeader->sizeImage;) {
			$pack = unpack("C2", substr($img, $p, 2));
			$p += 2;

			// コード化モード(pack[1] > 0)
			if ($pack[1]) {
				for ($n = 0; $n < $pack[1]; $n++) {
					array_push($uimg, $pack[2]);
				}
			}
			else {
				// 絶対モード
				$len = $pack[2];
				if ($len >= 3) {
					$pack = unpack("C" . $len, substr($img, $p, $len));
					for ($n = 1; $n <= $len; $n++) {
						array_push($uimg, $pack[$n]);
					}
					$p += $len + ($len & 0x01);
				}
				elseif ($len == 2) {
					// データのオフセット
					$pack = unpack("C2", substr($img, $p, 2));
					// echo var_dump($pack);
					// echo "test\n";
					echo "Error: rel8 dataoffset not compatible\n";
					exit(1);
				}
				else {
					// line 終端
				}
			}
		}
	
		$p = 0;
		for ($y = $infoHeader->height - 1; $y >= 0; $y--) {
			// 1byte --> 1pixel
			for ($x = 0; $x < $infoHeader->width; $x++) {
				$col = $palette[$uimg[$p] + 1];
				ImageSetPixel($gd, $x, $y, $col);
				$p++;
			}
		}
	}
	else {
		for ($y = $infoHeader->height - 1; $y >= 0; $y--) {
			// 1byte --> 1pixel
			for ($x = 0; $x < $infoHeader->width; $x++) {
				$pal = unpack("C", substr($img, $p, 1));
				$col = $palette[$pal[1] + 1];
				ImageSetPixel($gd, $x, $y, $col);
				$p += 1;
			}
			$p	+= $infoHeader->decal;
		}
	}
}

function image4bit($gd, $infoHeader, $img, $palette) {
	$p = 0;
	if ($infoHeader->compression == 2) {
		$uimg = array();
		// rel4 (run length encoded)
		// 圧縮展開 rel8 (run length encoded)
		for ($p = 0; $p < $infoHeader->sizeImage;) {
			$pack = unpack("C2", substr($img, $p, 2));
			$p += 2;

			// コード化モード(pack[1] > 0)
			if ($pack[1]) {
				$pid = array($pack[2] >> 4 & 0x0f,
								 $pack[2] >> 0 & 0x0f
								 );
				for ($n = 0; $n < $pack[1]; $n++) {
					array_push($uimg, $pid[$n & 0x01]);
				}
			}
			else {
				// 絶対モード
				$len = $pack[2];
				if ($len >= 2) {
					$plen = ($len + 1) >> 1;
					$pack = unpack("C" . $plen, substr($img, $p, $plen));
					for ($n = 0; $n < $len; $n++) {
						if ($n & 0x01) {
							array_push($uimg, $pack[($n >> 1)+1] & 0x0f);
						}
						else {
							array_push($uimg, $pack[($n >> 1)+1] >> 4 & 0x0f);
						}
					}
					$p += ($len + 1) >> 1;
					if (($len + 1) >> 1 & 0x1) {
						$p++;
					}
				}
				else {
					// line 終端
				}
			}
		}

		$p = 0;
		for ($y = $infoHeader->height - 1; $y >= 0; $y--) {
			for ($x = 0; $x < $infoHeader->width; $x++) {
				$col = $palette[$uimg[$p] + 1];
				ImageSetPixel($gd, $x, $y, $col);
				$p++;
			}
		}
	}
	else {
		for ($y = $infoHeader->height - 1; $y >= 0; $y--) {
			// 1byte --> 2pixels
			for ($x = 0; $x < $infoHeader->width; $x += 2) {
				$pal = unpack("C", substr($img, $p, 1));
				$col1 = $palette[(($pal[1] >> 4) & 0xf) + 1];
				$col2 = $palette[(($pal[1] >> 0) & 0xf) + 1];
				ImageSetPixel($gd, $x + 0, $y, $col1);
				if ($x + 1 < $infoHeader->width) ImageSetPixel($gd, $x + 1, $y, $col2);
				$p += 1;
			}
			$p	+= $infoHeader->decal;
		}
	}
}

function image1bit($gd, $infoHeader, $img, $palette) {
	$p = 0;
	for ($y = $infoHeader->height - 1; $y >= 0; $y--) {
		// 1byte --> 8pixels
		for ($x = 0; $x < $infoHeader->width; $x += 8) {
			$pal = unpack("C", substr($img, $p, 1));
			$bm = $x + 8 < $infoHeader->width ? 8 : $infoHeader->width - $x;
			for ($b = 0; $b < $bm; $b++) {
				$col = $palette[(($pal[1] >> (7 - $b)) & 0x1) + 1];
				ImageSetPixel($gd, $x + $b, $y, $col);
			}
			$p += 1;
		}
		$p	+= $infoHeader->decal;
	}
}

