import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_staggered_grid_view/flutter_staggered_grid_view.dart';
import 'package:provider/provider.dart';

import '../constants.dart';
import '../providers/my_courses.dart';
import '../widgets/my_course_grid.dart';

class MyCoursesScreen extends StatefulWidget {
  const MyCoursesScreen({super.key});

  @override
  State<MyCoursesScreen> createState() => _MyCoursesScreenState();
}

class _MyCoursesScreenState extends State<MyCoursesScreen> {
  @override
  void initState() {
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 15.0),
            child: Column(
              children: [
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 5.0, vertical: 10),
                  width: double.infinity,
                  child: const Text(
                    'My Courses',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                courseView(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget courseView() {
    final height = MediaQuery.of(context).size.height -
        MediaQuery.of(context).padding.top -
        kToolbarHeight -
        150;
    return FutureBuilder(
      future: Provider.of<MyCourses>(context, listen: false).fetchMyCourses(),
      builder: (ctx, dataSnapshot) {
        if (dataSnapshot.connectionState == ConnectionState.waiting) {
          return SizedBox(
            height: height,
            child: const Center(
              child: CupertinoActivityIndicator(color: kDefaultColor),
            ),
          );
        } else {
          if (dataSnapshot.error != null) {
            //error
            return Center(
              // child: Text('Error Occured'),
              child: Text(dataSnapshot.error.toString()),
            );
          } else {
            return Consumer<MyCourses>(
              builder: (context, myCourseData, child) => AlignedGridView.count(
                shrinkWrap: true,
                crossAxisCount: 2,
                mainAxisSpacing: 5,
                crossAxisSpacing: 0,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: myCourseData.items.length,
                itemBuilder: (ctx, index) {
                  return MyCourseGrid(
                    myCourse: myCourseData.items[index],
                  );
                  // return Text(myCourseData.items[index].title);
                },
              ),
            );
          }
        }
      },
    );
  }
}
