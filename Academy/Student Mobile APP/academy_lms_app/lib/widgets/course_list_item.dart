// ignore_for_file: avoid_print

import 'package:academy_lms_app/screens/course_detail.dart';
import 'package:flutter/material.dart';

import '../constants.dart';

class CourseListItem extends StatelessWidget {
  final int? id;
  final String? title;
  final String? thumbnail;
  final dynamic average_rating;
  final String? price;
  final String? instructor;
  final int? total_reviews;

  const CourseListItem({
    super.key,
    @required this.id,
    @required this.title,
    @required this.thumbnail,
    @required this.average_rating,
    @required this.price,
    @required this.instructor,
    @required this.total_reviews,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        Navigator.of(context)
            .pushNamed(CourseDetailScreen.routeName, arguments: id);
        // Navigator.of(context).push(MaterialPageRoute(
        //     builder: (context) => CourseDetailScreen1(
        //           courseId: id,
        //         )));

        print(id);
      },
      child: Container(
        decoration: BoxDecoration(
          boxShadow: [
            BoxShadow(
              color: kBackButtonBorderColor.withOpacity(0.07),
              blurRadius: 10,
              offset: const Offset(0, 0),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.only(bottom: 7.0),
          child: Card(
            color: Colors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
            ),
            elevation: 0,
            child: Row(
              children: [
                Expanded(
                  flex: 2,
                  child: Padding(
                    padding: const EdgeInsets.all(10.0),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(8),
                      child: FadeInImage.assetNetwork(
                        placeholder: 'assets/images/loading_animated.gif',
                        image: thumbnail.toString(),
                        width: 130,
                        height: 128,
                        fit: BoxFit.cover,
                      ),
                    ),
                  ),
                ),
                Expanded(
                  flex: 3,
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(vertical: 10, horizontal: 5),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: <Widget>[
                        Text(
                          '$title...',
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        const SizedBox(
                          height: 10,
                        ),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.start,
                          children: [
                            Icon(
                              Icons.star,
                              color: kStarColor,
                              size: 18,
                            ),
                            Text(
                              average_rating.toString(),
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w400,
                                color: kGreyLightColor,
                              ),
                            ),
                            Text(
                              '  (${total_reviews.toString()} Reviews)',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w400,
                                color: kGreyLightColor,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(
                          height: 20,
                        ),
                        Text(
                          price.toString(),
                          style: const TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              ],
            ),
          ),
        ),
      ),
    );
  }
}
