<?php
/*
 * PHP QR Code encoder
 *
 * Image output of code using GD2
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
 
    define('QR_IMAGE', true);

    class QRimage {
    
        //----------------------------------------------------------------------
        public static function png($frame, $filename = false, $pixelPerPoint = 4, $outerFrame = 4,$saveandprint=FALSE,$text="png") 
        {
            $image = self::image($frame, $pixelPerPoint, $outerFrame, $text);
            
            if ($filename === false) {
                Header("Content-type: image/png");
                ImagePng($image);
            } else {
                if($saveandprint===TRUE){
                    ImagePng($image, $filename);
                    header("Content-type: image/png");
                    ImagePng($image);
                }else{
                    ImagePng($image, $filename);
                }
            }
            
            ImageDestroy($image);
        }
    
        //----------------------------------------------------------------------
        public static function jpg($frame, $filename = false, $pixelPerPoint = 8, $outerFrame = 4, $q = 85,$text="jpg") 
        {
            $image = self::image($frame, $pixelPerPoint, $outerFrame, $text);
            
            if ($filename === false) {
                Header("Content-type: image/jpeg");
                ImageJpeg($image, null, $q);
            } else {
                ImageJpeg($image, $filename, $q);            
            }
            
            ImageDestroy($image);
        }
    
        //----------------------------------------------------------------------
        private static function image($frame, $pixelPerPoint = 4, $outerFrame = 4, $url = "blablabla") 
        {
			$borderBottom = 9;
			$font_file = "isocpeur.ttf";
			$font_file_bold = $font_file;
			
			$url_start = preg_replace('|(.*/)[A-Z]*|', '$1', $url);
			$id = preg_replace('|.*/([A-Z]*)|', '$1', $url);
			//$url_start = "http://flipdot.org/";
			
            $h = count($frame);
            $w = strlen($frame[0]);
            
            $imgW = $w + 2*$outerFrame;
            $imgH = $h + 2*$outerFrame;
            
            $base_image =ImageCreate($imgW, $imgH + $borderBottom);
            
        	// Font Size
			$font_size_url = 21;
			$font_size_id = 64;

			// bounding box for measuring ttf size
			$bbox_url = imagettfbbox($font_size_url, 0, $font_file, $url_start);
			$position_x_url = $imgW * $pixelPerPoint / 2 - $bbox_url[4] / 2 + (0.9 * $pixelPerPoint);
			$position_y_url = $imgH * $pixelPerPoint + (3.0 * $pixelPerPoint);
			
			$bbox_id = imagettfbbox($font_size_id, 0, $font_file, $id);
			$position_x_id = $imgW * $pixelPerPoint / 2 - $bbox_id[4] / 2 + (0.5 * $pixelPerPoint);
			$position_y_id = $imgH * $pixelPerPoint + (13.8 * $pixelPerPoint);

            $col[0] = ImageColorAllocate($base_image,255,255,255);
            $col[1] = ImageColorAllocate($base_image,0,0,0);

            imagefill($base_image, 0, 0, $col[0]);

            for($y=0; $y<$h; $y++) {
                for($x=0; $x<$w; $x++) {
                    if ($frame[$y][$x] == '1') {
                        ImageSetPixel($base_image,$x+$outerFrame,$y+$outerFrame,$col[1]); 
                    }
                }
            }
            
            //$wAdj = $imgW - 2;
            //$hAdj = $imgH + 11;
            
            $target_image =ImageCreate($imgW * $pixelPerPoint + 17, ($imgH + $borderBottom) * $pixelPerPoint + 57);
            ImageCopyResized($target_image, $base_image, 17/2, 17/2, 0, 0, $imgW * $pixelPerPoint, $imgH * $pixelPerPoint, $imgW, $imgH);
            
			$adjW = $imgW * $pixelPerPoint + 17 - 1;
			$adjH = ($imgH + $borderBottom) * $pixelPerPoint + 57 - 1;
			// Write the text ontop
			// URL
			imagettftext ($target_image, $font_size_url, 0, $position_x_url, $position_y_url, $col[1], $font_file , $url_start);
			// ID
			imagettftext ($target_image, $font_size_id, 0, $position_x_id, $position_y_id, $col[1], $font_file , $id);
			
            $col[2] = ImageColorAllocate($target_image,200,200,200);
			//borders
			imageline($target_image, 0, 0, 0, $adjH, $col[2]);
			imageline($target_image, $adjW, 0, $adjW, $adjH, $col[2]);
			imageline($target_image, 0, 0, $adjW, 0, $col[2]);
			imageline($target_image, 0, $adjH, $adjW, $adjH, $col[2]);
			
            ImageDestroy($base_image);
            
            return $target_image;
        }
    }
