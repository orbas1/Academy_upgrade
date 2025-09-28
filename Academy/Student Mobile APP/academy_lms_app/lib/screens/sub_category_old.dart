import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../constants.dart';
import '../providers/categories.dart';
import '../widgets/appbar_one.dart';
import 'courses_screen.dart';

class SubCategoryScreenTwo extends StatefulWidget {
  static const routeName = '/sub-cat';
  const SubCategoryScreenTwo({super.key});

  @override
  State<SubCategoryScreenTwo> createState() => _SubCategoryScreenTwoState();
}

class _SubCategoryScreenTwoState extends State<SubCategoryScreenTwo> {


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
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20.0),
            child: FutureBuilder(
              future: Provider.of<Categories>(context, listen: false).fetchSubCategories(categoryId),
              builder: (ctx, dataSnapshot) {
                if (dataSnapshot.connectionState == ConnectionState.waiting) {
                  return SizedBox(
                    height: MediaQuery.of(context).size.height * .5,
                    child: const Center(
                      child: CircularProgressIndicator(color: kDefaultColor),
                    ),
                  );
                } else {
                  if (dataSnapshot.error != null) {
                    return const Center(
                      child: Text('Error Occured'),
                      // child: Text(dataSnapshot.error.toString()),
                    );
                  } else {
                    return Consumer<Categories>(
                      builder: (context, myCourseData, child) => Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SizedBox(
                            height: 15,
                          ),
                          Text(
                            'Showing ${myCourseData.subItems.length} Courses',
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
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: myCourseData.subItems.length,
                            itemBuilder: (ctx, index) {
                              return InkWell(
                                onTap: () {
                                  Navigator.of(context).pushNamed(
                                    CoursesScreen.routeName,
                                    arguments: {
                                      'category_id': myCourseData.subItems[index].id,
                                      'seacrh_query': null,
                                      'type': CoursesPageData.category,
                                    },
                                  );
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
                                      child: Padding(
                                        padding: const EdgeInsets.symmetric(horizontal: 10.0),
                                        child: Row(
                                          // mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: <Widget>[
                                            Expanded(
                                              flex: 5,
                                              child: Container(
                                                padding:
                                                    const EdgeInsets.symmetric(vertical: 18, horizontal: 10),
                                                width: double.infinity,
                                                // height: 80,
                                                child:  Column(
                                                  children: <Widget>[
                                                    Align(
                                                      alignment: Alignment.centerLeft,
                                                      child: FittedBox(
                                                        fit: BoxFit.fitWidth,
                                                        child: Text(
                                                          '${index+1}. ${myCourseData.subItems[index].title}',
                                                          style: const TextStyle(
                                                            fontSize: 16,
                                                            fontWeight: FontWeight.w500,
                                                          ),
                                                        ),
                                                      ),
                                                    ),
                                                    Align(
                                                      alignment: Alignment.centerLeft,
                                                      child: Padding(
                                                        padding: const EdgeInsets.only(left: 18.0, top: 7),
                                                        child: Text(
                                                          '${myCourseData.subItems[index].numberOfCourses} Courses',
                                                          style: const TextStyle(
                                                            color: kGreyLightColor,
                                                            fontSize: 11,
                                                            fontWeight: FontWeight.w400
                                                          ),
                                                          textAlign: TextAlign.left,
                                                        ),
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ),
                                            Expanded(
                                              flex: 1,
                                              child: Card(
                                                color: kSignUpTextColor,
                                                elevation: 0,
                                                borderOnForeground: true,
                                                shape: RoundedRectangleBorder(
                                                  borderRadius: BorderRadius.circular(12.0),
                                                  side: const BorderSide(
                                                    color: kSignUpTextColor,
                                                    width: 1.0,
                                                  ),
                                                ),
                                                child: const Padding(
                                                  padding: EdgeInsets.symmetric(vertical: 14.0, horizontal: 12),
                                                  child: Icon(
                                                    Icons.arrow_forward_rounded,
                                                    color: kWhiteColor,
                                                    size: 18,
                                                  ),
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
                            },
                          ),
                        ],
                      ),
                    );
                  }
                }
              }
            ),
          ),
        ),
      ),
    );
  }
}