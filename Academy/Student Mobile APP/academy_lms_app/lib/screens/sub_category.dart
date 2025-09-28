import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../constants.dart';
import '../providers/categories.dart';
import '../widgets/appbar_one.dart';
import '../widgets/sub_category_list_item.dart';

class SubCategoryScreen extends StatefulWidget {
  static const routeName = '/sub-category';
  const SubCategoryScreen({super.key});

  @override
  State<SubCategoryScreen> createState() => _SubCategoryScreenState();
}

class _SubCategoryScreenState extends State<SubCategoryScreen> {

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
                    //error
                    return const Center(
                      child: Text('Error Occured'),
                      // child: Text(dataSnapshot.error.toString()),
                    );
                  } else {
                    return Consumer<Categories>(
                      builder: (context, myCourseData, child) {
                        return Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(
                              height: 15,
                            ),
                            Text(
                              'Showing ${myCourseData.subItems.length} Sub-Categories',
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
                                return SubCategoryListItem(
                                  id: myCourseData.subItems[index].id,
                                  title: myCourseData.subItems[index].title,
                                  parent: myCourseData.subItems[index].parentId,
                                  numberOfCourses: myCourseData.subItems[index].numberOfCourses,
                                  index: index,
                                );
                              },
                            ),
                          ],
                        );
                      }
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