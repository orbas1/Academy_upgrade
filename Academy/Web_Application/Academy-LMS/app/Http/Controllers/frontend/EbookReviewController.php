<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Models\EbookReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;


class EbookReviewController extends Controller
{
    public function store(Request $request)
    {

        $data['user_id'] = auth()->user()->id;
        $data['ebook_id'] = $request->ebook_id;
        $data['rating'] = $request->rating;
        $data['review'] = $request->review;
        EbookReview::insert($data);

        // update ebook rating
        $query          = EbookReview::where('ebook_id', $request->ebook_id)->get();
        $sum            = $query->sum('rating');
        $average_rating = $sum / $query->count();
        Ebook::where('id', $request->ebook_id)->update(['average_rating' => round($average_rating, 1)]);

        Session::flash('success', get_phrase('You review has been saved.'));
        return redirect()->back();
    }
    public function update(Request $request, $id)
    {
        // validate id
        if (!is_numeric($id) && $id < 1) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        $data['user_id'] = auth()->user()->id;
        $data['ebook_id'] = $request->ebook_id;
        $data['rating'] = $request->rating;
        $data['review'] = $request->review;
        EbookReview::where('id', $id)->update($data);

        // update ebook rating
        $query          = EbookReview::where('ebook_id', $request->ebook_id)->get();
        $sum            = $query->sum('rating');
        $average_rating = $sum / $query->count();
        Ebook::where('id', $request->ebook_id)->update(['average_rating' => round($average_rating, 1)]);
        return redirect()->back();
    }

    public function delete($id)
    {
        $query = EbookReview::where('id', $id);
        $query->delete();
        return redirect()->back();
    }
}