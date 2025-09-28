// ignore_for_file: file_names

import 'package:flutter/material.dart';
import '../constants.dart';
import '../models/course.dart';
import '../screens/course_detail.dart';
import 'util.dart';

class WishlistList extends StatelessWidget {
  final Course? course;

  const WishlistList({
    super.key,
    @required this.course,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        Navigator.of(context)
            .pushNamed(CourseDetailScreen.routeName, arguments: course!.id);
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
                  flex: 3,
                  child: Padding(
                    padding: const EdgeInsets.all(10.0),
                    child: Stack(
                      fit: StackFit.loose,
                      alignment: Alignment.center,
                      clipBehavior: Clip.none,
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: FadeInImage.assetNetwork(
                            placeholder: 'assets/images/loading_animated.gif',
                            image: course!.thumbnail.toString(),
                            height: 128,
                            width: double.infinity,
                            fit: BoxFit.cover,
                          ),
                        ),
                        Positioned(
                          top: 5,
                          left: 5,
                          child: Card(
                            elevation: 0,
                            shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(7.47)),
                            color: kWhiteColor,
                            child: const Padding(
                              padding: EdgeInsets.symmetric(
                                  horizontal: 8.0, vertical: 8.0),
                              child: Icon(
                                Icons.favorite_rounded,
                                size: 18,
                                color: kDefaultColor,
                              ),
                            ),
                          ),
                        ),
                      ],
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
                          course!.title!.length < 38
                              ? course!.title.toString()
                              : course!.title!.substring(0, 37),
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
                              ' ${course!.average_rating.toString()}',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w400,
                                color: kGreyLightColor,
                              ),
                            ),
                            Text(
                              ' (${course!.total_reviews} Reviews)',
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
                          course!.price.toString(),
                          style: const TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                Expanded(
                  flex: 1,
                  child: Padding(
                    padding: const EdgeInsets.only(right: 10.0),
                    child: ClipOval(
                      child: InkWell(
                        onTap: () {
                          showDialog(
                            context: context,
                            builder: (BuildContext context) =>
                                buildPopupDialog(context, course!.id),
                          );
                        },
                        child: Container(
                          width: 20,
                          height: 40,
                          decoration: const BoxDecoration(
                            color: kRemoveIconColor,
                            boxShadow: [kDefaultShadow],
                          ),
                          child: const Icon(
                            Icons.remove,
                            color: kGreyLightColor,
                            size: 18,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
