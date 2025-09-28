<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Image, DB;
use Illuminate\Support\Facades\Session;

class FileUploader extends Model
{
    use HasFactory;

    public static function upload($uploaded_file, $upload_to, $width = null, $height = null, $optimized_width = 250, $optimized_height = null)
    {
        // Explanation: $upload_file = this is the uploaded temp file => $request->video_feild_name
        // Explanation: $upload_to = "public/storage/video" OR "public/storage/video/Sj8Ro5Gksde3T.mp4" OR "sdsdncts7sn.png" OR empty if amazon s3 is active
        // Explanation: $width and $height => Image width and height
        // Explanation: $optimized_width and $optimized_height ultra optimization, That is stored in optimized folder

        if (!$uploaded_file)
            return;

        if (!extension_loaded('fileinfo')) {
            Session::flash('error', get_phrase('Please enable fileinfo extension on your server.'));
            return;
        }

        if (!extension_loaded('exif')) {
            Session::flash('error', get_phrase('Please enable exif extension on your server.'));
            return;
        }

        if(!str_contains($upload_to, 'http') && str_contains($upload_to, 'public')){
            $upload_to = str_replace('public/', "", $upload_to);
        }

        //Add public path
        $upload_path = $upload_to;
        $upload_to = public_path($upload_to);

            if (is_dir($upload_to)) {
                $file_name = time() . '-' . random(30) . '.' . $uploaded_file->extension();
                $upload_path = $upload_path.'/'.$file_name;
            } else {
                $uploaded_path_arr = explode('/', $upload_to);
                $file_name = end($uploaded_path_arr);
                $upload_to = str_replace('/' . $file_name, "", $upload_to);
                if (!is_dir($upload_to)) {
                    Storage::makeDirectory($upload_to);
                }
            }

            if ($width == null) {
                $uploaded_file->move($upload_to, $file_name);
            } else {

                //Image optimization
                Image::make($uploaded_file->path())->orientate()->resize($width, $height, function ($constraint) {
                    $constraint->upsize();
                    $constraint->aspectRatio();
                })->save($upload_to . '/' . $file_name);

                //Ultra Image optimization
                $optimized_path = $upload_to . '/optimized';
                if (is_dir($optimized_path)) {
                    //Image optimization
                    Image::make($uploaded_file->path())->orientate()->resize($optimized_width, $optimized_height, function ($constraint) {
                        $constraint->upsize();
                        $constraint->aspectRatio();
                    })->save($optimized_path . '/' . $file_name);
                }
            }

            return $upload_path;
        
    }
}
