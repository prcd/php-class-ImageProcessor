<?php

class imageProcessor
{
	// properties = NULL must be set before process can be run - other property values are default or calculated using on other properties
	// Methods
	//  - fill : input image will be scaled (enlarged if necessary) to fit to width and height with extra width/height overhang cropped
	//  - fit  : if input image is larger than either set dimension it will be scaled down so that the entire image is visible, otherwise it will be saved as the size it was uploaded
	
	private $input_path      = NULL;
	private $input_type      = '';
	private $input_width     = 0;
	private $input_height    = 0;
	private $input_ratio     = 0;
	
	private $folder          = NULL;
	private $file_name       = NULL;
	private $quality         = 75;
	
	private $method          = 'fit';// fill, fit
	private $overwrite       = false;
	private $width           = NULL;
	private $height          = NULL;
	private $ratio           = 0;
	
	function __construct($array = NULL, $process_now = false)
	{
		if ($array != NULL)
		{
			$this->setOptions($array);
		}
		
		if ($process_now == true)
		{
			$this->process();
		}
	}
	
	
	// Convert input to integer, returns NULL if not valid
	private function inputToInteger($input)
	{
		$i = $input;
		
		if (!is_int($i))
		{
			if (preg_match("|[^\d]|",$i))
			{
				$i = NULL;
			}
			else
			{
				$i = (int) $i;
			}
		}
		
		return $i;
	}
	
	
	// Convert input to boolean, returns NULL if not valid
	private function inputToBoolean($input)
	{
		$i = $input;
		
		if (!is_bool($i))
		{
			if ($i === 1 || $i === '1')
			{
				$i = true;
			}
			else if ($i === 0 || $i === '0')
			{
				$i = false;
			}
			else
			{
				$i = NULL;
			}
		}
		
		return $i;
	}
	
	
	// Expects an IMAGETYPE_XXX constant, sets input_type on success
	private function setInputType($image_type_constant)
	{
		switch ($image_type_constant)
		{
			case 1:
				$type = 'gif';
				break;
			case 2:
				$type = 'jpg';
				break;
			case 3:
				$type = 'png';
				break;
			default:
				throw new Exception ('Unrecognised value ('.$image_type_constant.') for '.__METHOD__);
		}
		
		$this->input_type = $type;
	}
	
	
	// set the way the input image will be handled
	private function setMethod($method)
	{
		$valid = array('fill','fit');
		
		if (!in_array($method,$valid))
			throw new Exception ('Invalid method ('.$method.') for '.__METHOD__);
		
		$this->method = $method;
	}
	
	
	private function setRatio()
	{
		if ($this->width && $this->height)
		{
			$this->ratio = $this->width/$this->height;
		}
	}
	
	
	// Check all required properties have been set before processing
	private function checkRequiredProperties()
	{
		if ($this->input_path === NULL)
			$a[] = 'input path';

		if ($this->folder === NULL)
			$a[] = 'folder';

		if ($this->file_name === NULL)
			$a[] = 'file name';

		if ($this->width === NULL)
			$a[] = 'width';

		if ($this->height === NULL)
			$a[] = 'height';
		
		if ($a)
		{
			$list = implode(", ",$a);
			
			throw new Exception ('Cannot process, required properties ('.$list.') not set');
		}
	}
	
	
	// Check overwrite
	private function checkOverwrite()
	{
		if ($this->overwrite == false && file_exists($this->folder.'/'.$this->file_name))
			throw new Exception ('Cannot process, file name ('.$this->file_name.') already exists');
	}
	
	
	// Check input image and set some values
	private function setInputProperties()
	{
		$i = getimagesize($this->input_path);
		
		$this->input_width  = $i[0];
		$this->input_height = $i[1];
		$this->input_ratio  = $i[0]/$i[1];
		
		$this->setInputType($i[2]);
	}
	
	
	// Create image resource from input file 
	private function returnInputResourse()
	{
		switch ($this->input_type)
		{	
			case 'gif':
				return imagecreatefromgif($this->input_path);
				break;
			case 'jpg':
				return imagecreatefromjpeg($this->input_path);
				break;
			case 'png':
				return imagecreatefrompng($this->input_path);
				break;
			default:
				throw new Exception ('Invalid input type ('.$this->input_type.') for '.__METHOD__);
		}
	}
		
	
	// Change permission for overwriting an existing file on save
	public function allowOverwrite($bool)
	{
		$b = $this->inputToBoolean($bool);
				
		if (!is_bool($b))
			throw new Exception ('Input must be boolean (0 or 1 permitted) for '.__METHOD__);
		
		$this->overwrite = $b;
	}
	
	
	// Set the path of the image to be processed
	public function setInputPath($path)
	{
		if (!file_exists($path))
			throw new Exception ('Input file does not exist ('.$path.') for '.__METHOD__);
		
		$this->input_path = $path;
		$this->setInputProperties();
	}
	
	
	public function setHeight($pixels)
	{
		$i = $this->inputToInteger($pixels);
		
		if ($i < 1)
			throw new Exception ('Output height must be positive integer (string permitted) for '.__METHOD__);
		
		$this->height = $i;
		$this->setRatio();
	}
	
	
	public function setWidth($pixels)
	{
		$i = $this->inputToInteger($pixels);
		
		if ($i < 1)
			throw new Exception ('Output width must be positive integer (string permitted) for '.__METHOD__);
		
		$this->width = $i;
		$this->setRatio();
	}
	
	
	// Set the folder where the image will be saved
	public function setFolder($path)
	{
		if (!is_dir($path))
			throw new Exception ('Output folder does not exist ('.$path.') for '.__METHOD__);
		
		$this->folder = $path;
	}
	
	
	// Set the output file name
	public function setFileName($file_name)
	{
		// check extension
		$v = explode('.',$file_name);
		$v = array_pop($v);
		
		if ($v != 'jpg')
			throw new Exception ('File name extension must be .jpg');
			
		$this->file_name = $file_name;
	}
	
	
	// Set the file quality for jpg
	public function setQuality($integer)
	{
		$i = $this->inputToInteger($integer);
		
		if (!is_int($i) || $i < 0 || $i > 100)
			throw new Exception ('Input must be integer (string permitted) between 0 and 100 for '.__METHOD__);
		
		$this->quality = $i;
	}
	
	
	// Set all/any options in one go
	public function setOptions($array)
	{
		// check incoming
		if (!is_array($array))
			throw new Exception ('Input must be array for '.__METHOD__);
		
		// assign variables
		if (array_key_exists('overwrite',$array))
		{
			$this->allowOverwrite($array['overwrite']);
			unset($array['overwrite']);
		}
		if (array_key_exists('input_path',$array))
		{
			$this->setInputPath($array['input_path']);
			unset($array['input_path']);
		}
		if (array_key_exists('width',$array))
		{
			$this->setWidth($array['width']);
			unset($array['width']);
		}
		if (array_key_exists('height',$array))
		{
			$this->setHeight($array['height']);
			unset($array['height']);
		}
		if (array_key_exists('folder',$array))
		{
			$this->setFolder($array['folder']);
			unset($array['folder']);
		}
		if (array_key_exists('file_name',$array))
		{
			$this->setFileName($array['file_name']);
			unset($array['file_name']);
		}
		if (array_key_exists('quality',$array))
		{
			$this->setQuality($array['quality']);
			unset($array['quality']);
		}
		if (array_key_exists('method',$array))
		{
			$this->setMethod($array['method']);
			unset($array['method']);
		}
		
		// any left over vars?
		if (count($array) > 0)
		{
			foreach ($array as $k => $v)
			{
				$list[] = $k;
			}
			$list = implode(', ',$list);
			
			throw new Exception ('Input array contained unknown keys ('.$list.') for '.__METHOD__);
		}
	}
	
	
	public function process()
	{
		$this->checkRequiredProperties();
		$this->checkOverwrite();
		
		$input_resource = $this->returnInputResourse(); 
		
		switch ($this->method)
		{
			case 'fill':
				
				if ($this->ratio < $this->input_ratio)
				{
					// uploaded image will be wider after scaling
					$src_width  = floor(($this->width/$this->height)*$this->input_height);
					$src_height = $this->input_height;
					$src_x      = floor(($this->input_width-$src_width)/2);
					$src_y      = 0;
				}
				else if ($this->ratio > $this->input_ratio)
				{
					// uploaded image will be taller after scaling
					$src_width  = $this->input_width;
					$src_height = floor(($this->height/$this->width)*$this->input_width);
					$src_x      = 0;
					$src_y      = floor(($this->input_height-$src_height)/2);
				}
				else
				{
					// uploaded image ratio is just right
					$src_width  = $this->input_width;
					$src_height = $this->input_height;
					$src_x      = 0;
					$src_y      = 0;
				}
				
				// create output resource at the required dimensions
				$output_resource = imagecreatetruecolor($this->width,$this->height);
				// paste input resource (cropped if needed) onto output resource
				imagecopyresampled($output_resource,$input_resource,0,0,$src_x,$src_y,$this->width,$this->height,$src_width,$src_height);
				// save
				imagejpeg ($output_resource,$this->folder.'/'.$this->file_name,$this->quality);
				
				break;
			
			case 'fit':
				
				if ($this->input_width <= $this->width && $this->input_height <= $this->height)
				{
					// uploaded image is smaller or equal to output size in both dimensions 
					$src_width  = $this->input_width;
					$src_height = $this->input_height;
					$dst_width  = $this->input_width;
					$dst_height = $this->input_height;
				}
				else if ($this->ratio < $this->input_ratio)
				{
					// uploaded image is wider
					$src_width  = $this->input_width;
					$src_height = $this->input_height;
					$dst_width  = $this->width;
					$dst_height = ($this->width/$this->input_width)*$src_height;
				}
				else if ($this->ratio > $this->input_ratio)
				{
					// uploaded image is taller
					$src_width  = $this->input_width;
					$src_height = $this->input_height;
					$dst_width  = ($this->height/$this->input_height)*$src_width;
					$dst_height = $this->height;
				}
				else
				{
					// uploaded image is the same ratio but larger
					$src_width  = $this->input_width;
					$src_height = $this->input_height;
					$dst_width  = $this->width;
					$dst_height = $this->height;
				}
				
				// create output resource at the required dimensions
				$output_resource = imagecreatetruecolor($dst_width,$dst_height);
				// paste input resource (cropped if needed) onto output resource
				imagecopyresampled($output_resource,$input_resource,0,0,0,0,$dst_width,$dst_height,$src_width,$src_height);
				// save
				imagejpeg ($output_resource,$this->folder.'/'.$this->file_name,$this->quality);
				
				break;
		}
		
		// tidy up
		imagedestroy($input_resource);
		imagedestroy($output_resource);
		
		return true;
	}
}
