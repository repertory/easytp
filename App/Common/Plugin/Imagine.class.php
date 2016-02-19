<?php
namespace Common\Plugin;
use Common\Plugin\Imagine\Imagick;
use Common\Plugin\Imagine\Gmagick;
use Common\Plugin\Imagine\Gd;
use Common\Plugin\Imagine\Image\Point;
use Common\Plugin\Imagine\Image\Box;
use Common\Plugin\Imagine\Image\Palette\RGB;

class Imagine {
	private $param     = array();

	private $imageType = array(
		0  => 'UNKNOWN',
		1  => 'GIF',
		2  => 'JPEG',
		3  => 'PNG',
		4  => 'SWF',
		5  => 'PSD',
		6  => 'BMP',
		7  => 'TIFF_II',
		8  => 'TIFF_MM',
		9  => 'JPC',
		10 => 'JP2',
		11 => 'JPX',
		12 => 'JB2',
		13 => 'SWC',
		14 => 'IFF',
		15 => 'WBMP',
		16 => 'XBM',
		17 => 'ICO',
		18 => 'COUNT',
	);

	//水印默认配置
	private $water     = array(
		'status'   => 0,         //状态
		'type'     => 0,         //模式 1为图片 0为文字
		'text'     => 'EASYTP',  //水印文字
		'image'    => '',        //水印图片，必须在UPLOAD_PATH目录下
		'position' => 9,         //九宫格位置
		'x'        => 0,         //x轴偏移
		'y'        => 0,         //y轴偏移
		'size'     => 30,        //水印文字大小
		'color'    => '#305697', //水印文字颜色
		'font'     => 'yuppy.otf',
	);

	/**
	 * 构造函数
	 */
	public function __construct(){
		if(class_exists('Imagick')){
			$this->lib     = 'imagick';
			$this->imagine = new Imagick\Imagine();
		}elseif(class_exists('Gmagick')){
			$this->lib     = 'gmagick';
			$this->imagine = new Gmagick\Imagine();
		}else{
			$this->lib     = 'gd';
			$this->imagine = new Gd\Imagine();
		}
	}

	public function __set($key, $value){
		$this->param[$key] = $value;
	}

	public function __get($key){
		return $this->param[$key];
	}

	/**
	 * 析构函数
	 */
	public function __destruct(){
		if($this->param !== null) $this->param = array();
	}

	/**
	 * 读取图片文件
	 * @param $path
	 */
	public function open($path){
		$this->image = $this->imagine->open($path);
		$this->info  = getimagesize($path);
	}

	/**
	 * 读取图片内容
	 * @param $string
	 */
	public function load($string){
		$this->image = $this->imagine->load($string);
		$this->info  = getimagesizefromstring($string);
	}

	/**
	 * 获取图片内容
	 * @param string $format
	 * @param array $options
	 * @return string
	 */
	public function get($format = '', array $options = array()){
		if(empty($format)) $format = $this->getFormat();

		if(strtolower($format) == 'gif'){
			$options = array_merge(array('animated' => true), $options);
		}

		return $this->image->get($format, $options);
	}

	/**
	 * 输出图片
	 * @param string $format
	 * @param array $options
	 * @return mixed
	 */
	public function show($format = '', array $options = array()){
		if(empty($format)) $format = $this->getFormat();

		if(strtolower($format) == 'gif'){
			$options = array_merge(array('animated' => true), $options);
		}

		return $this->image->show($format, $options);
	}

	/**
	 * 保存图片
	 * @param string|null $path
	 * @param array $options
	 * @return mixed
	 */
	public function save($path = null, array $options = array()){
		$format = $this->getFormat();

		if(strtolower($format) == 'gif'){
			$options = array_merge(array('animated' => true), $options);
		}

		return $this->image->save($path, $options);
	}

	/**
	 * 创建一个纯色图片
	 * @param $width
	 * @param $height
	 * @param $color
	 * @param $alpha  (透明度 0-100) 0为透明
	 * @return object
	 */
	public function create($width, $height, $color = '#ffffff', $alpha = 100){
		if(!empty($color) && is_string($color)){
			$palette = new RGB();
			$color   = $palette->color(ltrim($color, '#'), $alpha);
		}
		return $this->imagine->create(new Box($width, $height), $color);
	}

	/**
	 * 获取图片尺寸
	 * @return array
	 */
	public function getSize(){
		return array_slice($this->info, 0, 2);
	}

	/**
	 * 获取图片格式
	 * @return string
	 */
	public function getFormat(){
		$type = $this->info[2];
		return isset($this->imageType[$type]) ? $this->imageType[$type] : 'UNKNOWN';
	}

	/**
	 * 获取图片mimetype
	 * @return string
	 */
	public function getMime(){
		return $this->info['mime'];
	}

	/**
	 * 获取九宫格位置
	 * @param $w            内容宽度
	 * @param $h            内容高度
	 * @param int $position 九宫格位置
	 * @param int $x        x轴偏移量
	 * @param int $y        y轴偏移量
	 * @param int $width    容器宽度
	 * @param int $height   容器高度
	 * @return object
	 */
	public function getPosition($w, $h, $position = 9, $x = 0, $y = 0, $width = 0, $height = 0){
		if(empty($width) || empty($height)) list($width, $height) = $this->getSize();

		switch($position){
			case 1:
				$x = $x;
				$y = $y;
				break;
			case 2:
				$x = ceil(($width - $w) / 2) + $x;
				$y = $y;
				break;
			case 3:
				$x = $width - $w + $x;
				$y = $y;
				break;
			case 4:
				$x = $x;
				$y = ceil(($height - $h) / 2) + $y;
				break;
			case 5:
				$x = ceil(($width - $w) / 2) + $x;
				$y = ceil(($height - $h) / 2) + $y;
				break;
			case 6:
				$x = $width - $w + $x;
				$y = ceil(($height - $h) / 2) + $y;
				break;
			case 7:
				$x = $x;
				$y = $height - $h + $y;
				break;
			case 8:
				$x = ceil(($width - $w) / 2) + $x;
				$y = $height - $h + $y;
				break;
			case 9:
				$x = $width - $w + $x;
				$y = $height - $h + $y;
				break;
			default:
				$x = $width - $w + $x;
				$y = $height - $h + $y;
		}
		$x = max($x, 0);
		$y = max($y, 0);
		return new Point(min($x, $width), min($y, $height));
	}

	/**
	 * 添加水印(图片或文字)
	 * @param array $config
	 */
	public function watermark(array $config = array()){
		if(empty($config)) $config = C('IMAGE_WATER_CONFIG');
		$config = array_merge($this->water, $config);

		if($config['status']){
			$config['type'] ? $this->waterImage($config) : $this->waterText($config);
		}
	}

	/**
	 * 添加水印图片
	 * @param array $config
	 */
	public function waterImage(array $config = array()){
		$config = array_merge($this->water, $config);

		if(!$config['status']) return false;

		if(file_exist($config['image'])){
			$watermark  = $this->imagine->open($config['image']); //本地水印图片
		}else{
			$path = C('TMPL_PARSE_STRING');
			$file = str_replace($path[UPLOAD_PATH], UPLOAD_PATH, $config['image']);
			if(!file_exist($file)) return false;

			$waterImage = file_read($file);
			$watermark  = $this->imagine->load($waterImage); //水印图片
		}

		$water    = $watermark->getSize();            //水印图片尺寸
		$position = $this->getPosition($water->getWidth(), $water->getHeight(), $config['position'], $config['x'], $config['y']);

		//如果水印不能完整显示，则不添加水印
		list($width, $height) = $this->getSize();
		if($water->getWidth() + $position->getX() > $width) return false;
		if($water->getHeight() + $position->getY() > $height) return false;

		$format = $this->getFormat();

		if($this->lib != 'gd' && strtolower($format) == 'gif'){
			$this->image->layers()->coalesce();
			foreach ($this->image->layers() as $frame) {
				$frame->paste($watermark, $position);
			}
		}else{
			$this->image->paste($watermark, $position);
		}
	}

	/**
	 * 添加水印文字
	 * @param array $config
	 */
	public function waterText(array $config = array()){
		$config = array_merge($this->water, $config);

		if(!$config['status']) return false;

		$file     = COMMON_PATH . 'Font/' . $config['font'];
		$size     = $config['size'];
		$color    = $this->image->palette()->color(ltrim($config['color'], '#'));
		$text     = $config['text'];
		$angle    = 0;

		$font     = $this->imagine->font($file, $size, $color);

		$water    = $font->box($config['text'], $angle);
		$position = $this->getPosition($water->getWidth(), $water->getHeight(), $config['position'], $config['x'], $config['y']);

		//如果水印不能完整显示，则不添加水印
		list($width, $height) = $this->getSize();
		if($water->getWidth() + $position->getX() > $width) return false;
		if($water->getHeight() + $position->getY() > $height) return false;

		$format   = $this->getFormat();

		if($this->lib != 'gd' && strtolower($format) == 'gif'){
			$this->image->layers()->coalesce();
			foreach ($this->image->layers() as $frame) {
				$frame->draw()->text($text, $font, $position, $angle);
			}
		}else{
			$this->image->draw()->text($text, $font, $position, $angle);
		}
	}

	/**
	 * 图片缩放
	 * @param int $w
	 * @param int $h
	 * @param string $filter
	 * # filter可选项:
	 *  point,box,triangle,hermite,hanning,hamming,blackman,gaussian,quadratic,cubic,catrom,mitchell,lanczos,bessel,sinc
	 */
	public function resize($w, $h , $filter = 'undefined'){
		$format   = $this->getFormat();

		$w = ceil($w);
		$h = ceil($h);

		if($this->lib != 'gd' && strtolower($format) == 'gif'){
			$this->image->layers()->coalesce();
			foreach ($this->image->layers() as $frame) {
				$frame->resize(new Box($w, $h), $filter);
			}
		}else{
			$this->image->resize(new Box($w, $h), $filter);
		}
	}

	/**
	 * 图片裁剪
	 * @param int $x
	 * @param int $y
	 * @param int $w
	 * @param int $h
	 * @param int $width
	 * @param int $height
	 */
	public function crop($x, $y, $w, $h, $width = 0, $height = 0){
		$format   = $this->getFormat();

		$x = ceil($x);
		$y = ceil($y);
		$w = ceil($w);
		$h = ceil($h);

		if($this->lib != 'gd' && strtolower($format) == 'gif'){
			$this->image->layers()->coalesce();
			foreach ($this->image->layers() as $frame) {
				if($width && $height) $frame->resize(new Box($width, $height));
				$frame->crop(new Point($x, $y), new Box($w, $h));
			}
		}else{
			if($width && $height) $this->image->resize(new Box($width, $height));
			$this->image->crop(new Point($x, $y), new Box($w, $h));
		}
	}

	/**
	 * 清除图片信息
	 */
	public function strip(){
		$this->image->strip();
	}

	/**
	 * 旋转
	 * @param int $angle 旋转角度
	 * @param string $background 默认为白色
	 */
	public function rotate($angle, $background = null){
		if(!empty($background) && is_string($background)){
			$background = $this->image->palette()->color(ltrim($background, '#'));
		}
		$this->image->rotate($angle, $background);
	}

	/**
	 * 翻转
	 * @param string $type  v:垂直翻转 h:水平翻转
	 */
	public function flip($type = 'v'){
		if($type == 'h'){
			$this->image->flipHorizontally();
		}else{
			$this->image->flipVertically();
		}
	}

	/**
	 * 缩略图
	 * @param int $width
	 * @param int $height
	 * @param string $type  (scale|scale_color|scale_fill|force)
	 * @param string $color
	 * @param int $alpha    (透明度 0-100)
	 * @param string $filter
	 */
	public function thumb($width = 240, $height = 320, $type = 'force', $color = '#ffffff', $alpha = 100, $filter = 'undefined'){
		switch($type){
			//按比例在安全框内缩放图片，在区域内缩放 (可能小与当前尺寸)
			case 'scale':
				$w = $width;
				$h = $height;
				list($oWidth, $oHeight) = $this->getSize();

				if($oWidth * $h > $oHeight * $w){
					$h = ceil($w * $oHeight / $oWidth);
				}else{
					$w = ceil($h * $oWidth / $oHeight);
				}
				$this->resize($w, $h, $filter);
				break;

			//按比例在安全框内缩放图片，在区域内缩放，同时将空白地方已颜色填充(尺寸与设置一致)
			case 'scale_color':
				$w = $width;
				$h = $height;
				list($oWidth, $oHeight) = $this->getSize();

				if($oWidth * $h > $oHeight * $w){
					$h = ceil($w * $oHeight / $oWidth);
				}else{
					$w = ceil($h * $oWidth / $oHeight);
				}

				$position = $this->getPosition($w, $h, 5, 0, 0, $width, $height);

				$format = $this->getFormat();
				if($this->lib != 'gd' && strtolower($format) == 'gif'){
					$box = $this->create($width, $height, $color, 100);

					$this->image->layers()->coalesce();
					foreach ($this->image->layers() as $offset=>$frame) {
						$frame->resize(new Box($w, $h), $filter);
						$image = $box->copy()->paste($frame, $position);

						$frame->resize(new Box($width, $height), $filter);
						$frame->paste($image, new  Point(0, 0));
					}
				}else{
					$box = $this->create($width, $height, $color, $alpha);
					$this->image->resize(new Box($w, $h), $filter);
					$this->image = $box->paste($this->image, $position);
				}
				break;

			//按比例在安全框内缩放图片，填充整个区域同时裁剪超出内容(尺寸与设置一致)
			case 'scale_fill':
				$w = $width;
				$h = $height;
				list($oWidth, $oHeight) = $this->getSize();

				if($oWidth != $width){
					$h = ceil(($width / $oWidth) * $oHeight);
				}else{
					$h = $oHeight;
				}

				if($h < $height){
					$w = ceil($w / ($h / $height));
					$h = $height;
				}
				$x = ceil(($w - $width) / 2);;
				$y = ceil(($h - $height) / 2);

				$this->crop($x, $y, $width, $height, $w, $h);
				break;

			//把图片拉伸指定尺寸
			default:
				$this->resize($width, $height, $filter);
		}

	}
}