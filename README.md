
# GDライブラリを使用したクラス

	$image = new akiki\gd\Image();
	メソッドの説明には上の $image （インスタンス）を使います。
	
	カラー要素は、255段階ではなく0.0〜1.0の正規化した値を使います。
	角度は、0〜360(デグリー)を使います。

* [new インスタンス生成](#constructor)
* [file()](#file)
* [create()](#create)
* [clear()](#clear)
* [getSize()](#getSize)
* [getRect()](#getRect)
* [packColor()](#packColor)

フィルター  

* [fGrayscale()](#fGrayscale)
* [fSepia()](#fSepia)
* [fNegate()](#fNegate)
* [fBrightness()](#fBrightness)
* [fContrast()](#fContrast)
* [fColorize()](#fColorize)
* [fEdgedetect()](#fEdgedetect)
* [fEmboss()](#fEmboss)
* [fGaussianblur()](#fGaussianblur)
* [fSelectiveblur()](#fSelectiveblur)
* [fMeanremoval()](#fMeanremoval)
* [fSmooth()](#fSmooth)
* [fPixelate()](#fPixelate)

描画合成  

* [draw()](#draw)
* [drawRect()](#drawRect)

表示出力  

* [disp()](#disp)
* [save()](#save)
* [dataUriScheme()](#dataUriScheme)
* [gdResource()](#gdResource)

色操作  

* [getRgba()](#getRgba)
* [xSV()](#xSV)
* [rotateHue()](#rotateHue)
* [setHue()](#setHue)
* [setSaturation()](#setSaturation)

図形描画  

* [setShapeColor()](#setShapeColor)
* [shapeRect()](#shapeRect)
* [shapeLine()](#shapeLine)
* [shapeEllipse()](#shapeEllipse)
* [shapeRegularPolygon()](#shapeRegularPolygon)

画像操作  

* [flipV()](#flipV)
* [flipH()](#flipH)
* [clean()](#clean)
* [Image scale()](#scale)
* [Image rotate()](#rotate)
* [Image diffImage()](#diffImage)
* [Image shapeOutlinesImage()](#shapeOutlinesImage)

### <a name="constructor"> new Imageインスタンスの生成

	new akiki\gd\Image();
	new akiki\gd\Image("FILE_PATH");
	new akiki\gd\Image(WIDTH, HEIGTH);

### <a name="file"> void file(string $filepath)
ファイル読み込み(ファイルの拡張子でpng/jpg/gifを判断します。
コンストラクタで file()を呼び出しています。)

	$image->file("test.png");

### <a name="create"> void create(int $width, int $height)
指定サイズのイメージを作成(コンストラクタで create()を呼び出しています。)

	$image->create(200, 300); // 横200 縦300 の透明画像を作成

### <a name="clear"> void clear($rOrgba = 0.0, $g = 0.0, $b = 0.0, $a = 0.0)
指定したカラーで画像を消去します。(指定値は、0〜1.0 A(アルファ)0.0で完全透明 1.0で不透明)

	$image->clear(); // 透明な黒で消去
	$image->clear(1.0, 1.0, 1.0, 1.0); // 白で消去
	$rgba = (object) { "r" => 0.0, "g" => 0.0, "b" => 0.0, "a" => 0.0 };
	$image->clear($rgba); // rgba objectを使ってクリアカラーの指定

### <a name="getSize"> object getSize()
画像のサイズを取得する(object)

	$size = $image->getSize();
	echo $size->width; // 横幅
	echo $size->height;// 高さ

### <a name="getRect"> object getRect()
画像のサイズを取得する(object)

	$rect = $image->getRect();
	echo $rect->x;     // 0
	echo $rect->y;     // 0
	echo $rect->width; // 横幅
	echo $rect->height;// 高さ

### <a name="packcolor"> int packColor($rOrgba = 0.0, $g = 0.0, $b = 0.0, $a = 0.0)
指定したカラーをintにして返します。(指定値は、0〜1.0 A(アルファ)0.0で完全透明 1.0で不透明)

	$image->packColor(); // 透明な黒
	$image->packColor(1.0, 1,0, 1.0, 1.0); // 不透明な白
	$rgba = (object) { "r" => 0.0, "g" => 0.0, "b" => 0.0, "a" => 0.0 };
	$image->packColor($rgba); // rgba objectを使ってrgbaを指定(透明な黒)

### <a name="fGrayscale"> void fGrayscale()
画像をグレースケール(白黒)にする

	$image->fGrayscale();

### <a name="fSepia"> void fSepia()
画像をセピア調にする
(色相と彩度指定でもセピア調にすることもできます->xxxxx)

	$image->fSepia();

### <a name="fNegate">void fNegate()
画像を色を反転

	$image->fNegate();

### <a name="fBrightness"> void fBrightness($brightness = 0.0)
画像の輝度を変更($brightness (-1.0〜1.0))
	$image->fBrightness(1.3);

### <a name="fContrast"> void fContrast($contrast)
画像のコントラストを変更

	$image->fContrast(20);

### <a name="fColorize"> void fColorize($r = 0.0, $g = 0.0, $b = 0.0, $a = 1.0)
画像に指定カラーのフィルターをかける

	$image->fColorize(0.5, 0.0, 0.0, 1.0); // 赤みかかった画像

### <a name="fEdgedetect"> void fEdgedetect()
画像のエッジを検出し画像のエッジを強調

	$image->fEdgedetect();

### <a name="fEmboss"> void fEmboss()
画像にエンボス処理を行う

	$image->fEmboss();

### <a name="fGaussianblur"> void fGaussianblur(int $n = 1)
画像をぼかす(ガウス分布で)($n 何回かけるかの指定で多くなるとよりぼけた画像になる)

	$image->fGaussianblur(3);

### <a name="fSelectiveblur"> void fSelectiveblur(int $n = 1)
画像をぼかす($n 何回かけるかの指定で多くなるとよりぼけた画像になる)

	$image->fSelectiveblur(3);

### <a name="fMeanremoval"> void fMeanremoval(int $n = 1)
平均を除去しスケッチ風効果($n 回効果処理)

	$image->fMeanremoval(3);

### <a name="fSmooth"> void fSmooth(float $weight)
画像を滑らかにする($weight 強さの指定)

	$image->fSmooth(30);

### <a name="fPixelate"> void fPixelate(int $size, boolean $advanced = true)
画像にモザイクをかける($size モザイクサイズ)

	$image->fPixelate(5);

### <a name="draw"> void draw(Image $image, int $dx = 0, int $dy = 0)
画像に指定画像を指定位置へ描画する(コピー)
	$image->draw($image2, 50, 0);

### <a name="drawRect"> void drawRect(Image $image, object $srect, int $dx = 0, int $dy = 0, $pct = 1.0)
画像に指定画像の一部分を指定位置へ描画する(部分コピー)

	$srect = $image2->getRect();
	// $srect->x
	// $srect->y
	// $srect->width
	// $srect->height
	$image->drawRect($image2, $srect, 100, 10);
	$image->drawRect($image2, $srect, 100, 10, 0.5); // 半透明同士で合成

### <a name="disp"> void disp()
ブラウザへ描画する(画像データのみの場合だけブラウザーに表示される)

	$image->disp();

### <a name="save"> void save($file, $quality = 0.92)
ファイルへ画像を出力します（画像タイプは、拡張子で判定されます)

	$image->save("test.png");
	$image->save("test.jpg", 0.8); // $qualityは、jpgのみ使用されます

### <a name="dataUriScheme"> void dataUriScheme($mime = 'png', $quality = 0.92)
データuriスキームを得る   
data:image/png;base64,XXXXX 形式文字列データを得る
	$dataurischeme = $image->dataUriScheme('png');
	--> tag img src=$dataurischemeの内容をここに(htmlに埋め込める)

### <a name="gdResource"> Resource gdResource()
GDリソースオブジェクトを得る(PHP GDの関数を直接呼び出したい時に使用します。)
	$gdresource = $image->gdResource();

### <a name="getRgba"> object getRgba(int $x, int $y)
指定した位置の画像ピクセルカラーを得る(object:rgba (0.0〜1.0))

	$rgba = $image->getRgba(0, 0);
	// $rgba->r (0.0〜1.0)
	// $rgba->g (0.0〜1.0)
	// $rgba->b (0.0〜1.0)
	// $rgba->a (0.0〜1.0 0.0で透明)

### <a name="xSV"> void xSV($xS = 1.0, $xV = 1.0)
画像の彩度明度に値を乗算 xS(彩度0.0〜) xV(明度0.0〜)

	$image->xSV(1.3);      // 彩度を1.3倍にする
	$image->xSV(1.0, 1.3); // 明度を1.3倍にする

### <a name="rotateHue"> void rotateHue(float $angle)
画像の色相を回す($angle 0~360)

	$image->rotateHue(120);

### <a name="setHue"> void setHue(float $angle)
画像の色相を指定回転位置にする($angle 0~360)

	$image->setHue(30);

### <a name="setSaturation"> void setSaturation(float $saturation)
画像の彩度を指定値にする($saturation 0.0〜1.0)

	// 以下の２行でセピア調になる
	$image->setSaturation(0.45);
	$image->setHue(30);

### <a name="setShapeColor"> void setShapeColor($rOrgb = 0.0, $g = 0.0, $b = 0.0, $a = 1.0)
shapeXXX() 図形を描くメソッドで使用するカラーを設定する

	$image->setShapeColor(); // 不透明な黒
	$image->setShapeColor(1.0, 1,0, 1.0, 1.0); // 不透明な白
	$rgba = (object) { "r" => 0.0, "g" => 0.0, "b" => 0.0, "a" => 0.5 };
	$image->packColor($rgba); // rgba objectを使ってrgbaを指定(半透明な黒)

### <a name="shapeRect"> void shapeRect(boolean $fill, $rectOx, $y = 0, $ex = 0, $ey = 0)
画像に矩形を描画する

	$image->shapeRect(0, 10, 10, 100, 50); // 塗りつぶし無し横長矩形

	$rect = $image->getRect();
	$rect->width  /= 2;
	$rect->height /= 2;
	$image->shapeRect(1, $rect); // 塗りつぶし画像左上に画像の1/4の矩形

### <a name="shapeLine"> void shapeLine($x, $y, $ex, $ey)
画像に線を描画する
	$image->shapeLine(0, 0, 100, 100);

### <a name="shapeEllipse"> void shapeEllipse($fill, $rectOcx, $cy = 0, $width = 0, $height = 0)
画像に楕円を描画する

	// 中心座標100,100 に横幅50 高さ30 の塗りつぶした楕円を描画
	$image->shapeEllipse(1, 100, 100, 50, 30);
	
	// object rectを使った描画
	$rect = $image->getRect();
	$rect->x = 200;
	$rect->y = 200;
	$rect->width = 50;
	$rect->height = 30;
	$image->shapeEllipse(0, $rect); 

### <a name="shapeRegularPolygon"> void shapeRegularPolygon($fill, $vertexNum, $cx, $cy, $r, $rotateDeg = 0)
指定頂点数の正多角形を描画する(最初の頂点が中心座標の上になります)

	// 中心座標200,200 半径50の正六角形を塗りつぶしで描画
	$image->shapeRegularPolygon(1, 6, 200, 200, 50);
	
	// 中心座標200,200 半径50の正六角形を30度回転して枠線を描画
	$image->shapeRegularPolygon(0, 6, 200, 200, 50, 30);

### <a name="rgb2hsv"> hsv rgb2hsv($rOcol, $g = 0, $b = 0)
指定したrgbカラーのhsv objectを得る(rgb (0.0〜1.0))

	$hsv = $image->rgb2hsv(1.0, 0,0, 0,0); // 赤
	// $hsv->h // Hue:色相 (0〜360)
	// $hsv->s // Saturation:彩度 (0.0〜1.0)
	// $hsv->v // Value:明度 (0.0〜1.0)

### <a name="flipV"> void flipV()
画像を上下反転

	$image->flipV();

### <a name="flipH"> void flipH()
画像を左右反転

	$image->flipH();

### <a name="clean"> void clean()
画像の透明になっている部分を黒の透明にする(pngの圧縮効率が上がる等)

	$image->clean();

### <a name="scale"> Image scale(float $scale)
指定スケール(1.0が等倍)値でイメージの拡大縮小をして、新しく生成されたImageインスタンスを返します。

	$scale_image = $image->scale(2.0); // 2倍に拡大した画像

### <a name="rotate">Image rotate(float $angle)
指定ANGLE(デグリー0〜360)回転して、新しく生成されたImageインスタンスを返します。

	$rotate_image = $image->rotate(45); // ４５°回転した画像

### <a name="diffImage"> Image diffImage(Image $image2, object $rect = null)
画像と指定した画像の差分画像を得る(違う部分を$image2から抜き出し同じ部分をalpha透明とした画像)

	// rectを指定すると差分が生じた矩形情報が得られる
	$rect = $image->getRect();
	$diffimage = $image->diffImage($image2, $rect);

### <a name="shapeOutlinesImage"> Image shapeOutlinesImage()
アルファ抜き画像の輪郭画像を得る

	$outlinesimage = $image->shapeOutlinesImage();

