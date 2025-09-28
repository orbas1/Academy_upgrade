<?php

namespace App\Http\Controllers\student;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Models\EbookPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class MyEbookController extends Controller
{
    public function index()
    {
        $page_data['my_ebooks'] = EbookPurchase::join('ebooks', 'ebook_purchases.ebook_id', '=', 'ebooks.id')
            ->join('users', 'ebooks.user_id', '=', 'users.id')
            ->where('ebook_purchases.user_id', auth()->user()->id)
            ->select('ebook_purchases.*', 'ebooks.slug', 'ebooks.title', 'ebooks.thumbnail', 'users.name as user_name', 'users.photo as user_photo')
            ->paginate(6);

        $view_path = 'frontend.' . get_frontend_settings('theme') . '.student.my_ebooks.index';
        return view($view_path, $page_data);
    }
    public function preview($id)
    {
        // check in data base or not
        $ebook = Ebook::where('id', $id)->first();
        if (! $ebook) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        // file exists or not
        $file = public_path($ebook->preview);
        if (! file_exists($file)) {
            Session::flash('error', get_phrase('File does not exist.'));
            return redirect()->back();
        }
        return response()->file(public_path($ebook->preview));
    }
    public function read($slug)
    {
        // check in data base or not
        $ebook = Ebook::where('slug', $slug)->first();
        if (! $ebook) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        // file exists or not
        $file = public_path($ebook->complete);
        if (! file_exists($file)) {
            Session::flash('error', get_phrase('File does not exist.'));
            return redirect()->back();
        }
        return response()->file(public_path($ebook->complete));
    }
}
