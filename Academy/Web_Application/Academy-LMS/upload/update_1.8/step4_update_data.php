use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


$updateData = [
'elegant' => ["header","elegant_hero_banner","elegant_features_courses","elegant_counter","elegant_courses","elegant_testimonial","elegant_faq","elegant_blog","elegant_download_app","footer"],
'kindergarden' =>
["header","kindergarden_hero_banner","kindergarden_features","kindergarden_top_courses","kindergarden_category","kindergarden_courses","kindergarden_about","kindergarden_faq","kindergarden_testimonial","kindergarden_blog","footer"],
'cooking' =>
["header","cooking_hero_banner","cooking_features","cooking_top_courses","cooking_upcoming_courses","cooking_courses","cooking_counter","cooking_testimonial","cooking_about_us","cooking_instructor","cooking_faq","cooking_blog","footer"],
'university' =>
["header","university_hero_banner","university_features","university_about_us","university_courses","university_motivational","university_faq","university_testimonial","university_blog","footer"],
'language' => ["header","language_hero_banner","language_features","language_courses","language_about_us","language_counter","language_instructors","language_testimonial","language_blog","footer"],
'development' => ["header","developer_hero_banner","developer_about_us","developer_features","developer_courses","developer_ebook","developer_faq","developer_testimonial","developer_blog","footer"],
'marketplace' =>
["header","marketplace_hero_banner","marketplace_categories","marketplace_courses","marketplace_counter","marketplace_about_us","marketplace_faq","marketplace_testimonial","marketplace_subscribe","marketplace_blog","footer"],
'meditation' => ["header","meditation_hero_banner","meditation_features","meditation_courses","meditation_benefit","meditation_testimonial","meditation_blog","footer"],
'default' => ["top_bar","header","hero_banner","features","category","featured_courses","about_us","testimonial","blog","footer"]
];

$pages = DB::table('builder_pages')->get();

foreach ($pages as $page) {
$identifier = $page->identifier ?? '';

if (array_key_exists($identifier, $updateData)) {
$htmlValue = json_encode($updateData[$identifier]);
} else {
$htmlValue = json_encode($updateData['default']);
}

DB::table('builder_pages')
->where('id', $page->id)
->update([
'is_permanent' => null,
'html' => $htmlValue,
'updated_at' => now(),
]);
}
