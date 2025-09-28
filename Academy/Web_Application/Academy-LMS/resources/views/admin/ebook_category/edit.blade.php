@php
    $ebook_category_details = App\Models\EbookCategory::where('id', $id)->first();
@endphp
<div class="ol-card">
    <div class="ol-card-body">
        <form action="{{ route('admin.ebook.categories.update', $ebook_category_details->id) }}" method="post"
            enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="fpb-7 mb-3">
                    <label class="form-label ol-form-label" for="title">{{ get_phrase('Title') }}<span
                            class="text-danger ms-1">*</span></label>
                    <input type="text" name = "title" class="form-control ol-form-control"
                        value="{{ $ebook_category_details->title }}"
                        placeholder="{{ get_phrase('Enter Category Title') }}" required>
                </div>

                <div class="fpb-7">
                    <label for="thumbnail" class="form-label ol-form-label">{{ get_phrase('Thumbnail') }}</label>
                    <input type="file" name="thumbnail" class="form-control ol-form-control"
                        value="{{ $ebook_category_details->thumbnail }}" id="thumbnail" accept="image/*" />
                </div>
            </div>

            <div class="fpb-7 mb-3 my-3 d-flex justify-content-end">
                <button type="submit" class="ol-btn-primary ">{{ get_phrase('Update ') }}</button>
            </div>
        </form>
    </div>
</div>
