import 'package:flutter/foundation.dart';
import 'course.dart';
import 'sub_category.dart';

class CategoryDetail {
  List<SubCategory>? mSubCategory;
  List<Course>? mCourse;

  CategoryDetail({
    @required this.mSubCategory,
    @required this.mCourse,
  });
}