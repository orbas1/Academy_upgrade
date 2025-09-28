import 'package:flutter/foundation.dart';

import './section.dart';

// import 'package:academy_lms_app/models/section.dart';

class CourseDetail {
  int? courseId;
  String? title;
  String? thumbnail;
  String? price;
  int? isPaid;
  String? instructor;
  String? instructorImage;
  dynamic total_reviews;
  dynamic average_rating;
  dynamic price_cart;
  int? numberOfEnrollment;
  String? shareableLink;
  List<String>? courseIncludes;
  List<String>? courseRequirements;
  List<String>? courseOutcomes;
  bool? isWishlisted;
  bool? isPurchased;
  bool? is_cart;
  String? preview;
  List<Section>? mSection;

  CourseDetail({
    @required this.courseId,
    this.title,
    this.thumbnail,
    this.price,
    this.isPaid,
    this.instructor,
    this.instructorImage,
    this.total_reviews,
    this.average_rating,
    this.price_cart,
    this.numberOfEnrollment,
    this.shareableLink,
    @required this.courseIncludes,
    @required this.courseRequirements,
    @required this.courseOutcomes,
    @required this.isWishlisted,
    @required this.isPurchased,
    @required this.is_cart,
    this.preview,
    @required this.mSection,
  });
}
// To parse this JSON data, do
//
//     final courseDetails = courseDetailssFromJson(jsonString);

// CourseDetails courseDetailsFromJson(String str) =>
//     CourseDetails.fromJson(json.decode(str));

// String courseDetailsToJson(CourseDetails data) => json.encode(data.toJson());
// List<CourseDetails> courseDetailsFromJson(String str) => List<CourseDetails>.from(json.decode(str).map((x) => CourseDetails.fromJson(x)));

// String courseDetailsToJson(List<CourseDetails> data) => json.encode(List<dynamic>.from(data.map((x) => x.toJson())));

class CourseDetails {
  int? id;
  String? title;
  String? slug;
  String? shortDescription;
  int? userId;
  int? categoryId;
  String? courseType;
  String? status;
  String? level;
  String? language;
  int? isPaid;
  int? isBest;
  String? price;
  String? discountedPrice;
  int? discountFlag;
  String? metaKeywords;
  dynamic metaDescription;
  String? thumbnail;
  String? banner;
  String? preview;
  String? description;
  Map<String, String>? requirements;
  Map<String, String>? outcomes;
  List<Faq>? faqs;
  List<String>? instructors;
  int? averageRating;
  int? total_reviews;
  DateTime? createdAt;
  DateTime? updatedAt;
  String? instructorName;
  String? instructorImage;
  int? totalEnrollment;
  String? shareableLink;
  int? totalNumberOfLessons;
  int? completion;
  int? totalNumberOfCompletedLessons;
  List<Review>? reviews;
  List<Section>? sections;
  bool? isWishlisted;
  int? isPurchased;
  List<String>? includes;

  CourseDetails({
    this.id,
    this.title,
    this.slug,
    this.shortDescription,
    this.userId,
    this.categoryId,
    this.courseType,
    this.status,
    this.level,
    this.language,
    this.isPaid,
    this.isBest,
    this.price,
    this.discountedPrice,
    this.discountFlag,
    this.metaKeywords,
    this.metaDescription,
    this.thumbnail,
    this.banner,
    this.preview,
    this.description,
    this.requirements,
    this.outcomes,
    this.faqs,
    this.instructors,
    this.averageRating,
    this.total_reviews,
    this.createdAt,
    this.updatedAt,
    this.instructorName,
    this.instructorImage,
    this.totalEnrollment,
    this.shareableLink,
    this.totalNumberOfLessons,
    this.completion,
    this.totalNumberOfCompletedLessons,
    this.sections,
    this.reviews,
    this.isWishlisted,
    this.isPurchased,
    this.includes,
  });
  factory CourseDetails.fromJson(Map<String, dynamic> json) => CourseDetails(
        id: json["id"],
        title: json["title"],
        slug: json["slug"],
        shortDescription: json["short_description"],
        userId: json["user_id"],
        categoryId: json["category_id"],
        courseType: json["course_type"],
        status: json["status"],
        level: json["level"],
        language: json["language"],
        isPaid: json["is_paid"],
        isBest: json["is_best"],
        price: json["price"],
        discountedPrice: json["discounted_price"],
        discountFlag: json["discount_flag"],
        metaKeywords: json["meta_keywords"],
        metaDescription: json["meta_description"],
        thumbnail: json["thumbnail"],
        banner: json["banner"],
        preview: json["preview"],
        description: json["description"],
        requirements: json["requirements"] is List
            ? {} // Handle empty list case
            : json["requirements"] != null
                ? Map.from(json["requirements"])
                    .map((k, v) => MapEntry<String, String>(k, v))
                : null,
        outcomes: json["outcomes"] is List
            ? {} // Handle empty list case
            : json["outcomes"] != null
                ? Map.from(json["outcomes"])
                    .map((k, v) => MapEntry<String, String>(k, v))
                : null,
        faqs: json["faqs"] != null
            ? List<Faq>.from(json["faqs"].map((x) => Faq.fromJson(x)))
            : null,
        instructors: json["instructors"] != null
            ? List<String>.from(json["instructors"].map((x) => x))
            : null,
        averageRating: json["average_rating"],
        total_reviews: json["total_reviews"],
        createdAt: json["created_at"] != null
            ? DateTime.parse(json["created_at"])
            : null,
        updatedAt: json["updated_at"] != null
            ? DateTime.parse(json["updated_at"])
            : null,
        instructorName: json["instructor_name"],
        instructorImage: json["instructor_image"],
        totalEnrollment: json["total_enrollment"],
        shareableLink: json["shareable_link"],
        totalNumberOfLessons: json["total_number_of_lessons"],
        completion: json["completion"],
        totalNumberOfCompletedLessons:
            json["total_number_of_completed_lessons"],
        sections: json["sections"] != null
            ? List<Section>.from(json["sections"].map((x) => Section))
            : null,
        reviews: json["reviews"] != null
            ? List<Review>.from(json["reviews"].map((x) => Review.fromJson(x)))
            : null,
        isWishlisted: json["is_wishlisted"],
        isPurchased: json["is_purchased"],
        includes: json["includes"] != null
            ? List<String>.from(json["includes"].map((x) => x))
            : null,
      );

  // factory CourseDetails.fromJson(Map<String, dynamic> json) => CourseDetails(
  //       id: json["id"],
  //       title: json["title"],
  //       slug: json["slug"],
  //       shortDescription: json["short_description"],
  //       userId: json["user_id"],
  //       categoryId: json["category_id"],
  //       courseType: json["course_type"],
  //       status: json["status"],
  //       level: json["level"],
  //       language: json["language"],
  //       isPaid: json["is_paid"],
  //       isBest: json["is_best"],
  //       price: json["price"],
  //       discountedPrice: json["discounted_price"],
  //       discountFlag: json["discount_flag"],
  //       metaKeywords: json["meta_keywords"],
  //       metaDescription: json["meta_description"],
  //       thumbnail: json["thumbnail"],
  //       banner: json["banner"],
  //       preview: json["preview"],
  //       description: json["description"],
  //       requirements: Map.from(json["requirements"])
  //           .map((k, v) => MapEntry<String, String>(k, v)),
  //       outcomes: Map.from(json["outcomes"])
  //           .map((k, v) => MapEntry<String, String>(k, v)),
  //       faqs: List<Faq>.from(json["faqs"].map((x) => Faq.fromJson(x))),
  //       instructors: List<String>.from(json["instructors"].map((x) => x)),
  //       averageRating: json["average_rating"],
  //       createdAt: DateTime.parse(json["created_at"]),
  //       updatedAt: DateTime.parse(json["updated_at"]),
  //       instructorName: json["instructor_name"],
  //       instructorImage: json["instructor_image"],
  //       totalEnrollment: json["total_enrollment"],
  //       shareableLink: json["shareable_link"],
  //       totalNumberOfLessons: json["total_number_of_lessons"],
  //       sections: List<Section>.from(
  //           json["sections"].map((x) => Section.fromJson(x))),
  //       isWishlisted: json["is_wishlisted"],
  //       isPurchased: json["is_purchased"],
  //       includes: List<String>.from(json["includes"].map((x) => x)),
  //     );

  Map<String, dynamic> toJson() => {
        "id": id,
        "title": title,
        "slug": slug,
        "short_description": shortDescription,
        "user_id": userId,
        "category_id": categoryId,
        "course_type": courseType,
        "status": status,
        "level": level,
        "language": language,
        "is_paid": isPaid,
        "is_best": isBest,
        "price": price,
        "discounted_price": discountedPrice,
        "discount_flag": discountFlag,
        "meta_keywords": metaKeywords,
        "meta_description": metaDescription,
        "thumbnail": thumbnail,
        "banner": banner,
        "preview": preview,
        "description": description,
        "requirements": Map.from(requirements!)
            .map((k, v) => MapEntry<String, dynamic>(k, v)),
        "outcomes":
            Map.from(outcomes!).map((k, v) => MapEntry<String, dynamic>(k, v)),
        "faqs": List<dynamic>.from(faqs!.map((x) => x.toJson())),
        "instructors": List<dynamic>.from(instructors!.map((x) => x)),
        "average_rating": averageRating,
        "total_reviews": total_reviews,
        "created_at": createdAt!.toIso8601String(),
        "updated_at": updatedAt!.toIso8601String(),
        "instructor_name": instructorName,
        "instructor_image": instructorImage,
        "total_enrollment": totalEnrollment,
        "shareable_link": shareableLink,
        "total_number_of_lessons": totalNumberOfLessons,
        "completion": completion,
        "total_number_of_completed_lessons": totalNumberOfCompletedLessons,
        "sections": List<dynamic>.from(sections!.map((x) => x)),
        "reviews": List<dynamic>.from(reviews!.map((x) => x.toJson())),
        "is_wishlisted": isWishlisted,
        "is_purchased": isPurchased,
        "includes": List<dynamic>.from(includes!.map((x) => x)),
      };
}

class Faq {
  String? title;
  String? description;

  Faq({
    this.title,
    this.description,
  });

  factory Faq.fromJson(Map<String, dynamic> json) => Faq(
        title: json["title"],
        description: json["description"],
      );

  Map<String, dynamic> toJson() => {
        "title": title,
        "description": description,
      };
}

class Review {
  int? id;
  int? userId;
  int? courseId;
  int? rating;
  String? reviewType;
  String? review;
  String? createdAt;
  String? updatedAt;
  String? photo;
  String? name;
  String? createtime;

  Review({
    this.id,
    this.userId,
    this.courseId,
    this.rating,
    this.reviewType,
    this.review,
    this.createdAt,
    this.updatedAt,
    this.photo,
    this.name,
    this.createtime,
  });

  factory Review.fromJson(Map<String, dynamic> json) => Review(
        id: json["id"],
        userId: json["user_id"],
        courseId: json["course_id"],
        rating: json["rating"],
        reviewType: json["review_type"],
        review: json["review"],
        createdAt: json["created_at"],
        updatedAt: json["updated_at"],
        photo: json["photo"],
        name: json["name"],
        createtime: json["createtime"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "user_id": userId,
        "course_id": courseId,
        "rating": rating,
        "review_type": reviewType,
        "review": review,
        "created_at": createdAt,
        "updated_at": updatedAt,
        "photo": photo,
        "name": name,
        "createtime": createtime,
      };
}

// class Section {
//   int? id;
//   int? userId;
//   int? courseId;
//   String? title;
//   int? sort;
//   DateTime? createdAt;
//   DateTime? updatedAt;
//   List<Lesson>? lessons;
//   String? totalDuration;
//   int? lessonCounterStarts;
//   int? lessonCounterEnds;
//   int? completedLessonNumber;
//   bool? userValidity;

//   Section({
//     this.id,
//     this.userId,
//     this.courseId,
//     this.title,
//     this.sort,
//     this.createdAt,
//     this.updatedAt,
//     this.lessons,
//     this.totalDuration,
//     this.lessonCounterStarts,
//     this.lessonCounterEnds,
//     this.completedLessonNumber,
//     this.userValidity,
//   });

//   factory Section.fromJson(Map<String, dynamic> json) => Section(
//         id: json["id"],
//         userId: json["user_id"],
//         courseId: json["course_id"],
//         title: json["title"],
//         sort: json["sort"],
//         createdAt: DateTime.parse(json["created_at"]),
//         updatedAt: DateTime.parse(json["updated_at"]),
//         lessons:
//             List<Lesson>.from(json["lessons"].map((x) => Lesson.fromJson(x))),
//         totalDuration: json["total_duration"],
//         lessonCounterStarts: json["lesson_counter_starts"],
//         lessonCounterEnds: json["lesson_counter_ends"],
//         completedLessonNumber: json["completed_lesson_number"],
//         userValidity: json["user_validity"],
//       );

//   Map<String, dynamic> toJson() => {
//         "id": id,
//         "user_id": userId,
//         "course_id": courseId,
//         "title": title,
//         "sort": sort,
//         "created_at": createdAt!.toIso8601String(),
//         "updated_at": updatedAt!.toIso8601String(),
//         "lessons": List<dynamic>.from(lessons!.map((x) => x.toJson())),
//         "total_duration": totalDuration,
//         "lesson_counter_starts": lessonCounterStarts,
//         "lesson_counter_ends": lessonCounterEnds,
//         "completed_lesson_number": completedLessonNumber,
//         "user_validity": userValidity,
//       };
// }

// class Lesson {
//   int? id;
//   String? title;
//   String? duration;
//   int? courseId;
//   int? sectionId;
//   String? videoType;
//   String? videoUrl;
//   String? lessonType;
//   dynamic isFree;
//   String? attachment;
//   String? attachmentUrl;
//   String? attachmentType;
//   String? summary;
//   int? isCompleted;
//   bool? userValidity;

//   Lesson({
//     this.id,
//     this.title,
//     this.duration,
//     this.courseId,
//     this.sectionId,
//     this.videoType,
//     this.videoUrl,
//     this.lessonType,
//     this.isFree,
//     this.attachment,
//     this.attachmentUrl,
//     this.attachmentType,
//     this.summary,
//     this.isCompleted,
//     this.userValidity,
//   });

//   factory Lesson.fromJson(Map<String, dynamic> json) => Lesson(
//         id: json["id"],
//         title: json["title"],
//         duration: json["duration"],
//         courseId: json["course_id"],
//         sectionId: json["section_id"],
//         videoType: json["video_type"],
//         videoUrl: json["video_url"],
//         lessonType: json["lesson_type"],
//         isFree: json["is_free"],
//         attachment: json["attachment"],
//         attachmentUrl: json["attachment_url"],
//         attachmentType: json["attachment_type"],
//         summary: json["summary"],
//         isCompleted: json["is_completed"],
//         userValidity: json["user_validity"],
//       );

//   Map<String, dynamic> toJson() => {
//         "id": id,
//         "title": title,
//         "duration": duration,
//         "course_id": courseId,
//         "section_id": sectionId,
//         "video_type": videoType,
//         "video_url": videoUrl,
//         "lesson_type": lessonType,
//         "is_free": isFree,
//         "attachment": attachment,
//         "attachment_url": attachmentUrl,
//         "attachment_type": attachmentType,
//         "summary": summary,
//         "is_completed": isCompleted,
//         "user_validity": userValidity,
//       };
// }
