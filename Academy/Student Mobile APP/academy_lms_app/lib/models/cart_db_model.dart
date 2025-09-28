class CartDbModel {
  final int? id;
  final int courseId;
  final String courseTitle;
  final String thumbnail;
  final String price;

  CartDbModel(
      {this.id,
      required this.courseId,
      required this.courseTitle,
      required this.thumbnail,
      required this.price});

  factory CartDbModel.fromMap(Map<String, dynamic> json) => CartDbModel(
        id: json['id'],
        courseId: json['course_id'],
        courseTitle: json['course_title'],
        thumbnail: json['thumbnail'],
        price: json['price'],
      );

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'course_id': courseId,
      'course_title': courseTitle,
      'thumbnail': thumbnail,
      'price': price,
    };
  }
}
