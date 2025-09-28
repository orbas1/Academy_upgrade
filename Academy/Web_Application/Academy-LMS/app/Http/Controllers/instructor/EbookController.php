<?php

namespace App\Http\Controllers\instructor;

use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Models\EbookCategory;
use App\Models\EbookPurchase;
use App\Models\FileUploader;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EbookController extends Controller
{
    //
    public function index()
    {

        $query = Ebook::where('user_id', auth()->user()->id);

        if (request()->has('search') && request()->query('search') != '') {
            $query = $query->where('title', 'LIKE', "%" . request()->query('search') . "%");
        }

        $page_data['ebooks'] = $query->paginate(10)->appends(request()->query());
        return view('instructor.ebook.index', $page_data);
    }
    public function create()
    {
        $page_data["category"] = EbookCategory::all();
        $page_data["language"] = Language::all();
        return view('instructor.ebook.create', $page_data);
    }
    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|unique:ebooks,title',
            'category_id'      => 'required',
            'language_id'      => 'required',
            'is_paid'          => Rule::in(['0', '1']),
            'price'            => 'required_if:is_paid,1|min:1|nullable|numeric',
            'discount_flag'    => Rule::in(['', '1']),
            'discounted_price' => 'required_if:discount_flag,1|min:1|nullable|numeric',
            'thumbnail'        => 'required|file|mimes:jpg,png,jpeg',
            'preview'          => 'required|file|mimes:pdf',
            'complete'         => 'required|file|mimes:pdf',
        ]);

        $ebook = [
            'user_id'          => auth()->user()->id,
            'title'            => $request->title,
            'slug'             => str::slug($request->title),
            'category_id'      => $request->category_id,
            'language_id'      => $request->language_id,
            'description'      => $request->description,
            'summary'          => $request->summary,
            'publication_name' => $request->publication_name,
            'edition'          => $request->edition,
            'is_paid'          => $request->is_paid,
            'price'            => $request->price,
            'discount_flag'    => $request->discount_flag,
            'discounted_price' => $request->discounted_price,
            'published_date'   => strtotime($request->published_date),
            'status'           => 1,
        ];

        if ($request->thumbnail) {
            $ebook['thumbnail'] = "uploads/ebook-thumbnail/" . nice_file_name($request->title, $request->thumbnail->extension());
            FileUploader::upload($request->thumbnail, $ebook['thumbnail']);

        }

        if ($request->preview) {
            $ebook['preview'] = "uploads/ebook-preview/" . nice_file_name($request->title, $request->preview->extension());
            FileUploader::upload($request->preview, $ebook['preview']);

        }
        if ($request->complete) {
            $ebook['complete'] = "uploads/ebook-complete/" . nice_file_name($request->title, $request->complete->extension());
            FileUploader::upload($request->complete, $ebook['complete']);

        }

        // insert data
        Ebook::insert($ebook);
        Session::flash('success', get_phrase('Ebook has been created successfully.'));
        return redirect()->route('instructor.ebooks');
    }
    public function delete($id)
    {
        // check user data exists or not
        $query = Ebook::where('id', $id);
        if ($query->doesntExist()) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        // delete data
        $query->delete();
        Session::flash('success', get_phrase('Ebook has been deleted successfully.'));
        return redirect()->back();
    }
    public function edit($id)
    {
        // check user data exists or not
        $query = Ebook::where('id', $id);
        if ($query->doesntExist()) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        $page_data["category"]      = EbookCategory::all();
        $page_data["language"]      = Language::all();
        $page_data['ebook_details'] = $query->first();
        return view('instructor.ebook.edit', $page_data);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'            => 'required|unique:ebooks,title,' . $id,
            'category_id'      => 'required',
            'language_id'      => 'required',
            'is_paid'          => Rule::in(['0', '1']),
            'price'            => 'required_if:is_paid,1|min:1|nullable|numeric',
            'discount_flag'    => Rule::in(['', '1']),
            'discounted_price' => 'required_if:discount_flag,1|min:1|nullable|numeric',
        ]);

        $data = [
            'user_id'          => auth()->user()->id,
            'slug'             => Str::slug($request->title),
            'title'            => $request->title,
            'category_id'      => $request->category_id,
            'language_id'      => $request->language_id,
            'description'      => $request->description,
            'summary'          => $request->summary,
            'publication_name' => $request->publication_name,
            'edition'          => $request->edition,
            'is_paid'          => $request->is_paid,
            'price'            => $request->price,
            'discount_flag'    => $request->discount_flag ? 1 : 0,
            'discounted_price' => $request->discounted_price,
            'published_date'   => strtotime($request->published_date),
            'status'           => 1,
        ];

        // check user data exists or not
        $query = Ebook::where('id', $id);
        if ($query->doesntExist()) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        if ($request->thumbnail) {
            $data['thumbnail'] = "uploads/ebook-thumbnail/" . nice_file_name($request->title, $request->thumbnail->extension());
            FileUploader::upload($request->thumbnail, $data['thumbnail']);
            remove_file($query->first()->thumbnail);
        }
        if ($request->preview) {
            $data['preview'] = "uploads/ebook-preview/" . nice_file_name($request->title, $request->preview->extension());
            FileUploader::upload($request->preview, $data['preview']);
            remove_file($query->first()->preview);
        }
        if ($request->complete) {
            $data['complete'] = "uploads/ebook-complete/" . nice_file_name($request->title, $request->complete->extension());
            FileUploader::upload($request->complete, $data['complete']);
            remove_file($query->first()->complete);
        }
        // update data
        Ebook::where('id', $id)->update($data);

        Session::flash('success', get_phrase('Ebook has been updated successfully.'));
        return redirect()->route('instructor.ebooks');
    }
    public function status($id)
    {
        $blog = Ebook::where("id", $id);
        if ($blog->doesntExist()) {
            Session::flash('success', get_phrase('Data not found.'));
            return redirect()->back();
        }

        $data["status"] = $blog->first()->status ? 0 : 1;
        Ebook::where("id", $id)->update($data);

        Session::flash('success', get_phrase('Status change successfully'));
        return redirect()->back();
    }

    public function payout($id)
    {
        $ebook = Ebook::where('id', $id)->first();

        // prepare each item by its id

        $payment_details = [
            'items'          => [
                [
                    'id'             => $ebook->id,
                    'title'          => $ebook->title,
                    'subtitle'       => '',
                    'price'          => $ebook->price,
                    'discount_price' => $ebook->discount_flag ? $ebook->price - $ebook->discounted_price : 0,
                ],
            ],

            'custom_field'   => [],

            'success_method' => [
                'model_name'    => 'PurchaseEbook',
                'function_name' => 'purchase_ebook',
            ],

            'tax'            => 0,
            'payable_amount' => $ebook->discount_flag ? $ebook->price - $ebook->discounted_price : $ebook->price,
            'cancel_url'     => route('ebook.details', $ebook->slug),
            'success_url'    => route('payment.success', ''),
        ];

        Session::put(['payment_details' => $payment_details]);
        return redirect()->route('payment');
    }

    public function preview($slug, $type)
    {
        $ebook = Ebook::where('slug', $slug)->first();
        if (! $ebook) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->route('instructor.ebooks');
        }

        if ($type == 'full') {
            $path = public_path($ebook->complete);
        } elseif ($type == 'partial') {
            $path = public_path($ebook->preview);
        } else {
            Session::flash('error', get_phrase('Invalid ebook type.'));
            return redirect()->route('instructor.ebooks');
        }

        // check on the system
        if (file_exists($path)) {
            return response()->file($path);
        } else {
            Session::flash('error', get_phrase('File does not exist.'));
            return redirect()->route('instructor.ebooks');
        }
    }

    public function instructor_revenue(Request $request)
    {
        $query = DB::table('ebook_purchases')
            ->join('ebooks', 'ebook_purchases.ebook_id', 'ebooks.id')
            ->join('users', 'ebook_purchases.user_id', 'users.id')
            ->where('ebooks.user_id', auth()->user()->id)
            ->select(
                'ebook_purchases.*',
                'ebooks.title as ebook_title',
                'ebooks.thumbnail as ebook_thumbnail',
                'users.photo'
            );

        if ($request->eDateRange) {
            $date                    = explode('-', $request->eDateRange);
            $start_date              = strtotime($date[0] . ' 00:00:00');
            $end_date                = strtotime($date[1] . ' 23:59:59');
            $page_data['start_date'] = $start_date;
            $page_data['end_date']   = $end_date;

            $page_data['purchases'] = $query->whereNotNull('ebook_purchases.instructor_revenue')->where('ebook_purchases.created_at', '>=', date('Y-m-d H:i:s', $start_date))
                ->where('ebook_purchases.created_at', '<=', date('Y-m-d H:i:s', $end_date))
                ->latest('id')->paginate(10)->appends($request->query());
        } else {
            $start_date              = strtotime('first day of this month');
            $end_date                = strtotime('last day of this month');
            $page_data['start_date'] = $start_date;
            $page_data['end_date']   = $end_date;
            $page_data['purchases']  = $query->whereNotNull('ebook_purchases.instructor_revenue')->where('ebook_purchases.created_at', '>=', date('Y-m-d H:i:s', $start_date))
                ->where('ebook_purchases.created_at', '<=', date('Y-m-d H:i:s', $end_date))->latest('id')->paginate(10);
        }
        return view('instructor.ebook.instructor_revenue', $page_data);
    }
}
