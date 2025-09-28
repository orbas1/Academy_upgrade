<?php

namespace App\Http\Controllers\instructor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WatermarkController;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use App\Models\FileUploader;


class LessonController extends Controller
{
    public function store(Request $request)
    {
        $maximum_sort_value = Lesson::where('course_id', $request->course_id)->orderBy('sort', 'desc')->firstOrNew()->sort;
        // duplicate check
        if (Lesson::where('course_id', $request->course_id)->where('title', $request->title)->exists()) {
            Session::flash('error', get_phrase('Lesson already exists.'));
            return redirect()->back();
        }

        $lesson              = new Lesson();
        $lesson->title       = $request->title;
        $lesson->user_id     = auth()->user()->id;
        $lesson->course_id   = $request->course_id;
        $lesson->section_id  = $request->section_id;
        $lesson->is_free     = $request->free_lesson;
        $lesson->lesson_type = $request->lesson_type;
        $lesson->summary     = $request->summary;
        $lesson->sort     = $maximum_sort_value + 1;
        if ($request->lesson_type == 'text') {
            $lesson->attachment      = $request->text_description;
            $lesson->attachment_type = $request->lesson_provider;
        } elseif ($request->lesson_type == 'video-url') {

            $lesson->video_type = $request->lesson_provider;
            $lesson->lesson_src = $request->lesson_src;
            $lesson->duration   = $request->duration;
        } elseif ($request->lesson_type == 'html5') {

            $lesson->video_type = $request->lesson_provider;
            $lesson->lesson_src = $request->lesson_src;
            $lesson->duration   = $request->duration;
        } elseif ($request->lesson_type == 'document_type') {

            if ($request->attachment == '') {
                $file = '';
            } else {
                $item      = $request->file('attachment');
                $file_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();

                $path = public_path('uploads/lesson_file/attachment');
                if (!File::isDirectory($path)) {
                    File::makeDirectory($path, 0777, true, true);
                } else {
                    FileUploader::upload($request->attachment, 'uploads/lesson_file/attachment/' . $file_name);
                }
                $file = $file_name;
            }
            $lesson->attachment      = $file;
            $lesson->attachment_type = $request->attachment_type;
        } elseif ($request->lesson_type == 'scorm') {
            if ($request->scorm_file == '') {
                $file = '';
            } else {
                $item      = $request->file('scorm_file');
                $file_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();

                $upload_path = public_path('uploads/lesson_file/scorm_content');

                if (! File::isDirectory($upload_path)) {
                    File::makeDirectory($upload_path, 0777, true, true);
                }

                $item->move($upload_path, $file_name);

                $zip          = new \ZipArchive();
                $zip_path     = $upload_path . '/' . $file_name;
                $extract_path = $upload_path . '/' . pathinfo($file_name, PATHINFO_FILENAME);

                if ($zip->open($zip_path) === true) {
                    $zip->extractTo($extract_path);
                    $zip->close();

                    File::delete($zip_path);
                } else {
                    return response()->json(['error' => 'Failed to extract the SCORM file.'], 500);
                }

                $file = pathinfo($file_name, PATHINFO_FILENAME);
            }

            $lesson['attachment']      = $file;
            $lesson['attachment_type'] = $request->scorm_provider;
        } elseif ($request->lesson_type == 'image') {

            if ($request->attachment == '') {
                $file = '';
            } else {
                $item      = $request->file('attachment');
                $file_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();

                $path = public_path('uploads/lesson_file/attachment');
                if (!File::isDirectory($path)) {
                    File::makeDirectory($path, 0777, true, true);
                } else {
                    FileUploader::upload($request->attachment, 'uploads/lesson_file/attachment/' . $file_name);
                }
                $file = $file_name;
            }
            $lesson->attachment      = $file;
            $lesson->attachment_type = $item->getClientOriginalExtension();
        } elseif ($request->lesson_type == 'vimeo-url') {

            $lesson->video_type = $request->lesson_provider;
            $lesson->lesson_src = $request->lesson_src;
            $lesson->duration   = $request->duration;
        } elseif ($request->lesson_type == 'iframe') {

            $lesson->lesson_src = $request->iframe_source;
        } elseif ($request->lesson_type == 'google_drive') {

            $lesson->video_type = $request->lesson_provider;
            $lesson->lesson_src = $request->lesson_src;
            $lesson->duration   = $request->duration;
        } elseif ($request->lesson_type == 'system-video') {

            if ($request->system_video_file == '') {
                $file = '';
            } else {
                $item      = $request->file('system_video_file');
                $file_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();

                $path = public_path('uploads/lesson_file/videos');
                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0777, true, true);
                }

                $type = get_player_settings('watermark_type');
                if ($type == 'ffmpeg') {
                    $watermark = get_player_settings('watermark_logo');
                    if (! $watermark) {
                        return redirect()->back()->with('error', get_phrase('Please configure watermark setting.'));
                    }

                    if (! file_exists(public_path($watermark))) {
                        return redirect()->back()->with('error', get_phrase('File doesn\'t exists.'));
                    }

                    $watermark_status = WatermarkController::encode($item, $file_name, $path);
                    if (! $watermark_status) {
                        return redirect()->back()->with('error', get_phrase('Something went wrong.'));
                    }
                }
                $file = FileUploader::upload($request->system_video_file, 'uploads/lesson_file/videos/' . $file_name);
            }

            $lesson->video_type = $request->lesson_provider;
            $lesson->lesson_src = $file;
            if (empty($request->duration)) {
                $lesson->duration   = '00:00:00';
            } else {
                $duration_formatter = explode(':', $request->duration);
                $hour               = sprintf('%02d', $duration_formatter[0]);
                $min                = sprintf('%02d', $duration_formatter[1]);
                $sec                = sprintf('%02d', $duration_formatter[2]);
                $lesson->duration   = $hour . ':' . $min . ':' . $sec;
            }
        }

        $lesson->save();
        Session::flash('success', get_phrase('Lesson added successfully.'));
        return redirect()->back();
    }

    public function sort(Request $request)
    {
        $lessons = json_decode($request->itemJSON);
        foreach ($lessons as $key => $value) {
            $updater = $key + 1;
            Lesson::where('id', $value)->update(['sort' => $updater]);
        }
    }

    public function update(Request $request)
    {
        $current_data = Lesson::find($request->id);

        $lesson['title']      = $request->title;
        $lesson['section_id'] = $request->section_id;
        $lesson['summary']    = $request->summary;
        if ($request->lesson_type == 'text') {
            $lesson['attachment'] = $request->text_description;
        } elseif ($request->lesson_type == 'video-url') {
            $lesson['lesson_src'] = $request->lesson_src;
            if (empty($request->duration)) {
                $lesson['duration']   =  '00:00:00';
            } else {
                $duration_formatter = explode(':', $request->duration);
                $hour               = sprintf('%02d', $duration_formatter[0]);
                $min                = sprintf('%02d', $duration_formatter[1]);
                $sec                = sprintf('%02d', $duration_formatter[2]);
                $lesson['duration']   = $hour . ':' . $min . ':' . $sec;
            }
        } elseif ($request->lesson_type == 'html5') {
            $lesson['lesson_src'] = $request->lesson_src;
            if (empty($request->duration)) {
                $lesson['duration']   =  '00:00:00';
            } else {
                $duration_formatter = explode(':', $request->duration);
                $hour               = sprintf('%02d', $duration_formatter[0]);
                $min                = sprintf('%02d', $duration_formatter[1]);
                $sec                = sprintf('%02d', $duration_formatter[2]);
                $lesson['duration']   = $hour . ':' . $min . ':' . $sec;
            }
        } elseif ($request->lesson_type == 'document_type') {
            if ($request->attachment) {
                $item      = $request->file('attachment');
                $file_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();

                $path = public_path('uploads/lesson_file/attachment');
                if (!File::isDirectory($path)) {
                    File::makeDirectory($path, 0777, true, true);
                } else {
                    FileUploader::upload($request->attachment, 'uploads/lesson_file/attachment/' . $file_name);
                }
                $lesson['attachment']      = $file_name;
                $lesson['attachment_type'] = $request->attachment_type;

                remove_file('uploads/lesson_file/attachment/'.$current_data->attachment);
            }
        } elseif ($request->lesson_type == 'image') {
            if ($request->attachment) {
                $item      = $request->file('attachment');
                $file_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();

                $path = public_path('uploads/lesson_file/attachment');
                if (!File::isDirectory($path)) {
                    File::makeDirectory($path, 0777, true, true);
                } else {
                    FileUploader::upload($request->attachment, 'uploads/lesson_file/attachment/' . $file_name);
                }
                $lesson['attachment']      = $file_name;
                $lesson['attachment_type'] = $item->getClientOriginalExtension();
                remove_file('uploads/lesson_file/attachment/'.$current_data->attachment);
            }
        } elseif ($request->lesson_type == 'vimeo-url') {
            $lesson['lesson_src'] = $request->lesson_src;
            if (empty($request->duration)) {
                $lesson['duration']   =  '00:00:00';
            } else {
                $duration_formatter = explode(':', $request->duration);
                $hour               = sprintf('%02d', $duration_formatter[0]);
                $min                = sprintf('%02d', $duration_formatter[1]);
                $sec                = sprintf('%02d', $duration_formatter[2]);
                $lesson['duration']   = $hour . ':' . $min . ':' . $sec;
            }
        } elseif ($request->lesson_type == 'iframe') {
            $lesson['lesson_src'] = $request->iframe_source;
        } elseif ($request->lesson_type == 'google_drive') {
            $lesson['lesson_src'] = $request->lesson_src;
            if (empty($request->duration)) {
                $lesson['duration']   =  '00:00:00';
            } else {
                $duration_formatter = explode(':', $request->duration);
                $hour               = sprintf('%02d', $duration_formatter[0]);
                $min                = sprintf('%02d', $duration_formatter[1]);
                $sec                = sprintf('%02d', $duration_formatter[2]);
                $lesson['duration']   = $hour . ':' . $min . ':' . $sec;
            }
        } elseif ($request->lesson_type == 'scorm') {

            $existing_scorm_folder = $request->attachment;

            if ($request->scorm_file == '') {
                $file = '';
            } else {
                $item      = $request->file('scorm_file');
                $file_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();

                $upload_path = public_path('uploads/lesson_file/scorm_content');

                if (! File::isDirectory($upload_path)) {
                    File::makeDirectory($upload_path, 0777, true, true);
                }
                $this->deleteDir(public_path('uploads/lesson_file/scorm_content/' . $existing_scorm_folder));

                FileUploader::upload($request->scorm_file, 'uploads/lesson_file/scorm_content/' . $file_name);

                $zip          = new \ZipArchive();
                $zip_path     = $upload_path . '/' . $file_name;
                $extract_path = $upload_path . '/' . pathinfo($file_name, PATHINFO_FILENAME);

                // Remove old SCORM directory if it exists
                $existing_scorm_path = $upload_path . '/' . $request->scorm_file;
                if (File::isDirectory($existing_scorm_path)) {
                    File::deleteDirectory($existing_scorm_path);
                }

                if ($zip->open($zip_path) === true) {
                    $zip->extractTo($extract_path);
                    $zip->close();

                    File::delete($zip_path);
                } else {
                    return response()->json(['error' => 'Failed to extract the SCORM file.'], 500);
                }

                $file = pathinfo($file_name, PATHINFO_FILENAME);
            }

            $lesson['attachment']      = $file;
            $lesson['attachment_type'] = $request->scorm_provider;
        } elseif ($request->lesson_type == 'system-video') {

            if ($request->system_video_file) {
                $item      = $request->file('system_video_file');
                $file_name = strtotime('now') . random(4) . '.' . $item->getClientOriginalExtension();

                $path = public_path('uploads/lesson_file/videos');
                if (! File::isDirectory($path)) {
                    File::makeDirectory($path, 0777, true, true);
                }

                $type = get_player_settings('watermark_type');
                if ($type == 'ffmpeg') {
                    $watermark = get_player_settings('watermark_logo');
                    if (! $watermark) {
                        return redirect()->back()->with('error', get_phrase('Please configure watermark setting.'));
                    }

                    if (! file_exists(public_path($watermark))) {
                        return redirect()->back()->with('error', get_phrase('File doesn\'t exists.'));
                    }

                    $watermark_status = WatermarkController::encode($item, $file_name, $path);
                    if (! $watermark_status) {
                        return redirect()->back()->with('error', get_phrase('Something went wrong.'));
                    }
                }
                FileUploader::upload($request->system_video_file, 'uploads/lesson_file/videos/' . $file_name);

                $file = str_replace(public_path('/'), '', $path).'/'. $file_name;
                $lesson['lesson_src'] = $file;
                remove_file($current_data->lesson_src);
            }

            if (empty($request->duration)) {
                $lesson['duration']   =  '00:00:00';
            } else {
                $duration_formatter = explode(':', $request->duration);
                $hour               = sprintf('%02d', $duration_formatter[0]);
                $min                = sprintf('%02d', $duration_formatter[1]);
                $sec                = sprintf('%02d', $duration_formatter[2]);
                $lesson['duration']   = $hour . ':' . $min . ':' . $sec;
            }
        }

        Lesson::where('id', $request->id)->update($lesson);
        Session::flash('success', get_phrase('lesson update successfully'));
        return redirect()->back();
    }

    public function deleteDir($directoryPath)
    {
        if (File::exists($directoryPath)) {
            // Delete all files and subdirectories within the directory
            File::deleteDirectory($directoryPath);

            // Finally, delete the root directory itself
            File::delete($directoryPath);
        }
    }

    public function delete($id)
    {
        $current_data = Lesson::find($id);
        remove_file($current_data->lesson_src);
        remove_file('uploads/lesson_file/attachment/'.$current_data->attachment);
        
        Lesson::where('id', $id)->delete();
        Session::flash('success', get_phrase('Delete successfully'));
        return redirect()->back();
    }
}
