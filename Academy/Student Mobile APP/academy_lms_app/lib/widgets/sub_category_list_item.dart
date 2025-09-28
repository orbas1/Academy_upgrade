import 'package:academy_lms_app/screens/courses_screen.dart';
import 'package:flutter/material.dart';
import 'package:html_unescape/html_unescape.dart';
import '../constants.dart';

class SubCategoryListItem extends StatelessWidget {
  final int? id;
  final String? title;
  final int? parent;
  final int? numberOfCourses;
  final int? index;

  const SubCategoryListItem(
      {super.key,
      @required this.id,
      @required this.title,
      @required this.parent,
      @required this.numberOfCourses,
      @required this.index});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () {
        Navigator.of(context).pushNamed(
          CoursesScreen.routeName,
          arguments: {
            'category_id': id,
            'search_query': null,
            'type': CoursesPageData.category,
          },
        );
      },
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 2),
        child: Row(
          children: [
            Expanded(
              flex: 1,
              child: Text(
                index! < 10 ? '0$index' : '${index! + 1}',
                style: TextStyle(
                  color: kGreyLightColor.withOpacity(0.6),
                  fontSize: 40,
                  fontWeight: FontWeight.w500
                ),
              ),
            ),
            Expanded(
              flex: 4,
              child: Container(
                padding:
                    const EdgeInsets.symmetric(vertical: 18, horizontal: 10),
                width: double.infinity,
                // height: 80,
                child: Column(
                  children: <Widget>[
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        '$numberOfCourses Courses',
                        style: const TextStyle(
                          color: kGreyLightColor,
                          fontSize: 11,
                          fontWeight: FontWeight.w400
                        ),
                        textAlign: TextAlign.left,
                      ),
                    ),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: FittedBox(
                        fit: BoxFit.fitWidth,
                        child: Text(
                          HtmlUnescape().convert(title!),
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Card(
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
                padding: EdgeInsets.symmetric(vertical: 12.0, horizontal: 12),
                child: Icon(
                  Icons.arrow_forward_rounded,
                  color: kWhiteColor,
                  size: 18,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
