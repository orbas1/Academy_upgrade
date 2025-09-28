class   Course {
  int? id;
  String? title;
  String? thumbnail;
  String? preview;
  String? price;
  int? isPaid;
  String? instructor;
  String? instructorImage;
  dynamic total_reviews;
  dynamic average_rating;
  dynamic price_cart;
  int? numberOfEnrollment;
  String? shareableLink;
  // String? courseOverviewProvider;
  // String? courseOverviewUrl;
  // String? vimeoVideoId;

  Course({
    this.id,
    this.title,
    this.thumbnail,
    this.preview,
    this.price,
    this.isPaid,
    this.instructor,
    this.instructorImage,
    this.total_reviews,
    this.average_rating,
    this.price_cart,
    this.numberOfEnrollment,
    this.shareableLink,
    // @required this.courseOverviewProvider,
    // @required this.courseOverviewUrl,
    // @required this.vimeoVideoId,
  });
}
