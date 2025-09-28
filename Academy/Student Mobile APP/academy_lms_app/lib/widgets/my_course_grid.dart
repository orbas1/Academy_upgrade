import 'package:flutter/material.dart';
import 'package:percent_indicator/percent_indicator.dart';

import '../constants.dart';
import '../models/my_course.dart';
import '../screens/my_course_detail.dart';

class MyCourseGrid extends StatefulWidget {
  final MyCourse? myCourse;

  const MyCourseGrid({
    super.key,
    @required this.myCourse,
  });

  @override
  State<MyCourseGrid> createState() => _MyCourseGridState();
}

class _MyCourseGridState extends State<MyCourseGrid> {
  @override
  void initState() {
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        Navigator.of(context).push(MaterialPageRoute(builder: (_) {
          return MyCourseDetailScreen(
              courseId: widget.myCourse!.id!,
              enableDripContent: widget.myCourse!.enableDripContent.toString());
        }));
      },
      child: Padding(
        padding: const EdgeInsets.only(right: 5.0),
        child: SizedBox(
          // width: 175,
          width: MediaQuery.of(context).size.width * .45,
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
            child: Card(
              color: kWhiteColor,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
              elevation: 0,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Text(widget.myCourse!.id!.toString()),
                  Padding(
                    padding: const EdgeInsets.all(8.0),
                    child: Stack(
                      fit: StackFit.loose,
                      alignment: Alignment.center,
                      clipBehavior: Clip.none,
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(10),
                          child: FadeInImage.assetNetwork(
                            placeholder: 'assets/images/loading_animated.gif',
                            image: widget.myCourse!.thumbnail.toString(),
                            height: 120,
                            width: double.infinity,
                            fit: BoxFit.cover,
                          ),
                        ),
                        // Positioned(
                        //   top: 5,
                        //   left: 5,
                        //   child: Card(
                        //     elevation: 0,
                        //     shape: RoundedRectangleBorder(
                        //       borderRadius: BorderRadius.circular(7.47)
                        //     ),
                        //     color: kWhiteColor,
                        //     child: const Padding(
                        //       padding: EdgeInsets.symmetric(horizontal: 6.0, vertical: 6.0),
                        //       child: Icon(
                        //           Icons.favorite_rounded,
                        //           size: 18,
                        //           color: kDefaultColor,
                        //         ),
                        //     ),
                        //   ),
                        // ),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 8.0),
                    child: SizedBox(
                      height: 50,
                      child: Align(
                        alignment: Alignment.centerLeft,
                        child: Text(
                          widget.myCourse!.title!,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    ),
                  ),
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: 8.0),
                    child: Row(
                      children: [
                        Expanded(
                          flex: 1,
                          child: Icon(
                            Icons.star,
                            color: kStarColor,
                            size: 18,
                          ),
                        ),
                        Expanded(
                          flex: 1,
                          child: Text(
                            widget.myCourse!.average_rating.toString(),
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w400,
                              color: kGreyLightColor,
                            ),
                          ),
                        ),
                        Expanded(
                          flex: 5,
                          child: Text(
                            '(${widget.myCourse!.total_reviews.toString()} Reviews)',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w400,
                              color: kGreyLightColor,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(
                    height: 10,
                  ),
                  SizedBox(
                    width: double.infinity,
                    child: LinearPercentIndicator(
                      lineHeight: 8.0,
                      percent: widget.myCourse!.courseCompletion! / 100,
                      progressColor: kSignUpTextColor,
                      backgroundColor: kGreyLightColor.withOpacity(0.3),
                      barRadius: const Radius.circular(8),
                    ),
                  ),
                  const SizedBox(
                    height: 15,
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 10.0),
                    child: Row(
                      children: [
                        Expanded(
                          flex: 1,
                          child: Text(
                            '${(widget.myCourse!.courseCompletion!).toString()}% completed',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w400,
                              color: kGreyLightColor,
                            ),
                          ),
                        ),
                        Text(
                          '${widget.myCourse!.totalNumberOfCompletedLessons}/${widget.myCourse!.totalNumberOfLessons}',
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w400,
                            color: kGreyLightColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(
                    height: 15,
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
