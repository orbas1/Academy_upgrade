import 'package:academy_lms_app/widgets/appbar_one.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../constants.dart';
import '../providers/courses.dart';
import '../widgets/wishlist_List.dart';

class MyWishlistScreen extends StatefulWidget {
  const MyWishlistScreen({super.key});

  @override
  State<MyWishlistScreen> createState() => _MyWishlistScreenState();
}

class _MyWishlistScreenState extends State<MyWishlistScreen> {

  @override
  void initState() {
    super.initState();
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const AppBarOne(logo: 'light_logo.png'),
      body: Container(
        height: MediaQuery.of(context).size.height * 1,
        color: kBackGroundColor,
        child: SingleChildScrollView(
          child: Column(
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                child: const Text(
                  'My Wishlists',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              const SizedBox(height: 10),
              FutureBuilder(
                future: Provider.of<Courses>(context, listen: false).fetchMyWishlist(),
                builder: (ctx, dataSnapshot) {
                  if (dataSnapshot.connectionState == ConnectionState.waiting) {
                    return SizedBox(
                      height: MediaQuery.of(context).size.height * .7,
                      child: const Center(
                        child: CircularProgressIndicator(color: kDefaultColor),
                      ),
                    );
                  } else {
                    if (dataSnapshot.error != null) {
                      return const Center(
                        child: Text('Error Occurred'),
                      );
                    } else {
                      return Consumer<Courses>(
                        builder: (context, courseData, child) {
                          return Container(
                            margin: const EdgeInsets.only(bottom: 20.0),
                            padding: const EdgeInsets.symmetric(horizontal: 15),
                            child: ListView.builder(
                              shrinkWrap: true,
                              itemCount: courseData.items.length,
                              physics: const NeverScrollableScrollPhysics(),
                              itemBuilder: (ctx, index){
                                return WishlistList(
                                  course: courseData.items[index],
                                );
                              },
                            ),
                          );
                        }
                      );
                    }
                  }
                }
              ),
            ],
          ),
        ),
      ),
    );
  }
}