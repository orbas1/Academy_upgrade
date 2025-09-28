import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../constants.dart';
import '../providers/courses.dart';
import '../widgets/appbar_one.dart';
import '../widgets/course_list_item.dart';

class CoursesScreen extends StatefulWidget {
  static const routeName = '/courses';
  const CoursesScreen({super.key});

  @override
  State<CoursesScreen> createState() => _CoursesScreenState();
}

class _CoursesScreenState extends State<CoursesScreen> {
  var _isInit = true;
  var _isLoading = false;

  @override
  void initState() {
    super.initState();
  }

  @override
  void didChangeDependencies() {
    if (_isInit) {
      setState(() {
        _isLoading = true;
      });

      final routeArgs =
          ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;

      final pageDataType = routeArgs['type'] as CoursesPageData;
      if (pageDataType == CoursesPageData.category) {
        final categoryId = routeArgs['category_id'] as int;
        Provider.of<Courses>(context)
            .fetchCoursesByCategory(categoryId)
            .then((_) {
          setState(() {
            _isLoading = false;
          });
        });
      } else if (pageDataType == CoursesPageData.search) {
        final searchQuery = routeArgs['search_query'] as String;
        print(searchQuery);

        Provider.of<Courses>(context)
            .fetchCoursesBySearchQuery(searchQuery)
            .then((_) {
          setState(() {
            _isLoading = false;
          });
        });
      } else if (pageDataType == CoursesPageData.all) {
        Provider.of<Courses>(context)
            .filterCourses('all', 'all', 'all', 'all', 'all')
            .then((_) {
          setState(() {
            _isLoading = false;
          });
        });
      } else {
        setState(() {
          _isLoading = false;
        });
      }
    }
    _isInit = false;
    super.didChangeDependencies();
  }

  @override
  void dispose() {
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final courseData = Provider.of<Courses>(context, listen: false).items;
    final courseCount = courseData.length;
    return Scaffold(
      appBar: const AppBarOne(title: 'Courses'),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: kDefaultColor),
            )
          : Container(
              height: MediaQuery.of(context).size.height * 1,
              color: kBackGroundColor,
              child: SingleChildScrollView(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(
                        height: 15,
                      ),
                      Text(
                        'Showing $courseCount Courses',
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(
                        height: 20,
                      ),
                      ListView.builder(
                        shrinkWrap: true,
                        itemCount: courseData.length,
                        physics: const NeverScrollableScrollPhysics(),
                        itemBuilder: (ctx, index) {
                          return CourseListItem(
                            id: courseData[index].id,
                            title: courseData[index].title.toString(),
                            thumbnail: courseData[index].thumbnail.toString(),
                            // rating: courseData[index].rating!.toInt(),

                            price: courseData[index].price.toString(),
                            instructor: courseData[index].instructor.toString(),
                            average_rating:
                                courseData[index].average_rating.toString(),
                            total_reviews: courseData[index].total_reviews,
                            // noOfRating: courseData[index].totalNumberRating!.toInt(),
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ),
            ),
    );
  }
}
