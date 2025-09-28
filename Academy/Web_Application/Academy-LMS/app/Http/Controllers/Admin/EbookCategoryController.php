<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EbookCategory;
use App\Models\FileUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// Add this at the top of your file if not already included

class EbookCategoryController extends Controller
{
    public function index()
    {
        $page_data['category_list'] = EbookCategory::latest('id')->paginate(10);
        return view('admin.ebook_category.index', $page_data);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'     => 'required|unique:ebook_categories,title',
            'thumbnail' => 'required|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $ebook_category = [
            'title' => $request->title,
            'slug'  => Str::slug($request->title),
        ];
        if ($request->thumbnail) {
            $ebook_category['thumbnail'] = "uploads/ebook-category-thumbnail/" . nice_file_name($request->title, $request->thumbnail->extension());
            FileUploader::upload($request->thumbnail, $ebook_category['thumbnail']);
        }

        // insert data
        EbookCategory::insert($ebook_category);
        Session::flash('success', get_phrase('Coupon has been created successfully.'));
        return redirect()->back();
    }

    public function delete($id)
    {
        // Check user data exists or not
        $query = EbookCategory::where('id', $id);
        if ($query->doesntExist()) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        // Retrieve the ebook category record
        $ebookCategory = $query->first();

        // Delete the image file from the server
        $imagePath = public_path($ebookCategory->thumbnail);
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Delete the data
        $ebookCategory->delete();

        Session::flash('success', get_phrase('Ebook category has been deleted successfully.'));
        return redirect()->back();
    }

    public function edit($id)
    {
        // check user data exists or not
        $query = EbookCategory::where('id', $id);
        if ($query->doesntExist()) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        $page_data['ebook_category_details'] = $query->first();
        return view('admin.ebook_category.edit', $page_data);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:ebook_categories,title,' . $id,
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // check user data exists or not
        $query = EbookCategory::where('id', $id);
        if ($query->doesntExist()) {
            Session::flash('error', get_phrase('Data not found.'));
            return redirect()->back();
        }

        if ($request->thumbnail) {
            $data['thumbnail'] = "uploads/ebook-category-thumbnail/" . nice_file_name($request->title, $request->thumbnail->extension());
            FileUploader::upload($request->thumbnail, $data['thumbnail']);
            remove_file($query->first()->thumbnail);
        }
        $data['title'] = $request->title;
        $data['slug']  = Str::slug($request->title);

        // update data
        EbookCategory::where('id', $id)->update($data);

        Session::flash('success', get_phrase('Ebook category has been updated successfully.'));
        return redirect()->back();
    }
}
