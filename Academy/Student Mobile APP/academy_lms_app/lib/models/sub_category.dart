import 'package:flutter/foundation.dart';

class SubCategory {
  final int? id;
  final String? title;
  final int? parentId;
  final String? thumbnail;
  final int? numberOfCourses;

  SubCategory({
    @required this.id,
    @required this.title,
    @required this.parentId,
    @required this.thumbnail,
    @required this.numberOfCourses,
  });
}
