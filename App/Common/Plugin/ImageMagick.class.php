<?php
namespace Common\Plugin;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class ImageMagick {
	private $image = null;
	private $type = null;

	// 构造函数
	public function __construct(){}

	// 析构函数
	public function __destruct(){
		if($this->image!==null) $this->image->destroy();
	}

	// 载入图像文件
	public function open($path){
		$this->image = new Imagick( $path );
		if($this->image)
		{
			$this->type = strtolower($this->image->getImageFormat());
		}
		return $this->image;
	}

	// 载入图像数据
	public function read($content){
		$this->image = new Imagick();
		$this->image->readImageBlob($content);
		if($this->image){
			$this->type = strtolower($this->image->getImageFormat());
		}
		return $this->image;
	}

	// 裁剪图片
	public function crop($x=0, $y=0, $width=null, $height=null){
		if($width==null) $width = $this->image->getImageWidth()-$x;
		if($height==null) $height = $this->image->getImageHeight()-$y;
		if($width<=0 || $height<=0) return;

		if($this->type=='gif'){
			$image = $this->image;
			$canvas = new Imagick();
			$transparent = new ImagickPixel("transparent"); //透明色
			foreach($image as $frame){
				$page = $frame->getImagePage();
				$img = new Imagick();
				$img->newImage($page['width'], $page['height'], $transparent, 'gif');
				$img->compositeImage($frame, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
				$img->cropImage($width, $height, $x, $y);

				$canvas->addImage( $img );
				$canvas->setImageDelay( $img->getImageDelay() );
				$canvas->setImagePage($width, $height, 0, 0);
			}
			$image->destroy();
			$this->image = $canvas;
		}else{
			$this->image->cropImage($width, $height, $x, $y);
		}
	}

	/**
	 * 更改图像大小
	 * $fit: 适应大小方式
	 *   'force': 把图片强制变形成 $width X $height 大小
	 *   'scale': 按比例在安全框 $width X $height 内缩放图片, 输出缩放后图像大小 不完全等于 $width X $height
	 *   'scale_fill': 按比例在安全框 $width X $height 内缩放图片，安全框内没有像素的地方填充色, 使用此参数时可设置背景填充色 $bg_color = array(255,255,255)(红,绿,蓝, 透明度) 透明度(0不透明-127完全透明))
	 * 其它: 智能模能 缩放图像并载取图像的中间部分 $width X $height 像素大小
	 * $fit = 'force','scale','scale_fill' 时： 输出完整图像
	 * $fit = 图像方位值 时, 输出指定位置部分图像
	 * 字母与图像的对应关系如下:
	 *   north_west   north   north_east
	 *   west         center        east
	 *   south_west   south   south_east
	 */
	public function resize_to($width = 100, $height = 100, $fit = 'center', $fill_color = array(255,255,255,0) ){
		switch($fit){
			case 'force':
				if($this->type=='gif'){
					$image = $this->image;
					$canvas = new Imagick();
					$transparent = new ImagickPixel("transparent"); //透明色
					foreach($image as $frame){
						$page = $frame->getImagePage();
						$img = new Imagick();
						$img->newImage($page['width'], $page['height'], $transparent, 'gif');
						$img->compositeImage($frame, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
						$img->thumbnailImage( $width, $height, false );

						$canvas->addImage( $img );
						$canvas->setImageDelay( $img->getImageDelay() );
					}
					$image->destroy();
					$this->image = $canvas;
				}else{
					$this->image->thumbnailImage( $width, $height, false );
				}
				break;
			case 'scale':
				if($this->type=='gif'){
					$image = $this->image;
					$canvas = new Imagick();
					$transparent = new ImagickPixel("transparent"); //透明色
					foreach($image as $frame){
						$page = $frame->getImagePage();
						$img = new Imagick();
						$img->newImage($page['width'], $page['height'], $transparent, 'gif');
						$img->compositeImage($frame, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
						$img->thumbnailImage( $width, $height, true );

						$canvas->addImage( $img );
						$canvas->setImageDelay( $img->getImageDelay() );
					}
					$image->destroy();
					$this->image = $canvas;
				}else{
					$this->image->thumbnailImage( $width, $height, true );
				}
				break;
			case 'scale_fill':
				$size = $this->image->getImagePage();
				$src_width = $size['width'];
				$src_height = $size['height'];

				$x = 0;
				$y = 0;

				$dst_width = $width;
				$dst_height = $height;

				if($src_width*$height > $src_height*$width){
					$dst_height = intval($width*$src_height/$src_width);
					$y = intval( ($height-$dst_height)/2 );
				}else{
					$dst_width = intval($height*$src_width/$src_height);
					$x = intval( ($width-$dst_width)/2 );
				}

				$image = $this->image;
				$canvas = new Imagick();

				if(!is_array($fill_color)){
					$color = new ImagickPixel("transparent"); //透明色
				}else{
					$color = 'rgba('.$fill_color[0].','.$fill_color[1].','.$fill_color[2].','.$fill_color[3].')';
				}
				if($this->type=='gif'){
					$images = $image->coalesceImages();
					foreach($images as $frame){
						$frame->thumbnailImage( $width, $height, true );

						$draw = new ImagickDraw();
						$draw->composite($frame->getImageCompose(), $x, $y, $dst_width, $dst_height, $frame);

						$img = new Imagick();
						$img->newImage($width, $height, $color, 'gif');
						$img->drawImage($draw);

						$canvas->addImage( $img );
						$canvas->setImageDelay( $img->getImageDelay() );
						$canvas->setImagePage($width, $height, 0, 0);
					}
				}else{
					$image->thumbnailImage( $width, $height, true );

					$draw = new ImagickDraw();
					$draw->composite($image->getImageCompose(), $x, $y, $dst_width, $dst_height, $image);

					$canvas->newImage($width, $height, $color, $this->get_type() );
					$canvas->drawImage($draw);
					$canvas->setImagePage($width, $height, 0, 0);
				}
				$image->destroy();
				$this->image = $canvas;
				break;
			default:
				$size = $this->image->getImagePage();
				$src_width = $size['width'];
				$src_height = $size['height'];

				$crop_x = 0;
				$crop_y = 0;

				$crop_w = $src_width;
				$crop_h = $src_height;

				if($src_width*$height > $src_height*$width){
					$crop_w = intval($src_height*$width/$height);
				}else{
					$crop_h = intval($src_width*$height/$width);
				}

				switch($fit){
					case 'north_west':
						$crop_x = 0;
						$crop_y = 0;
						break;
					case 'north':
						$crop_x = intval( ($src_width-$crop_w)/2 );
						$crop_y = 0;
						break;
					case 'north_east':
						$crop_x = $src_width-$crop_w;
						$crop_y = 0;
						break;
					case 'west':
						$crop_x = 0;
						$crop_y = intval( ($src_height-$crop_h)/2 );
						break;
					case 'center':
						$crop_x = intval( ($src_width-$crop_w)/2 );
						$crop_y = intval( ($src_height-$crop_h)/2 );
						break;
					case 'east':
						$crop_x = $src_width-$crop_w;
						$crop_y = intval( ($src_height-$crop_h)/2 );
						break;
					case 'south_west':
						$crop_x = 0;
						$crop_y = $src_height-$crop_h;
						break;
					case 'south':
						$crop_x = intval( ($src_width-$crop_w)/2 );
						$crop_y = $src_height-$crop_h;
						break;
					case 'south_east':
						$crop_x = $src_width-$crop_w;
						$crop_y = $src_height-$crop_h;
						break;
					default:
						$crop_x = intval( ($src_width-$crop_w)/2 );
						$crop_y = intval( ($src_height-$crop_h)/2 );
				}

				$image = $this->image;
				$canvas = new Imagick();

				if($this->type=='gif'){
					$transparent = new ImagickPixel("transparent"); //透明色
					foreach($image as $frame){
						$page = $frame->getImagePage();
						$img = new Imagick();
						$img->newImage($page['width'], $page['height'], $transparent, 'gif');
						$img->compositeImage($frame, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
						$img->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
						$img->thumbnailImage( $width, $height, true );

						$canvas->addImage( $img );
						$canvas->setImageDelay( $img->getImageDelay() );
						$canvas->setImagePage($width, $height, 0, 0);
					}
				}else{
					$image->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
					$image->thumbnailImage( $width, $height, true );
					$canvas->addImage( $image );
					$canvas->setImagePage($width, $height, 0, 0);
				}
				$image->destroy();
				$this->image = $canvas;
		}
	}

	/**
	 * 添加水印
	 */
	public function watermark($config = array()){
		$config = !empty($config) ? $config : C('IMAGE_WATER_CONFIG');

		if(!$config['status']) return false;

		$position = $config['position'] ? $config['position'] : 9; //水印位置  1-9九宫格
		$x        = $config['x'];
		$y        = $config['y'];
		if($config['type']){
			if(!$config['image']) return false;
			$path  = C('TMPL_PARSE_STRING');
			$water = file_read(str_replace($path[UPLOAD_PATH], UPLOAD_PATH, $config['image']));
			unset($path);
			if(!$water) return false;

			$this->add_watermark($water, $x, $y, $position, false);
		}else{
			$water = $config['text'];
			if(!$water) return false;

			$this->add_text($water, $x, $y, $position, 0, array(
				'font'        => COMMON_PATH . 'Font/yuppy.otf',
				'font_size'   => !empty($config['size']) ? $config['size'] : 30,
				'fill_color'  => !empty($config['color']) ? $config['color'] : '#333333',
			));
		}
	}

	/**
	 * 添加水印图片
	 * @param $water         水印图片
	 * @param int $x         偏移量
	 * @param int $y         偏移量
	 * @param int $position  九宫格位置
	 * @param bool $isFile   水印为文件还是内容
	 * @return null
	 */
	public function add_watermark($water, $x = 0, $y = 0, $position = 9, $isFile = true){
		if($isFile){
			$watermark = new Imagick($water);
		}else{
			$watermark = new Imagick();
			$watermark->readImageBlob($water);
		}

		$draw = new ImagickDraw();
		if($position) $draw->setGravity($position); //设置位置
		$draw->composite($watermark->getImageCompose(), $x, $y, $watermark->getImageWidth(), $watermark->getimageheight(), $watermark);

		if($this->type=='gif'){
			$image = $this->image;
			$canvas = new Imagick();
			$transparent = new ImagickPixel("transparent"); //透明色
			foreach($image as $frame){
				$page = $frame->getImagePage();
				$img = new Imagick();
				$img->newImage($page['width'], $page['height'], $transparent, 'gif');
				$img->compositeImage($frame, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);

				$img->drawImage($draw);

				$canvas->addImage( $img );
				$canvas->setImageDelay( $img->getImageDelay() );
			}

			$image->destroy();
			$this->image = $canvas;
		}else{
			$this->image->drawImage($draw);
		}
	}

	// 添加水印文字
	public function add_text($text, $x = 0 , $y = 0, $position = 9, $angle=0, $style=array()){
		$draw = new ImagickDraw();
		if($position) $draw->setGravity($position); //设置位置

		if(isset($style['font'])) $draw->setFont($style['font']);
		if(isset($style['font_size'])) $draw->setFontSize($style['font_size']);
		if(isset($style['fill_color'])) $draw->setFillColor($style['fill_color']);
		if(isset($style['under_color'])) $draw->setTextUnderColor($style['under_color']);

		if($this->type=='gif'){
			foreach($this->image as $frame){
				$frame->annotateImage($draw, $x, $y, $angle, $text);
			}
		}else{
			$this->image->annotateImage($draw, $x, $y, $angle, $text);
		}
	}

	// 保存到指定路径
	public function save_to( $path ){
		if($this->type=='gif'){
			$this->image->writeImages($path, true);
		}else{
			$this->image->writeImage($path);
		}
	}

	// 输出图像
	public function output($header = true){
		if($header) header('Content-type: '.$this->type);
		echo $this->image->getImagesBlob();
	}

	// 获取图像内容
	public function get_content(){
		return $this->image->getImagesBlob();
	}

	public function get_width(){
		$size = $this->image->getImagePage();
		return $size['width'];
	}

	public function get_height(){
		$size = $this->image->getImagePage();
		return $size['height'];
	}

	// 设置图像类型， 默认与源类型一致
	public function set_type( $type='png' ){
		$this->type = $type;
		$this->image->setImageFormat( $type );
	}

	// 获取源图像类型
	public function get_type(){
		return $this->type;
	}

	// 当前对象是否为图片
	public function is_image(){
		return $this->image ? true : false;
	}

	// 生成缩略图 $fit为真时将保持比例并在安全框 $width X $height 内生成缩略图片
	public function thumbnail($width = 100, $height = 100, $fit = true){
		if($this->type=='gif'){
			$image = $this->image;
			$canvas = new Imagick();
			$transparent = new ImagickPixel("transparent"); //透明色
			foreach($image as $frame){
				$page = $frame->getImagePage();
				$img = new Imagick();
				$img->newImage($page['width'], $page['height'], $transparent, 'gif');
				$img->compositeImage($frame, Imagick::COMPOSITE_OVER, $page['x'], $page['y']);
				$img->thumbnailImage($width, $height, $fit);

				$canvas->addImage( $img );
				$canvas->setImageDelay( $img->getImageDelay() );
				$canvas->setImagePage($width, $height, 0, 0);
			}
			$image->destroy();
			$this->image = $canvas;
		}else{
			$this->image->thumbnailImage( $width, $height, $fit );
		}
	}

	/**
	 * 添加一个边框
	 * @param $width         左右边框宽度
	 * @param $height        上下边框宽度
	 * @param string $color  颜色: RGB 颜色 'rgb(255,0,0)' 或 16进制颜色 '#FF0000' 或颜色单词 'white'/'red'...
	 */
	public function border($width, $height, $color='rgb(220, 220, 220)'){
		$color=new ImagickPixel();
		$color->setColor($color);
		$this->image->borderImage($color, $width, $height);
	}

	// 模糊
	public function blur($radius, $sigma){
		$this->image->blurImage($radius, $sigma);
	}

	// 高斯模糊
	public function gaussian_blur($radius, $sigma){
		$this->image->gaussianBlurImage($radius, $sigma);
	}

	// 运动模糊
	public function motion_blur($radius, $sigma, $angle){
		$this->image->motionBlurImage($radius, $sigma, $angle);
	}

	// 径向模糊
	public function radial_blur($radius){
		$this->image->radialBlurImage($radius);
	}

	// 添加噪点
	public function add_noise($type=null){
		$this->image->addNoiseImage($type==null?imagick::NOISE_IMPULSE:$type);
	}

	// 调整色阶
	public function level($black_point, $gamma, $white_point){
		$this->image->levelImage($black_point, $gamma, $white_point);
	}

	// 调整亮度、饱和度、色调
	public function modulate($brightness, $saturation, $hue){
		$this->image->modulateImage($brightness, $saturation, $hue);
	}

	// 素描
	public function charcoal($radius, $sigma){
		$this->image->charcoalImage($radius, $sigma);
	}

	// 油画效果
	public function oil_paint($radius){
		$this->image->oilPaintImage($radius);
	}

	// 水平翻转
	public function flop(){
		$this->image->flopImage();
	}

	// 垂直翻转
	public function flip(){
		$this->image->flipImage();
	}

	// 去除图像配置信息
	public function strip(){
		$this->image->stripImage();
	}

	//指定颜色透明
	public function transparent($color = 'rgb(255, 255, 255)' , $alpha = 0.0, $fuzz = 0){
		$color = new ImagickPixel();
		$color->setColor($color);
		$this->image->paintTransparentImage($color , $alpha, $fuzz);
//		$this->image->transparentPaintImage($color , $alpha, $fuzz, $invert);
	}
}