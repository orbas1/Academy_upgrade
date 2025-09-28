import 'package:academy_lms_app/screens/course_detail.dart';
import 'package:flutter/material.dart';
import 'package:flutter_staggered_grid_view/flutter_staggered_grid_view.dart';
import 'package:provider/provider.dart';

import '../constants.dart';
import '../providers/categories.dart';
import '../widgets/appbar_one.dart';
import 'courses_screen.dart';
import 'sub_category.dart';

class CategoryDetailsScreen extends StatefulWidget {
  static const routeName = '/sub-cat';
  const CategoryDetailsScreen({super.key});

  @override
  State<CategoryDetailsScreen> createState() => _CategoryDetailsScreenState();
}

class _CategoryDetailsScreenState extends State<CategoryDetailsScreen> {
  @override
  void initState() {
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    final routeArgs =
        ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;

    final categoryId = routeArgs['category_id'] as int;
    final title = routeArgs['title'];
    return Scaffold(
      appBar: AppBarOne(title: title),
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: FutureBuilder(
            future: Provider.of<Categories>(context, listen: false)
                .fetchCategoryDetails(categoryId),
            builder: (ctx, dataSnapshot) {
              if (dataSnapshot.connectionState == ConnectionState.waiting) {
                return const Center(
                  child: CircularProgressIndicator(color: kDefaultColor),
                );
              } else {
                if (dataSnapshot.error != null) {
                  //error
                  return const Center(
                    child: Text('Error Occured'),
                  );
                } else {
                  return SingleChildScrollView(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 25.0),
                      child: Consumer<Categories>(
                          builder: (context, categoryDetails, child) {
                        final loadedCategoryDetail =
                            categoryDetails.getCategoryDetail;
                        return Column(
                          children: [
                            SizedBox(
                              width: double.infinity,
                              child: Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text(
                                    'Sub Categories',
                                    style: TextStyle(
                                      fontSize: 18,
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                  MaterialButton(
                                    onPressed: () {
                                      Navigator.of(context).pushNamed(
                                        SubCategoryScreen.routeName,
                                        arguments: {
                                          'category_id': categoryId,
                                          'title': title,
                                        },
                                      );
                                    },
                                    padding: const EdgeInsets.all(0),
                                    child: const Row(
                                      children: [
                                        Text(
                                          'Show all',
                                          style: TextStyle(
                                            color: kSignUpTextColor,
                                            fontSize: 18,
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
                              height: MediaQuery.of(context).size.height * .13,
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
                              child: ListView.builder(
                                  scrollDirection: Axis.horizontal,
                                  itemCount:
                                      loadedCategoryDetail.mSubCategory!.length,
                                  itemBuilder: (ctx, index) {
                                    return InkWell(
                                      onTap: () {
                                        Navigator.of(context).pushNamed(
                                          CoursesScreen.routeName,
                                          arguments: {
                                            'category_id': loadedCategoryDetail
                                                .mSubCategory![index].id,
                                            'search_query': null,
                                            'type': CoursesPageData.category,
                                          },
                                        );
                                      },
                                      child: Padding(
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 2.0, vertical: 10),
                                        child: SizedBox(
                                          width: MediaQuery.of(context)
                                                  .size
                                                  .width *
                                              .435,
                                          child: Card(
                                            elevation: 0,
                                            shape: RoundedRectangleBorder(
                                              borderRadius:
                                                  BorderRadius.circular(16),
                                            ),
                                            child: Padding(
                                              padding:
                                                  const EdgeInsets.symmetric(
                                                      horizontal: 8.0),
                                              child: Row(
                                                children: [
                                                  Expanded(
                                                    flex: 3,
                                                    child: ClipRRect(
                                                      borderRadius:
                                                          BorderRadius.circular(
                                                              8),
                                                      child: FadeInImage
                                                          .assetNetwork(
                                                        placeholder:
                                                            'assets/images/loading_animated.gif',
                                                        image:
                                                            loadedCategoryDetail
                                                                .mSubCategory![
                                                                    index]
                                                                .thumbnail
                                                                .toString(),
                                                        height: 60,
                                                        width: double.infinity,
                                                        fit: BoxFit.cover,
                                                      ),
                                                    ),
                                                  ),
                                                  Expanded(
                                                    flex: 4,
                                                    child: Padding(
                                                      padding:
                                                          const EdgeInsets.only(
                                                              left: 10.0,
                                                              right: 5.0),
                                                      child: Column(
                                                        crossAxisAlignment:
                                                            CrossAxisAlignment
                                                                .start,
                                                        mainAxisAlignment:
                                                            MainAxisAlignment
                                                                .center,
                                                        children: [
                                                          Text(
                                                            "${loadedCategoryDetail.mSubCategory![index].numberOfCourses.toString()} Courses",
                                                            style: const TextStyle(
                                                                color:
                                                                    kGreyLightColor,
                                                                fontSize: 10,
                                                                fontWeight:
                                                                    FontWeight
                                                                        .w500),
                                                            textAlign:
                                                                TextAlign.left,
                                                          ),
                                                          Text(
                                                            loadedCategoryDetail
                                                                .mSubCategory![
                                                                    index]
                                                                .title
                                                                .toString(),
                                                            maxLines: 1,
                                                            overflow:
                                                                TextOverflow
                                                                    .ellipsis,
                                                            style:
                                                                const TextStyle(
                                                              fontSize: 14,
                                                              fontWeight:
                                                                  FontWeight
                                                                      .w500,
                                                            ),
                                                          ),
                                                        ],
                                                      ),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ),
                                        ),
                                      ),
                                    );
                                  }),
                            ),
                            SizedBox(
                              width: double.infinity,
                              child: Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text(
                                    'Courses',
                                    style: TextStyle(
                                      fontSize: 18,
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
                                            fontSize: 18,
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
                            SizedBox(
                              height: MediaQuery.of(context).size.height * .7,
                              child: AlignedGridView.count(
                                  shrinkWrap: true,
                                  crossAxisCount: 2,
                                  itemCount:
                                      loadedCategoryDetail.mCourse!.length,
                                  mainAxisSpacing: 5,
                                  crossAxisSpacing: 5,
                                  itemBuilder: (ctx, index) {
                                    return InkWell(
                                      onTap: () {
                                            Navigator.of(context).pushNamed(
                                          CourseDetailScreen.routeName,
                                          arguments: loadedCategoryDetail.mCourse![index].id,
                                        );

                                        // Navigator.of(context).push(
                                        //     MaterialPageRoute(
                                        //         builder: (context) =>
                                        //             CourseDetailScreen1(
                                        //               courseId:
                                        //                   loadedCategoryDetail
                                        //                       .mCourse![index]
                                        //                       .id
                                        //                       .toString(),
                                        //             )));
                                      },
                                      child: SizedBox(
                                        width: double.infinity,
                                        child: Container(
                                          decoration: BoxDecoration(
                                            boxShadow: [
                                              BoxShadow(
                                                color: kBackButtonBorderColor
                                                    .withOpacity(0.07),
                                                blurRadius: 10,
                                                offset: const Offset(0, 5),
                                              ),
                                            ],
                                          ),
                                          child: Card(
                                            shape: RoundedRectangleBorder(
                                              borderRadius:
                                                  BorderRadius.circular(16),
                                            ),
                                            elevation: 0,
                                            child: Column(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment.start,
                                              children: [
                                                Padding(
                                                  padding:
                                                      const EdgeInsets.all(8.0),
                                                  child: ClipRRect(
                                                    borderRadius:
                                                        BorderRadius.circular(
                                                            8),
                                                    child: FadeInImage
                                                        .assetNetwork(
                                                      placeholder:
                                                          'assets/images/loading_animated.gif',
                                                      image:
                                                          loadedCategoryDetail
                                                              .mCourse![index]
                                                              .thumbnail
                                                              .toString(),
                                                      height: 120,
                                                      width: double.infinity,
                                                      fit: BoxFit.cover,
                                                    ),
                                                  ),
                                                ),
                                                Padding(
                                                  padding: const EdgeInsets
                                                      .symmetric(
                                                      horizontal: 8.0,
                                                      vertical: 5.0),
                                                  child: SizedBox(
                                                    height: 50,
                                                    child: Text(
                                                      loadedCategoryDetail
                                                          .mCourse![index].title
                                                          .toString(),
                                                      style: const TextStyle(
                                                        fontSize: 15,
                                                        fontWeight:
                                                            FontWeight.w500,
                                                      ),
                                                    ),
                                                  ),
                                                ),
                                                Padding(
                                                  padding: EdgeInsets.symmetric(
                                                      horizontal: 8.0),
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
                                                          loadedCategoryDetail
                                                              .mCourse![index]
                                                              .average_rating
                                                              .toString(),
                                                          style: TextStyle(
                                                            fontSize: 12,
                                                            fontWeight:
                                                                FontWeight.w400,
                                                            color:
                                                                kGreyLightColor,
                                                          ),
                                                        ),
                                                      ),
                                                      Expanded(
                                                        flex: 5,
                                                        child: Text(
                                                          '(${loadedCategoryDetail.mCourse![index].total_reviews} Reviews)',
                                                          style: TextStyle(
                                                            fontSize: 12,
                                                            fontWeight:
                                                                FontWeight.w400,
                                                            color:
                                                                kGreyLightColor,
                                                          ),
                                                        ),
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                                const SizedBox(height: 10),
                                              ],
                                            ),
                                          ),
                                        ),
                                      ),
                                    );
                                  }),
                            ),
                          ],
                        );
                      }),
                    ),
                  );
                }
              }
            }),
      ),
    );
  }
}
