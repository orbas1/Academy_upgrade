<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Models\EbookCategory;
use App\Models\EbookReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class EbookController extends Controller
{
    public function index(Request $request, $category = '')
    {

        $query = Ebook::join('users', 'ebooks.user_id', 'users.id')
            ->select('ebooks.*', 'users.name as author_name', 'users.email as author_email', 'users.photo')->where('ebooks.status', 1);

        // searched ebook
        if (request()->has('search')) {
            $query->where(function ($query) {
                $query->where('ebooks.title', 'LIKE', '%' . request()->input('search') . '%');
                $query->orWhere('ebooks.description', 'LIKE', '%' . request()->input('search') . '%');
            });
        }
        // filter by category
        if ($category != '') {
            $category_details = EbookCategory::where('slug', $category)->first();
            $query            = $query->where('category_id', $category_details->id);
        }

        if (request()->has('price')) {
            $price = request()->query('price');
            if ($price == 'paid') {
                // Show only paid items, exclude discounted items
                $query = $query->where('is_paid', 1)->where('discount_flag', 0);
            } elseif ($price == 'discount') {
                // Show only discounted items
                $query = $query->where('discount_flag', 1);
            } elseif ($price == 'free') {
                // Show only free items
                $query = $query->where('is_paid', 0);
            }
        }

        // filter by rating
        if (request()->has('rating')) {
            $rating = request()->query('rating');
            $query  = $query->where('average_rating', $rating);
        }
        // dd($rating);
        $page_data["ebooks"] = $query->paginate(9)->appends($request->query());
        return view('frontend.default.ebooks.index', $page_data);
    }

    public function details($slug)
    {

        $ebook = Ebook::join('users', 'ebooks.user_id', 'users.id', )
            ->join('ebook_categories', 'ebooks.category_id', 'ebook_categories.id', )
            ->join('languages', 'ebooks.language_id', 'languages.id')
            ->select(
                'ebooks.*',
                'ebook_categories.title as category',
                'languages.name as language'
            )
            ->where('ebooks.slug', $slug)
            ->first();

        if (! $ebook) {
            return redirect()->back();
        }

        // $reviews = EbookReview::join('users', 'ebook_reviews.user_id', '=', 'users.id')
        //     ->select(
        //         'ebook_reviews.*',
        //         'users.name as author_name'
        //     )
        //     ->where('ebook_reviews.ebook_id', $ebook->id)
        //     ->get();

        $reviews = EbookReview::join('users', 'ebook_reviews.user_id', '=', 'users.id')
        ->select('ebook_reviews.*', 'users.name as author_name')
        ->where('ebook_reviews.ebook_id', $ebook->id)
        ->orderBy('ebook_reviews.created_at', 'desc')
        ->get();

       
        $page_data['reviews'] = $reviews;
         $page_data['ebook']   = $ebook;

        $page_data['related_ebooks'] = Ebook::join('users', 'ebooks.user_id', 'users.id')
            ->select('ebooks.*', 'users.name as author_name', 'users.email as author_email', 'users.photo as img')
            ->where('ebooks.id', '!=', $ebook->id)
            ->where('ebooks.category_id', $ebook->category_id)
            ->take(10)->get();

        return view('frontend.default.ebooks.details', $page_data);
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

}
