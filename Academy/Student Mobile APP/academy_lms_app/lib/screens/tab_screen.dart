// ignore_for_file: prefer_const_constructors, prefer_const_literals_to_create_immutables, use_key_in_widget_constructors, use_super_parameters, library_private_types_in_public_api

import 'package:academy_lms_app/constants.dart';
import 'package:academy_lms_app/screens/account.dart';
import 'package:academy_lms_app/screens/cart.dart';
import 'package:academy_lms_app/screens/filter_screen.dart';
import 'package:academy_lms_app/screens/home.dart';
import 'package:academy_lms_app/screens/login.dart';
import 'package:academy_lms_app/screens/my_courses.dart';
import 'package:academy_lms_app/widgets/appbar_one.dart';
import 'package:flutter/material.dart';
import 'package:convex_bottom_bar/convex_bottom_bar.dart';
import 'package:flutter_svg/svg.dart';
import 'package:shared_preferences/shared_preferences.dart';


class TabsScreen extends StatefulWidget {
  final int pageIndex;

  const TabsScreen({Key? key, this.pageIndex = 0}) : super(key: key);

  @override
  _TabsScreenState createState() => _TabsScreenState();
}

class _TabsScreenState extends State<TabsScreen> {
  int _selectedPageIndex = 0;
  bool isLoggedIn = false;
  bool _isInit = true;

  @override
  void initState() {
    super.initState();
    _selectedPageIndex = widget.pageIndex;
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    final prefs = await SharedPreferences.getInstance();
    final token = (prefs.getString('access_token') ?? '');

    setState(() {
      isLoggedIn = token.isNotEmpty;
      _isInit = false;
    });
  }

  List<Widget> _pages() {
    return isLoggedIn
        ? [HomeScreen(), MyCoursesScreen(), CartScreen(), AccountScreen()]
        : [HomeScreen(), LoginScreen(), LoginScreen(), LoginScreen()];
  }

  void _selectPage(int index) {
    setState(() {
      _selectedPageIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarOne(logo: 'light_logo.png'),
     
      body: 
      _isInit
          ? Center(child: CircularProgressIndicator())
          :
           IndexedStack(
              index: _selectedPageIndex,
              children: _pages(),
            ),
      floatingActionButton: _selectedPageIndex != 2
          ? FloatingActionButton(
              onPressed: () {
                Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => const FilterScreen(),
                    ));
              },
              backgroundColor: kWhiteColor,
              shape: RoundedRectangleBorder(
                  side: const BorderSide(width: 1, color: kDefaultColor),
                  borderRadius: BorderRadius.circular(100)),
              child: SvgPicture.asset(
                'assets/icons/filter.svg',
                colorFilter: const ColorFilter.mode(
                  kBlackColor,
                  BlendMode.srcIn,
                ),
              ),
            )
          : null,
      bottomNavigationBar: ConvexAppBar(
        items: [
          TabItem(
            icon: Padding(
              padding: const EdgeInsets.all(2.0),
              child: SvgPicture.asset(
                'assets/icons/home.svg',
                colorFilter:
                    const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
              ),
            ),
            activeIcon: Padding(
              padding: const EdgeInsets.all(8.0),
              child: SvgPicture.asset(
                'assets/icons/home.svg',
                colorFilter:
                    const ColorFilter.mode(kDefaultColor, BlendMode.srcIn),
              ),
            ),
            title: 'Home',
          ),
          TabItem(
            icon: Padding(
              padding: const EdgeInsets.all(2.0),
              child: SvgPicture.asset(
                'assets/icons/my_courses.svg',
                colorFilter:
                    const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
              ),
            ),
            activeIcon: Padding(
              padding: const EdgeInsets.all(8.0),
              child: SvgPicture.asset(
                'assets/icons/my_courses.svg',
                colorFilter:
                    const ColorFilter.mode(kDefaultColor, BlendMode.srcIn),
              ),
            ),
            title: 'My Courses',
          ),
          TabItem(
            icon: Padding(
              padding: const EdgeInsets.all(2.0),
              child: SvgPicture.asset(
                'assets/icons/shopping_bag.svg',
                colorFilter:
                    const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
              ),
            ),
            activeIcon: Padding(
              padding: const EdgeInsets.all(8.0),
              child: SvgPicture.asset(
                'assets/icons/shopping_bag.svg',
                colorFilter:
                    const ColorFilter.mode(kDefaultColor, BlendMode.srcIn),
              ),
            ),
            title: 'My Cart',
          ),
          TabItem(
            icon: Padding(
              padding: const EdgeInsets.all(2.0),
              child: SvgPicture.asset(
                'assets/icons/account.svg',
                colorFilter:
                    const ColorFilter.mode(kGreyLightColor, BlendMode.srcIn),
              ),
            ),
            activeIcon: Padding(
              padding: const EdgeInsets.all(8.0),
              child: SvgPicture.asset(
                'assets/icons/account.svg',
                colorFilter:
                    const ColorFilter.mode(kDefaultColor, BlendMode.srcIn),
              ),
            ),
            title: 'Account',
          ),
        ],
        backgroundColor: kWhiteColor,
        color: kGreyLightColor,
        activeColor: kBlackColor,
        elevation: 0,
        style: TabStyle.react,
        initialActiveIndex: _selectedPageIndex,
        onTap: _selectPage,
      ),
    );
  }
}

