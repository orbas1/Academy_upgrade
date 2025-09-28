// ignore_for_file: avoid_print, use_build_context_synchronously

import 'package:academy_lms_app/widgets/appbar_one.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../constants.dart';
import '../models/all_category.dart';
import '../models/category.dart';
import '../providers/categories.dart';
import '../providers/courses.dart';
import '../providers/misc_provider.dart';
import '../widgets/common_functions.dart';
import '../widgets/custom_text.dart';
import '../widgets/star_display_widget.dart';
import 'courses_screen.dart';

class FilterScreen extends StatefulWidget {
  const FilterScreen({super.key});

  @override
  State<FilterScreen> createState() => _FilterScreenState();
}

class _FilterScreenState extends State<FilterScreen> {
  final GlobalKey<FormState> _formKey = GlobalKey();
  final _keywordController = TextEditingController();

  var _isInit = true;
  var _isLoading = false;
  var subIndex = 0;
  var data = <AllSubCategory>[];
  String _selectedCategory = 'all';
  dynamic _selectedSubCat;
  String _selectedPrice = 'all';
  String _selectedLevel = 'all';
  String _selectedLanguage = 'all';
  String _selectedRating = 'all';

  get kBackgroundColor => null;

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

      Provider.of<Categories>(context, listen: false)
          .fetchAllCategory()
          .then((_) {
        setState(() {
          _isLoading = false;
        });
      });

      Provider.of<Languages>(context, listen: false).fetchLanguages().then((_) {
        setState(() {
          _isLoading = false;
        });
      });
    }
    _isInit = false;
    super.didChangeDependencies();
  }

  void _resetForm() {
    setState(() {
      _selectedCategory = 'all';
      _selectedSubCat = null;
      _selectedPrice = 'all';
      _selectedLevel = 'all';
      _selectedLanguage = 'all';
      _selectedRating = 'all';
    });
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    _formKey.currentState!.save();

    setState(() {
      _isLoading = true;
    });

    try {
      if (_selectedSubCat != null) {
        await Provider.of<Courses>(context, listen: false).filterCourses(
            _selectedSubCat!,
            _selectedPrice,
            _selectedLevel,
            _selectedLanguage,
            _selectedRating);
      } else {
        await Provider.of<Courses>(context, listen: false).filterCourses(
            _selectedCategory,
            _selectedPrice,
            _selectedLevel,
            _selectedLanguage,
            _selectedRating);
      }
      Navigator.of(context).pushNamed(
        CoursesScreen.routeName,
        arguments: {
          'category_id': null,
          'search_query': null,
          'type': CoursesPageData.filter,
        },
      );
    } catch (error) {
      const errorMsg = 'Could not process request!';
      CommonFunctions.showErrorDialog(errorMsg, context);
    }
    setState(() {
      _isLoading = false;
    });
  }

  // void _handleSubmitted(String value) {
  //   final searchText = _keywordController.text;
  //   if (searchText.isEmpty) {
  //     return;
  //   }

  //   _keywordController.clear();
  //   Navigator.of(context).pushNamed(
  //     CoursesScreen.routeName,
  //     arguments: {
  //       'category_id': null,
  //       'search_query': searchText,
  //       'type': CoursesPageData.search,
  //     },
  //   );
  // }
void _handleSubmitted(String value) {
  final searchText = _keywordController.text.trim();
  if (searchText.isEmpty) {
    CommonFunctions.showErrorDialog('Search query cannot be empty', context);
    return;
  }

  
  Navigator.of(context).pushNamed(
    CoursesScreen.routeName,
    arguments: {
      'category_id': null,
      'search_query': searchText,
      'type': CoursesPageData.search,
    },
  );
  _keywordController.clear();
}

  InputDecoration getInputDecoration(String hintext) {
    return InputDecoration(
      enabledBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      border: OutlineInputBorder(
        borderRadius: const BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: kDefaultColor.withOpacity(0.1), width: 1),
      ),
      focusedErrorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      errorBorder: const OutlineInputBorder(
        borderRadius: BorderRadius.all(Radius.circular(16.0)),
        borderSide: BorderSide(color: Color(0xFFF65054)),
      ),
      filled: true,
      hintStyle: const TextStyle(color: Colors.black54, fontSize: 16),
      hintText: hintext,
      fillColor: kInputBoxBackGroundColor,
      contentPadding: const EdgeInsets.symmetric(vertical: 15, horizontal: 15),
      suffixIcon: GestureDetector(
        onTap: () {
          _handleSubmitted(_keywordController.text);
        },
        child: const Icon(Icons.search_rounded),
      ),
      suffixIconColor: kDefaultColor.withOpacity(0.8),
    );
  }

  @override
  Widget build(BuildContext context) {
    final catData = Provider.of<Categories>(context, listen: false).items;
    catData.insert(
        0,
        Category(
            id: 0,
            title: 'All Category',
            thumbnail: null,
            numberOfCourses: null,
            numberOfSubCategories: null));
    final langData = Provider.of<Languages>(context, listen: false).items;
    langData.insert(
        0, Language(id: 0, value: 'all', displayedValue: 'All Language'));
    final allCategory =
        Provider.of<Categories>(context, listen: false).allItems;
    allCategory.insert(
        0, AllCategory(id: 0, title: 'All Category', subCategory: data));

    return Scaffold(
      appBar: const AppBarOne(title: 'Filter Courses'),
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: _isLoading
            ? const Center(
                child: CircularProgressIndicator(color: kDefaultColor),
              )
            : SingleChildScrollView(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 5.0),
                        child: TextFormField(
                          style: const TextStyle(fontSize: 14),
                          decoration: getInputDecoration(
                            'Search',
                          ),
                          controller: _keywordController,
                          keyboardType: TextInputType.text,
                          validator: (value) {
                            if (value!.isEmpty) {
                              return 'Keyword cannot be empty';
                            }
                            return null;
                          },
                          onSaved: (value) {
                            _keywordController.text = value as String;
                          },
                          onChanged: (value) {
                            _keywordController.text = value;
                          },
                          onFieldSubmitted: _handleSubmitted,
                        ),
                      ),
                      const SizedBox(
                        height: 25,
                      ),
                      Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                              children: [
                                const CustomText(
                                  text: 'Category',
                                  fontSize: 18,
                                  fontWeight: FontWeight.w500,
                                ),
                                const Spacer(),
                                InkWell(
                                  onTap: _resetForm,
                                  child: const CustomText(
                                    text: 'Reset',
                                    fontSize: 18,
                                    fontWeight: FontWeight.w500,
                                    colors: kDefaultColor,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            Card(
                              elevation: 0.0,
                              margin: EdgeInsets.zero,
                              color: kInputBoxBackGroundColor,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 20, vertical: 5),
                                child: DropdownButton(
                                  underline: const SizedBox(),
                                  icon: Icon(
                                    Icons.keyboard_arrow_down_outlined,
                                    size: 22,
                                    color: kGreyLightColor.withOpacity(.9),
                                  ),
                                  value: _selectedCategory,
                                  onChanged: (value) {
                                    setState(() {
                                      _selectedSubCat = null;
                                      _selectedCategory = value.toString();
                                      // subIndex = allCategory.indexWhere(
                                      //     (cat) => cat.id.toString() == value);

                                      data = allCategory[subIndex].subCategory!;
                                      print(data);
                                    });
                                  },
                                  isExpanded: true,
                                  items: allCategory.map((cd) {
                                    return DropdownMenuItem(
                                      value:
                                          cd.id == 0 ? 'all' : cd.id.toString(),
                                      onTap: () {
                                        setState(() {
                                          subIndex = allCategory.indexOf(cd);
                                          print(subIndex);
                                        });
                                      },
                                      child: Text(
                                        cd.title.toString(),
                                        style: const TextStyle(
                                          color: kSecondaryColor,
                                          fontSize: 15,
                                        ),
                                      ),
                                    );
                                  }).toList(),
                                ),
                              ),
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            const CustomText(
                              text: 'Sub Category',
                              fontSize: 16,
                              fontWeight: FontWeight.w400,
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            Card(
                              elevation: 0.0,
                              margin: EdgeInsets.zero,
                              color: kInputBoxBackGroundColor,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 20, vertical: 5),
                                child: DropdownButton(
                                  underline: const SizedBox(),
                                  icon: Icon(
                                    Icons.keyboard_arrow_down_outlined,
                                    size: 22,
                                    color: kGreyLightColor.withOpacity(.9),
                                  ),
                                  value: _selectedSubCat,
                                  onChanged: (value) {
                                    setState(() {
                                      _selectedSubCat = value.toString();
                                      print(_selectedSubCat);
                                    });
                                  },
                                  isExpanded: true,
                                  hint: const Text(
                                    'All Sub-Category',
                                    style: TextStyle(
                                      color: kSecondaryColor,
                                      fontSize: 15,
                                    ),
                                  ),
                                  items: allCategory[subIndex]
                                      .subCategory!
                                      .map((cd) {
                                    return DropdownMenuItem(
                                      value:
                                          cd.id == 0 ? 'all' : cd.id.toString(),
                                      child: Text(
                                        cd.title.toString(),
                                        style: const TextStyle(
                                          color: kSecondaryColor,
                                          fontSize: 15,
                                        ),
                                      ),
                                    );
                                  }).toList(),
                                ),
                              ),
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            const CustomText(
                              text: 'Pricing',
                              fontSize: 16,
                              fontWeight: FontWeight.w400,
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            Card(
                              elevation: 0.0,
                              margin: EdgeInsets.zero,
                              color: kInputBoxBackGroundColor,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 20, vertical: 5),
                                child: DropdownButton(
                                  underline: const SizedBox(),
                                  icon: Icon(
                                    Icons.keyboard_arrow_down_outlined,
                                    size: 22,
                                    color: kGreyLightColor.withOpacity(.9),
                                  ),
                                  value: _selectedPrice,
                                  onChanged: (value) {
                                    setState(() {
                                      _selectedPrice = value.toString();
                                    });
                                  },
                                  isExpanded: true,
                                  items: PriceFilter.getPriceFilter().map((dl) {
                                    return DropdownMenuItem(
                                      value: dl.id,
                                      child: Text(
                                        dl.name,
                                        style: const TextStyle(
                                          color: kSecondaryColor,
                                          fontSize: 15,
                                        ),
                                      ),
                                    );
                                  }).toList(),
                                ),
                              ),
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            const CustomText(
                              text: 'Level',
                              fontSize: 16,
                              fontWeight: FontWeight.w400,
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            Card(
                              elevation: 0.0,
                              margin: EdgeInsets.zero,
                              color: kInputBoxBackGroundColor,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 20, vertical: 5),
                                child: DropdownButton(
                                  underline: const SizedBox(),
                                  icon: Icon(
                                    Icons.keyboard_arrow_down_outlined,
                                    size: 22,
                                    color: kGreyLightColor.withOpacity(.9),
                                  ),
                                  value: _selectedLevel,
                                  onChanged: (value) {
                                    setState(() {
                                      _selectedLevel = value.toString();
                                    });
                                  },
                                  isExpanded: true,
                                  items: DifficultyLevel.getDifficultyLevel()
                                      .map((dl) {
                                    return DropdownMenuItem(
                                      value: dl.id,
                                      child: Text(
                                        dl.name,
                                        style: const TextStyle(
                                          color: kSecondaryColor,
                                          fontSize: 15,
                                        ),
                                      ),
                                    );
                                  }).toList(),
                                ),
                              ),
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            const CustomText(
                              text: 'Language',
                              fontSize: 16,
                              fontWeight: FontWeight.w400,
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            Card(
                              elevation: 0.0,
                              margin: EdgeInsets.zero,
                              color: kInputBoxBackGroundColor,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 20, vertical: 5),
                                child: DropdownButton(
                                  underline: const SizedBox(),
                                  icon: Icon(
                                    Icons.keyboard_arrow_down_outlined,
                                    size: 22,
                                    color: kGreyLightColor.withOpacity(.9),
                                  ),
                                  value: _selectedLanguage,
                                  onChanged: (value) {
                                    setState(() {
                                      _selectedLanguage = value.toString();
                                    });
                                  },
                                  isExpanded: true,
                                  items: langData.map((ld) {
                                    return DropdownMenuItem(
                                      value: ld.value,
                                      child: Text(
                                        ld.displayedValue.toString(),
                                        style: const TextStyle(
                                          color: kSecondaryColor,
                                          fontSize: 15,
                                        ),
                                      ),
                                    );
                                  }).toList(),
                                ),
                              ),
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            const CustomText(
                              text: 'Rating',
                              fontSize: 16,
                              fontWeight: FontWeight.w400,
                            ),
                            const SizedBox(
                              height: 10,
                            ),
                            Card(
                              elevation: 0.0,
                              margin: EdgeInsets.zero,
                              color: kInputBoxBackGroundColor,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 20, vertical: 5),
                                child: DropdownButton(
                                  underline: const SizedBox(),
                                  icon: Icon(
                                    Icons.keyboard_arrow_down_outlined,
                                    size: 22,
                                    color: kGreyLightColor.withOpacity(.9),
                                  ),
                                  value: _selectedRating,
                                  onChanged: (value) {
                                    setState(() {
                                      _selectedRating = value.toString();
                                    });
                                  },
                                  isExpanded: true,
                                  items: [0, 1, 2, 3, 4, 5].map((item) {
                                    return DropdownMenuItem(
                                      value:
                                          item == 0 ? 'all' : item.toString(),
                                      child: item == 0
                                          ? const Text(
                                              'All Rating',
                                              style: TextStyle(
                                                color: kSecondaryColor,
                                                fontSize: 15,
                                              ),
                                            )
                                          : StarDisplayWidget(
                                              value: item,
                                              filledStar: const Icon(
                                                Icons.star,
                                                color: kStarColor,
                                                size: 15,
                                              ),
                                              unfilledStar: const Icon(
                                                Icons.star,
                                                color: kGreyLightColor,
                                                size: 15,
                                              ),
                                            ),
                                    );
                                  }).toList(),
                                ),
                              ),
                            ),
                            const SizedBox(
                              height: 25,
                            ),
                            Center(
                              child: MaterialButton(
                                elevation: 0,
                                padding: const EdgeInsets.symmetric(
                                    vertical: 18, horizontal: 10),
                                onPressed: _submitForm,
                                color: kDefaultColor,
                                height: 50,
                                minWidth: 140,
                                textColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(16.0),
                                  side: const BorderSide(color: kDefaultColor),
                                ),
                                child: const Text(
                                  'Filter',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w500,
                                    fontSize: 16,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(
                              height: 15,
                            ),
                          ],
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
