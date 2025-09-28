import 'package:flutter/material.dart';

import '../constants.dart';
import '../widgets/appbar_one.dart';
import '../widgets/custom_text.dart';

class OfflineScreen extends StatefulWidget {
  const OfflineScreen({super.key});

  @override
  State<OfflineScreen> createState() => _OfflineScreenState();
}

class _OfflineScreenState extends State<OfflineScreen> {

  int _selectedPageIndex = 0;
  final _keywordController = TextEditingController();

  @override
  void initState() {
    super.initState();
  }

  void _selectPage(int index) {
    setState(() {
      _selectedPageIndex = index;
    });
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
      prefixIcon: const Icon(
        Icons.search,
        color: kGreyLightColor,
      ),
      hintStyle: const TextStyle(color: Colors.black54, fontSize: 16),
      hintText: hintext,
      fillColor: kInputBoxBackGroundColor,
      contentPadding: const EdgeInsets.symmetric(vertical: 15, horizontal: 15),
    );
  }

  Color _getTextColor(Set<WidgetState> states) => states.any(<WidgetState>{
        WidgetState.pressed,
        WidgetState.hovered,
        WidgetState.focused,
      }.contains)
          ? Colors.purpleAccent
          : kDefaultColor;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarOne(logo: 'light_logo.png'),
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20.0),
            child: Column(
              children: [
                const SizedBox(height: 25),
                SizedBox(
                  height: MediaQuery.of(context).size.height * .1,
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
                  ),
                ),
                const SizedBox(height: 30),
                Image.asset(
                  'assets/images/offline.png',
                  height: 217,
                  width: 286,
                ),
                const SizedBox(height: 20),
                const CustomText(
                  text: 'There is no Internet connection',
                  colors: kGreyLightColor,
                  fontSize: 16,
                  fontWeight: FontWeight.w400,
                ),
                const SizedBox(height: 5),
                const CustomText(
                  text: 'Please check your Internet connection',
                  colors: kGreyLightColor,
                  fontSize: 16,
                  fontWeight: FontWeight.w400,
                ),
                const SizedBox(height: 30),
                SizedBox(
                  height: 50,
                  width: 210,
                  child: ElevatedButton.icon(
                    onPressed: () {
                    },
                    style: ButtonStyle(
                      backgroundColor: WidgetStateColor.resolveWith(_getTextColor),
                      shape: WidgetStateProperty.all<RoundedRectangleBorder>(
                        RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16.0),
                        ),
                      ),
                    ),
                    icon: const Icon(Icons.download_done_rounded),
                    label: const Text(
                      'Play offline courses',
                      style: TextStyle(color: Colors.white),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      bottomNavigationBar: BottomNavigationBar(
        onTap: _selectPage,
        items: const [
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.home_outlined),
            activeIcon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.school_outlined),
            activeIcon: Icon(Icons.school),
            label: 'My Course',
          ),
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.favorite_border),
            activeIcon: Icon(Icons.favorite),
            label: 'Wishlist',
          ),
          BottomNavigationBarItem(
            backgroundColor: kBackGroundColor,
            icon: Icon(Icons.account_circle_outlined),
            activeIcon: Icon(Icons.account_circle),
            label: 'Account',
          ),
        ],
        backgroundColor: Colors.white,
        unselectedItemColor: kSecondaryColor,
        selectedItemColor: kSelectItemColor,
        currentIndex: _selectedPageIndex,
        type: BottomNavigationBarType.fixed,
      ),
    );
  }
}