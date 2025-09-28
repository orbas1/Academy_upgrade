import 'package:academy_lms_app/screens/course_detail.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../constants.dart';
import '../providers/categories.dart';
import '../providers/courses.dart';
import '../widgets/common_functions.dart';
import 'category_details.dart';
import 'courses_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  var _isInit = true;
  var topCourses = [];
  var bundles = [];
  dynamic bundleStatus;

  @override
  void initState() {
    super.initState();
  }

  @override
  void didChangeDependencies() {
    if (_isInit) {
      setState(() {});

      Provider.of<Courses>(context).fetchTopCourses().then((_) {
        setState(() {
          topCourses = Provider.of<Courses>(context, listen: false).topItems;
        });
      });
    }
    _isInit = false;
    super.didChangeDependencies();
  }

  Future<void> refreshList() async {
    try {
      setState(() {});
      await Provider.of<Courses>(context, listen: false).fetchTopCourses();

      setState(() {
        topCourses = Provider.of<Courses>(context, listen: false).topItems;
      });
    } catch (error) {
      const errorMsg = 'Could not refresh!';
      // ignore: use_build_context_synchronously
      CommonFunctions.showErrorDialog(errorMsg, context);
    }

    return;
  }

  @override
  Widget build(BuildContext context) {
    final height = MediaQuery.of(context).size.height -
        MediaQuery.of(context).padding.top -
        kToolbarHeight -
        50;
    return Container(
      height: MediaQuery.of(context).size.height * 1,
      color: kBackGroundColor,
      child: RefreshIndicator(
        onRefresh: refreshList,
        child: SingleChildScrollView(
          child: FutureBuilder(
              future: Provider.of<Categories>(context, listen: false)
                  .fetchCategories(),
              builder: (ctx, dataSnapshot) {
                if (dataSnapshot.connectionState == ConnectionState.waiting) {
                  return SizedBox(
                    height: height,
                    child: const Center(
                      child: CupertinoActivityIndicator(
                        color: kDefaultColor,
                      ),
                    ),
                  );
                } else {
                  if (dataSnapshot.error != null) {
                    return Center(
                      // child: Text('Error Occured'),
                      child: Text(dataSnapshot.error.toString()),
                    );
                  } else {
                    return Column(
                      children: [
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(
                              horizontal: 20, vertical: 5),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'Top Course',
                                style: TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              MaterialButton(
                                onPressed: () {
                                  Navigator.of(context).pushNamed(
                                    CoursesScreen.routeName,
                                    arguments: {
                                      'category_id': null,
                                      'seacrh_query': null,
                                      'type': CoursesPageData.all,
                                    },
                                  );
                                },
                                padding: const EdgeInsets.all(0),
                                child: const Row(
                                  children: [
                                    Text(
                                      'All courses',
                                      style: TextStyle(
                                        color: kSignUpTextColor,
                                        fontSize: 16,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                    Icon(
                                      Icons.arrow_forward_ios_rounded,
                                      color: kSignUpTextColor,
                                      size: 18,
                                    ),
                                  ],
                                ),
                              )
                            ],
                          ),
                        ),
                        Container(
                          margin: const EdgeInsets.only(bottom: 15.0),
                          padding: const EdgeInsets.symmetric(horizontal: 20),
                          height: 255.0,
                          child: ListView.builder(
                            scrollDirection: Axis.horizontal,
                            itemCount: topCourses.length,
                            itemBuilder: (ctx, index) {
                              final course = topCourses[index];
                              final courseId = course.id;

                              return Padding(
                                padding: const EdgeInsets.only(right: 8.0),
                                child: InkWell(
                                  onTap: () {
                                    Navigator.of(context).pushNamed(
                                      CourseDetailScreen.routeName,
                                      arguments: courseId,
                                    );
                                    // Navigator.of(context).push(
                                    //     MaterialPageRoute(
                                    //         builder: (context) =>
                                    //             CourseDetailScreen1(
                                    //               courseId: topCourses[index].id,
                                    //             )));
                                  },
                                  child: Container(
                                    decoration: BoxDecoration(
                                      boxShadow: [
                                        BoxShadow(
                                          color: kBackButtonBorderColor
                                              .withOpacity(0.023),
                                          blurRadius: 10,
                                          offset: const Offset(0, 0),
                                        ),
                                      ],
                                    ),
                                    width:
                                        MediaQuery.of(context).size.width * .45,
                                    child: Card(
                                      color: kWhiteColor,
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      elevation: 0,
                                      shadowColor: kWhiteColor,
                                      child: Column(
                                        children: [
                                          Padding(
                                            padding: const EdgeInsets.all(8.0),
                                            child: ClipRRect(
                                              borderRadius:
                                                  BorderRadius.circular(10),
                                              child: FadeInImage.assetNetwork(
                                                placeholder:
                                                    'assets/images/loading_animated.gif',
                                                image:
                                                    course.thumbnail.toString(),
                                                height: 120,
                                                width: 200,
                                                fit: BoxFit.cover,
                                              ),
                                            ),
                                          ),
                                          Padding(
                                            padding: const EdgeInsets.symmetric(
                                                horizontal: 8.0),
                                            child: SizedBox(
                                              height: 50,
                                              child: Text(
                                                '${course.title}...',
                                                style: const TextStyle(
                                                  fontSize: 16,
                                                  fontWeight: FontWeight.w500,
                                                ),
                                              ),
                                            ),
                                          ),
                                          Padding(
                                            padding: const EdgeInsets.symmetric(
                                                horizontal: 8.0),
                                            child: Row(
                                              children: [
                                                const Expanded(
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
                                                    course.average_rating
                                                        .toString(),
                                                    style: const TextStyle(
                                                      fontSize: 12,
                                                      fontWeight:
                                                          FontWeight.w400,
                                                      color: kGreyLightColor,
                                                    ),
                                                  ),
                                                ),
                                                Expanded(
                                                    flex: 5,
                                                    child: Text(
                                                      '(${course.total_reviews.toString()} Reviews)',
                                                      style: const TextStyle(
                                                        fontSize: 12,
                                                        fontWeight:
                                                            FontWeight.w400,
                                                        color: kGreyLightColor,
                                                      ),
                                                    )),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                ),
                              );
                            },
                          ),
                        ),

                        // Container(
                        //   margin: const EdgeInsets.only(bottom: 15.0),
                        //   padding: const EdgeInsets.symmetric(horizontal: 20),
                        //   height: 225.0,
                        //   // height: MediaQuery.of(context).size.height * .3,
                        //   child: ListView.builder(
                        //     scrollDirection: Axis.horizontal,
                        //     itemCount: topCourses.length,
                        //     itemBuilder: (ctx, index) {
                        //       return Padding(
                        //         padding: const EdgeInsets.only(right: 8.0),
                        //         child: InkWell(
                        //           onTap: () {
                        //             Navigator.of(context).pushNamed(
                        //                 CourseDetailScreen.routeName,
                        //                 arguments: topCourses[index].id);
                        //           },
                        //           child: Container(
                        //             decoration: BoxDecoration(
                        //               boxShadow: [
                        //                 BoxShadow(
                        //                   color: kBackButtonBorderColor
                        //                       .withOpacity(0.023),
                        //                   blurRadius: 10,
                        //                   offset: const Offset(0, 0),
                        //                 ),
                        //               ],
                        //             ),
                        //             // width: 175,
                        //             width:
                        //                 MediaQuery.of(context).size.width * .45,
                        //             child: Card(
                        //               color: kWhiteColor,
                        //               shape: RoundedRectangleBorder(
                        //                 borderRadius: BorderRadius.circular(12),
                        //               ),
                        //               elevation: 0,
                        //               shadowColor: kWhiteColor,
                        //               child: Column(
                        //                 children: [
                        //                   Padding(
                        //                     padding: const EdgeInsets.all(8.0),
                        //                     child: ClipRRect(
                        //                       borderRadius:
                        //                           BorderRadius.circular(10),
                        //                       child: FadeInImage.assetNetwork(
                        //                         placeholder:
                        //                             'assets/images/loading_animated.gif',
                        //                         image: topCourses[index]
                        //                             .thumbnail
                        //                             .toString(),
                        //                         height: 120,
                        //                         width: 200,
                        //                         fit: BoxFit.cover,
                        //                       ),
                        //                     ),
                        //                   ),
                        //                   Padding(
                        //                     padding: const EdgeInsets.symmetric(
                        //                         horizontal: 8.0),
                        //                     child: SizedBox(
                        //                       height: 50,
                        //                       child: Text(
                        //                         '${topCourses[index].title}...',
                        //                         style: const TextStyle(
                        //                           fontSize: 16,
                        //                           fontWeight: FontWeight.w500,
                        //                         ),
                        //                       ),
                        //                     ),
                        //                   ),
                        //                   Padding(
                        //                     padding: const EdgeInsets.symmetric(
                        //                         horizontal: 8.0),
                        //                     child: Row(
                        //                       children: [
                        //                         const Expanded(
                        //                           flex: 1,
                        //                           child: Icon(
                        //                             Icons.star,
                        //                             color: kStarColor,
                        //                             size: 18,
                        //                           ),
                        //                         ),
                        //                         Expanded(
                        //                           flex: 1,
                        //                           child: Consumer<ReviewProvider>(
                        //                             builder:
                        //                                 (ctx, reviewProvider, _) {
                        //                               final review =
                        //                                   reviewProvider.review;

                        //                               // Check if review data is available
                        //                               if (review != null) {
                        //                                 return Text(
                        //                                   review.averageRating
                        //                                       .toStringAsFixed(
                        //                                           1), // Show average rating
                        //                                   style: const TextStyle(
                        //                                     fontSize: 12,
                        //                                     fontWeight:
                        //                                         FontWeight.w400,
                        //                                     color:
                        //                                         kGreyLightColor,
                        //                                   ),
                        //                                 );
                        //                               } else if (reviewProvider
                        //                                   .isLoading) {
                        //                                 return const CircularProgressIndicator(); // Show loading indicator
                        //                               } else {
                        //                                 return const Text(
                        //                                   'No data',
                        //                                   style: TextStyle(
                        //                                     fontSize: 12,
                        //                                     fontWeight:
                        //                                         FontWeight.w400,
                        //                                     color:
                        //                                         kGreyLightColor,
                        //                                   ),
                        //                                 );
                        //                               }
                        //                             },
                        //                           ),
                        //                         ),
                        //                         Expanded(
                        //                           flex: 5,
                        //                           child: Consumer<ReviewProvider>(
                        //                             builder:
                        //                                 (ctx, reviewProvider, _) {
                        //                               final review =
                        //                                   reviewProvider.review;

                        //                               if (review != null) {
                        //                                 return Text(
                        //                                   '(${review.totalReviews} Reviews)', // Show total reviews
                        //                                   style: const TextStyle(
                        //                                     fontSize: 12,
                        //                                     fontWeight:
                        //                                         FontWeight.w400,
                        //                                     color:
                        //                                         kGreyLightColor,
                        //                                   ),
                        //                                 );
                        //                               } else {
                        //                                 return const SizedBox
                        //                                     .shrink(); // Show nothing if no data
                        //                               }
                        //                             },
                        //                           ),
                        //                         ),
                        //                       ],
                        //                     ),
                        //                   ),
                        //                   // const Padding(
                        //                   //   padding: EdgeInsets.symmetric(horizontal: 8.0),
                        //                   //   child: Row(
                        //                   //     children: [
                        //                   //       Expanded(
                        //                   //         flex: 1,
                        //                   //         child: Icon(
                        //                   //           Icons.star,
                        //                   //           color: kStarColor,
                        //                   //           size: 18,
                        //                   //         ),
                        //                   //       ),
                        //                   //       Expanded(
                        //                   //         flex: 1,
                        //                   //         child: Text(
                        //                   //           "${Provider.of<ReviewProvider>(context, listen: false).fetchReview(courseId)}",
                        //                   //           style: TextStyle(
                        //                   //             fontSize: 12,
                        //                   //             fontWeight: FontWeight.w400,
                        //                   //             color: kGreyLightColor,
                        //                   //           ),
                        //                   //         ),
                        //                   //       ),
                        //                   //       Expanded(
                        //                   //         flex: 5,
                        //                   //         child: Text(
                        //                   //           '(30 Reviews)',
                        //                   //           style: TextStyle(
                        //                   //             fontSize: 12,
                        //                   //             fontWeight: FontWeight.w400,
                        //                   //             color: kGreyLightColor,
                        //                   //           ),
                        //                   //         ),
                        //                   //       ),
                        //                   //     ],
                        //                   //   ),
                        //                   // ),
                        //                 ],
                        //               ),
                        //             ),
                        //           ),
                        //         ),
                        //       );
                        //     },
                        //   ),
                        // ),

                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(horizontal: 20),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const Text(
                                'Course Categories',
                                style: TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                              MaterialButton(
                                onPressed: () {
                                  Navigator.of(context).pushNamed(
                                    CoursesScreen.routeName,
                                    arguments: {
                                      'category_id': null,
                                      'seacrh_query': null,
                                      'type': CoursesPageData.all,
                                    },
                                  );
                                },
                                padding: const EdgeInsets.all(0),
                                child: const Row(
                                  children: [
                                    Text(
                                      'All courses',
                                      style: TextStyle(
                                        color: kSignUpTextColor,
                                        fontSize: 16,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                    Icon(
                                      Icons.arrow_forward_ios_rounded,
                                      color: kSignUpTextColor,
                                      size: 18,
                                    ),
                                  ],
                                ),
                              )
                            ],
                          ),
                        ),
                        Consumer<Categories>(
                          builder: (context, myCourseData, child) => Padding(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 20, vertical: 5),
                            child: ListView.builder(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: myCourseData.items.length,
                              padding: EdgeInsets.zero,
                              itemBuilder: (ctx, index) {
                                return InkWell(
                                  onTap: () {
                                    Navigator.of(context).pushNamed(
                                      CategoryDetailsScreen.routeName,
                                      arguments: {
                                        'category_id':
                                            myCourseData.items[index].id,
                                        'title':
                                            myCourseData.items[index].title,
                                      },
                                    );
                                  },
                                  child: Column(
                                    children: [
                                      Row(
                                        // mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: <Widget>[
                                          Expanded(
                                            flex: 1,
                                            child: Padding(
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                      vertical: 10.0),
                                              child: ClipRRect(
                                                borderRadius:
                                                    BorderRadius.circular(10),
                                                child: FadeInImage.assetNetwork(
                                                  placeholder:
                                                      'assets/images/loading_animated.gif',
                                                  image: myCourseData
                                                      .items[index].thumbnail
                                                      .toString(),
                                                  height: 56.38,
                                                  width: 68.98,
                                                  fit: BoxFit.cover,
                                                ),
                                              ),
                                            ),
                                          ),
                                          Expanded(
                                            flex: 3,
                                            child: Container(
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                      vertical: 10,
                                                      horizontal: 20),
                                              width: double.infinity,
                                              // height: 80,
                                              child: Column(
                                                children: <Widget>[
                                                  Align(
                                                    alignment:
                                                        Alignment.centerLeft,
                                                    child: Text(
                                                      '${myCourseData.items[index].numberOfSubCategories} sub-categories',
                                                      style: const TextStyle(
                                                          color:
                                                              kGreyLightColor,
                                                          fontSize: 12,
                                                          fontWeight:
                                                              FontWeight.w500),
                                                      textAlign: TextAlign.left,
                                                    ),
                                                  ),
                                                  Align(
                                                    alignment:
                                                        Alignment.centerLeft,
                                                    child: FittedBox(
                                                      fit: BoxFit.fitWidth,
                                                      child: Text(
                                                        myCourseData
                                                            .items[index].title
                                                            .toString(),
                                                        maxLines: 1,
                                                        overflow: TextOverflow
                                                            .ellipsis,
                                                        style: const TextStyle(
                                                          fontSize: 16,
                                                          fontFamily: 'Poppins',
                                                          fontWeight:
                                                              FontWeight.w500,
                                                        ),
                                                      ),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ),
                                          Center(
                                            child: Stack(
                                              children: [
                                                Positioned.fill(
                                                  child: Container(
                                                    decoration: BoxDecoration(
                                                      borderRadius:
                                                          BorderRadius.circular(
                                                              10),
                                                      gradient:
                                                          const LinearGradient(
                                                        colors: [
                                                          Color(0xFFCC61FF),
                                                          Color(0xFF5851EF),
                                                        ],
                                                        stops: [0.05, 0.88],
                                                        begin:
                                                            Alignment.topLeft,
                                                        end: Alignment
                                                            .centerLeft,
                                                      ),
                                                    ),
                                                  ),
                                                ),
                                                const Padding(
                                                  padding: EdgeInsets.symmetric(
                                                      vertical: 12.0,
                                                      horizontal: 12),
                                                  child: Icon(
                                                    Icons.arrow_forward_rounded,
                                                    color: kWhiteColor,
                                                    size: 18,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                      if (index != 9)
                                        Divider(
                                          thickness: 1,
                                          height: 1,
                                          color:
                                              kGreyLightColor.withOpacity(0.30),
                                        ),
                                    ],
                                  ),
                                );
                              },
                            ),
                          ),
                        ),
                      ],
                    );
                  }
                }
              }),
        ),
      ),
    );
  }
}
